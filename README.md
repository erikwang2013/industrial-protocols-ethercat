# EtherCAT 协议包 — 需 Beckhoff TwinCAT 3 / SOEM 桥接

> [English](README.en.md)

EtherCAT (Ethernet for Control Automation Technology) 高速实时工业以太网。需要 Beckhoff TwinCAT 3 或 SOEM (Simple Open EtherCAT Master) SDK，通过内核 ExternalProcessBridge 桥接。

## 安装

```bash
composer require erikwang2013/industrial-protocols-ethercat
```

## 功能

EtherCAT 主站桥接（CoE SDO 读写）、ExternalProcessBridge 进程通信、BridgeConnector 连接管理

## 所需硬件/SDK

Beckhoff TwinCAT 3 ADS SDK、SOEM (Simple Open EtherCAT Master)、Beckhoff CX2030/CX5140/C6015/C6030/EK1100/EK1501

## 使用说明

```php
$bridge = new ExternalProcessBridge('/opt/ethercat-sdk/bin/ecat_master');
$conn = $kernel->getConnectionManager()->connect('ethercat-device', [
    'protocol' => 'ethercat', 'bridge' => $bridge,
]);
$result = $conn->read('0x6000:0x01');  // CoE SDO 读取
```

## 兼容框架

Laravel / Webman / Hyperf / ThinkPHP / Yii2 / Plain PHP

## 系统要求

- PHP >= 8.1
- Beckhoff TwinCAT 3 或 SOEM SDK
- erikwang2013/industrial-protocols-kernel

## License

MIT — Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
