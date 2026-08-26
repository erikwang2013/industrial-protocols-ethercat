<?php

/*
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

namespace Erikwang2013\IndustrialProtocols\EtherCat\Tests\Simulation;

use Erikwang2013\IndustrialProtocols\Bridge\TcpGatewayBridge;
use Erikwang2013\IndustrialProtocols\Connection\ConnectionState;
use Erikwang2013\IndustrialProtocols\EtherCat\EtherCatProtocol;
use PHPUnit\Framework\TestCase;

class EtherCatBridgeTest extends TestCase
{
    /**
     * Start a fake TCP gateway in a child process speaking the TcpGatewayBridge
     * wire protocol: [cmdLen:2][cmd][payloadLen:4][payload] -> raw response.
     */
    private function startGateway(int $port): array
    {
        $proc = proc_open([PHP_BINARY, '-r', <<<'STUB'
            $server = stream_socket_server('tcp://127.0.0.1:' . $argv[1], $errno, $errstr);
            if (!$server) { fwrite(STDERR, "server fail: $errstr\n"); exit(1); }
            echo "READY\n";
            flush();
            $client = @stream_socket_accept($server, 5);
            if ($client) {
                while (($head = fread($client, 2)) !== '' && $head !== false) {
                    if (strlen($head) < 2) break;
                    $cmdLen = unpack('v', $head)[1];
                    $cmd = fread($client, $cmdLen);
                    $payloadLen = unpack('V', fread($client, 4))[1];
                    $payload = $payloadLen > 0 ? fread($client, $payloadLen) : '';
                    fwrite($client, "OK:$cmd:" . $payload);
                }
                fclose($client);
            }
            fclose($server);
STUB, (string) $port], [1 => ['pipe', 'w']], $pipes);

        fgets($pipes[1]);
        return [$proc, $pipes];
    }

    public function testBridgeLifecycleRoundTrip(): void
    {
        [$proc] = $this->startGateway(15080);

        $protocol = new EtherCatProtocol();
        $this->assertSame('ethercat', $protocol->getName());
        $this->assertSame('1.1.1', $protocol->getVersion());
        $this->assertSame(0, $protocol->getDefaultPort());
        $this->assertContains('bridge', $protocol->getSupportedVariants());

        $bridge = new TcpGatewayBridge('127.0.0.1', 15080, 2.0);
        $connector = $protocol->createConnector(['bridge' => $bridge]);
        $this->assertFalse($connector->isConnected());
        $this->assertSame(ConnectionState::CLOSED, $connector->getHealth()->state);

        $connector->connect();
        $this->assertTrue($connector->isConnected());
        $this->assertSame(ConnectionState::HEALTHY, $connector->getHealth()->state);

        $read = $connector->read('S-100');
        $this->assertStringStartsWith('OK:read:', $read['S-100']);

        $write = $connector->write('S-100', [42]);
        $this->assertStringContainsString('"value":42', $write['S-100']);

        $raw = $connector->command('ident');
        $this->assertStringStartsWith('OK:ident:', $raw);

        $connector->disconnect();
        $this->assertFalse($connector->isConnected());
        $this->assertSame(ConnectionState::CLOSED, $connector->getHealth()->state);

        proc_close($proc);
    }

    public function testCreateConnectorWithoutBridgeThrows(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('BridgeInterface');
        (new EtherCatProtocol())->createConnector([]);
    }

    public function testCreateConnectorWithNonBridgeConfigThrows(): void
    {
        $this->expectException(\TypeError::class);
        (new EtherCatProtocol())->createConnector(['bridge' => new \stdClass()]);
    }

    public function testGatewayConnectRefused(): void
    {
        $bridge = new TcpGatewayBridge('127.0.0.1', 15980, 1.0);
        $connector = (new EtherCatProtocol())->createConnector(['bridge' => $bridge]);
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Gateway bridge connect failed');
        $connector->connect();
    }
}
