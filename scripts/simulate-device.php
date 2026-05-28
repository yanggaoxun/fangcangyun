#!/usr/bin/env php
<?php

/**
 * 边缘设备模拟器 (PHP 版本)
 *
 * 用法:
 *   php scripts/simulate-device.php CH001              # 默认连接 emqx（Docker 内部）
 *   php scripts/simulate-device.php CH001 localhost    # 连接本地 MQTT Broker
 *   php scripts/simulate-device.php CH001 192.168.1.100 # 连接指定 IP
 */

require __DIR__.'/../vendor/autoload.php';

use PhpMqtt\Client\ConnectionSettings;
use PhpMqtt\Client\MqttClient;

// 配置
if (! isset($argv[1])) {
    echo "错误: 必须提供设备编码\n";
    echo "用法: php simulate-device.php <设备编码> [broker地址]\n";
    echo "示例: php simulate-device.php CH001\n";
    exit(1);
}

$deviceCode = $argv[1];
$broker = $argv[2] ?? 'emqx';
$port = 1883;
$username = 'device';
$password = 'device_password';
$clientId = "device_{$deviceCode}_".uniqid();

// 设备状态跟踪（收到命令后更新，上报时使用真实状态）
$deviceStates = [
    'inner_circulation' => false,
    'cooling' => false,
    'heating' => false,
    'fan' => false,
    'four_way_valve' => false,
    'fresh_air' => false,
    'humidification' => false,
    'lighting_supplement' => false,
    'lighting' => false,
];

$running = true;

// 信号处理
pcntl_signal(SIGINT, function () use (&$running) {
    echo "\n正在停止...\n";
    $running = false;
});

pcntl_signal(SIGTERM, function () use (&$running) {
    echo "\n正在停止...\n";
    $running = false;
});

echo "设备 {$deviceCode} 正在连接到 MQTT Broker ({$broker}:{$port})...\n";

try {
    // 创建连接配置
    $connectionSettings = (new ConnectionSettings)
        ->setConnectTimeout(10)
        ->setSocketTimeout(10)
        ->setKeepAliveInterval(60)
        ->setUsername($username)
        ->setPassword($password);

    // 创建客户端
    $client = new MqttClient($broker, $port, $clientId);
    $client->connect($connectionSettings, true);

    echo "连接成功！\n\n";

    // 订阅命令 Topic
    $client->subscribe("chambers/{$deviceCode}/command/manual", function ($topic, $message) use ($client, $deviceCode, &$deviceStates) {
        $data = json_decode($message, true);

        echo '【收到命令】'.date('Y-m-d H:i:s')."\n";
        echo "Topic: {$topic}\n";
        echo "Command ID: {$data['command_id']}\n";
        echo 'Actions: '.json_encode($data['actions'])."\n";

        // 执行命令：更新本地设备状态
        $actions = $data['actions'] ?? [];
        $executedActions = [];
        foreach ($actions as $device => $state) {
            if (isset($deviceStates[$device])) {
                $deviceStates[$device] = (bool) $state;
                $executedActions[$device] = (bool) $state;
                echo "  → {$device}: ".($state ? '开启' : '关闭')."\n";
            }
        }

        // 模拟执行耗时
        sleep(1);

        // 发送 ACK（包含实际执行的动作）
        $ack = [
            'command_id' => $data['command_id'],
            'status' => 'success',
            'executed_at' => time(),
            'executed_actions' => $executedActions,
        ];
        $client->publish("chambers/{$deviceCode}/ack", json_encode($ack), 1);
        echo "【ACK 已发送】\n\n";
    }, 1);

    echo "已订阅命令 Topic\n";

    // 订阅配置 Topic
    $client->subscribe("chambers/{$deviceCode}/config/auto", function ($topic, $message) use ($client, $deviceCode) {
        $data = json_decode($message, true);

        $configType = $data['control_type'] ?? null;
        $configData = $data['config'] ?? [];
        $configId = $data['config_id'] ?? null;

        echo '【收到配置】'.date('Y-m-d H:i:s')."\n";
        echo "Topic: {$topic}\n";
        echo "Config ID: {$configId}\n";
        echo "Control Type: {$configType}\n";
        echo 'Config: '.json_encode($configData)."\n";

        $saveSuccess = false;
        $errorMessage = '';

        // 保存配置到 JSON 文件
        if ($configType) {
            $filename = "{$configType}.json";
            try {
                $configToSave = [
                    'config_id' => $configId,
                    'device_code' => $deviceCode,
                    'received_at' => (new DateTime)->format('c'),
                    'control_type' => $configType,
                    'config' => $configData,
                ];
                file_put_contents($filename, json_encode($configToSave, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                echo "【配置已保存】{$filename}\n";
                $saveSuccess = true;
            } catch (Exception $e) {
                $errorMessage = $e->getMessage();
                echo "【保存配置失败】{$filename}: {$errorMessage}\n";
            }
        } else {
            $errorMessage = '未找到 control_type';
            echo "【警告】未找到 control_type，无法保存配置\n";
        }

        // 发送 ACK（使用 command_id 以保持与手动控制一致）
        $ack = [
            'command_id' => $configId,
            'status' => $saveSuccess ? 'success' : 'failed',
            'received_at' => time(),
        ];

        if (! $saveSuccess && $errorMessage) {
            $ack['error'] = $errorMessage;
        }

        $client->publish("chambers/{$deviceCode}/ack", json_encode($ack), 1);
        echo "【ACK 已发送】\n\n";
    }, 1);

    echo "已订阅配置 Topic\n\n";
    echo "开始定时上报数据（每 60 秒）...\n";
    echo "按 Ctrl+C 停止\n\n";

    // 定时上报
    $lastReport = 0;
    while ($running) {
        pcntl_signal_dispatch();

        $now = time();
        if ($now - $lastReport >= 60) {
            $data = [
                'timestamp' => $now,
                'temperature' => round(20 + mt_rand(-50, 100) / 10, 1),
                'humidity' => round(60 + mt_rand(-100, 200) / 10, 1),
                'co2_level' => mt_rand(400, 1200),
                'devices' => $deviceStates, // 使用当前真实设备状态
            ];

            $client->publish("chambers/{$deviceCode}/data", json_encode($data), 1);

            // 显示当前设备状态
            $activeDevices = [];
            foreach ($deviceStates as $device => $state) {
                if ($state) {
                    $activeDevices[] = $device;
                }
            }
            $statusStr = empty($activeDevices) ? '全部关闭' : implode(', ', $activeDevices);

            echo '【数据上报】'.date('Y-m-d H:i:s')." - 温度: {$data['temperature']}°C, 湿度: {$data['humidity']}%, 运行设备: {$statusStr}\n";

            $lastReport = $now;
        }

        // 非阻塞处理 MQTT 消息
        $client->loop(true, false, 100);
        usleep(100000); // 100ms
    }

    $client->disconnect();
    echo "已断开连接\n";

} catch (Exception $e) {
    echo '错误: '.$e->getMessage()."\n";
    if (isset($client)) {
        $client->disconnect();
    }
    exit(1);
}
