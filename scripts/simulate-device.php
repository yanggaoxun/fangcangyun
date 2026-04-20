<?php

/**
 * 边缘设备模拟器
 *
 * 用法：php scripts/simulate-device.php CH001
 */

require __DIR__.'/../vendor/autoload.php';

use PhpMqtt\Client\ConnectionSettings;
use PhpMqtt\Client\MqttClient;

$deviceCode = $argv[1] ?? 'CH001';
$broker = 'emqx';
$port = 1883;
$username = "device_{$deviceCode}";
$password = 'device_password';

$connectionSettings = (new ConnectionSettings)
    ->setConnectTimeout(10)
    ->setSocketTimeout(10)
    ->setKeepAliveInterval(60)
    ->setUsername($username)
    ->setPassword($password);

$client = new MqttClient($broker, $port, "device_{$deviceCode}_".uniqid());

echo "设备 {$deviceCode} 正在连接到 MQTT Broker...\n";

try {
    $client->connect($connectionSettings, true);
    echo "连接成功！\n\n";

    // 订阅命令 Topic
    $client->subscribe("chambers/{$deviceCode}/command/manual", function ($topic, $message) use ($deviceCode) {
        $data = json_decode($message, true);
        echo '【收到命令】'.date('Y-m-d H:i:s')."\n";
        echo "Topic: {$topic}\n";
        echo "Command ID: {$data['command_id']}\n";
        echo 'Actions: '.json_encode($data['actions'])."\n";

        // 模拟执行命令
        sleep(1);

        // 发送 ACK
        global $client;
        $ack = [
            'command_id' => $data['command_id'],
            'status' => 'success',
            'executed_at' => time(),
        ];
        $client->publish("chambers/{$deviceCode}/ack", json_encode($ack), 1);
        echo "【ACK 已发送】\n\n";
    }, 1);

    $client->subscribe("chambers/{$deviceCode}/config/auto", function ($topic, $message) {
        $data = json_decode($message, true);
        echo '【收到配置】'.date('Y-m-d H:i:s')."\n";
        echo "Topic: {$topic}\n";
        echo "Config ID: {$data['config_id']}\n";
        echo "Control Type: {$data['control_type']}\n";
        echo 'Config: '.json_encode($data['config'])."\n\n";
    }, 1);

    echo "已订阅命令和配置 Topic\n";
    echo "开始定时上报数据（每 60 秒）...\n";
    echo "按 Ctrl+C 停止\n\n";

    // 启动消息循环（非阻塞）
    //$client->loop(true, true, 1000);

    // 定时上报
    $lastReport = 0;
    while (true) {
        $now = time();
        if ($now - $lastReport >= 60) {
            $data = [
                'timestamp' => $now,
                'temperature' => round(20 + mt_rand(-50, 100) / 10, 1),
                'humidity' => round(60 + mt_rand(-100, 200) / 10, 1),
                'co2_level' => mt_rand(400, 1200),
                'devices' => [
                    'inner_circulation' => (bool) mt_rand(0, 1),
                    'cooling' => (bool) mt_rand(0, 1),
                    'heating' => (bool) mt_rand(0, 1),
                    'fan' => (bool) mt_rand(0, 1),
                    'four_way_valve' => false,
                    'fresh_air' => (bool) mt_rand(0, 1),
                    'humidification' => (bool) mt_rand(0, 1),
                    'lighting_supplement' => (bool) mt_rand(0, 1),
                    'lighting' => (bool) mt_rand(0, 1),
                ],
            ];

            $client->publish("chambers/{$deviceCode}/data", json_encode($data), 1);
            echo '【数据上报】'.date('Y-m-d H:i:s')." - 温度: {$data['temperature']}°C, 湿度: {$data['humidity']}%\n";

            $lastReport = $now;
        }

        usleep(100000); // 100ms
    }

} catch (Exception $e) {
    echo '错误: '.$e->getMessage()."\n";
}
