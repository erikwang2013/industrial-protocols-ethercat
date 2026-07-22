# EtherCAT 协议包 — 需 Beckhoff TwinCAT/SOEM 桥接

> [中文](README.md)

EtherCAT 协议包 — 需 Beckhoff TwinCAT/SOEM 桥接。Requires dedicated hardware, bridged to vendor SDK or gateway via kernel Bridge layer.

## Installation

```bash
composer require erikwang2013/industrial-protocols-kernel erikwang2013/industrial-protocols-ethercat
```

> Depends on [erikwang2013/industrial-protocols-kernel](https://github.com/erikwang2013/industrial-protocols-kernel) for hardware bridge layer, connection management, and protocol registry.

## Design

EtherCAT (Ethernet for Control Automation Technology) is a real-time industrial Ethernet protocol developed by Beckhoff. Slaves use ESC (EtherCAT Slave Controller) dedicated chips (e.g. ET1100/ET1200) implementing on-the-fly processing at the hardware layer — frames are processed as they pass through each slave without software intervention. Pure PHP cannot implement an EtherCAT master protocol stack — external SDK bridging (TwinCAT ADS or SOEM) is required. This package wraps BridgeConnector, mapping EtherCAT protocol interfaces to BridgeInterface for unified ConnectorInterface access.

## Architecture

Bridge mode: BridgeConnector (implements ConnectorInterface) → BridgeInterface (open/close/execute/isReady) → ExternalProcessBridge (local SDK subprocess via proc_open stdin/stdout) or TcpGatewayBridge (remote gateway via TCP/UDP Socket). Applications use ConnectionManager for unified connect/read/write/getHealth calls.

## Supported Frameworks

Compatible with 6 PHP runtimes via kernel framework adapters: Laravel (ServiceProvider+Facade+artisan), Webman (config/plugin auto-discovery+ProtocolProcess), Hyperf (ConfigProvider+DI+KernelFactory), ThinkPHP (services.php+IndustrialProtocolsService), Yii2 (Bootstrap+component), Plain PHP (direct Kernel instantiation)

## Usage

```php
use Erikwang2013\IndustrialProtocols\Kernel;
use IndustrialProtocols\Bridge\TcpGatewayBridge;

$kernel = new Kernel(['config_path' => __DIR__ . '/config.php']);
$kernel->boot();

// Via TCP gateway
$bridge = new TcpGatewayBridge('192.168.1.200', 502);
$conn = $kernel->getConnectionManager()->connect('device-id', [
    'protocol' => 'ethercat', 'bridge' => $bridge,
]);
$result = $conn->read('address');

// Via vendor factory
$bridge = $kernel->getVendorBridgeFactory()->create('beckhoff', 'CX2030', '3.1');
```

## Adapter Vendors

Beckhoff (TwinCAT 3, CX2030/5140, EK1100/1501), Hilscher (netX 90/4000, cifX RE), HMS/Anybus (EtherCAT Slave)

## Requirements

- PHP >= 8.1
- Composer
- erikwang2013/industrial-protocols-kernel
- EtherCAT Master (Beckhoff TwinCAT 3 / SOEM (Simple Open EtherCAT Master) open-source library)

## Related Links

- [Industrial Protocols Main Project](https://github.com/erikwang2013/industrial-protocols)
- [Kernel](https://github.com/erikwang2013/industrial-protocols-kernel)
- [All 42 Protocol Packages](https://github.com/erikwang2013/industrial-protocols#supported-protocols)

## License

MIT — Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
