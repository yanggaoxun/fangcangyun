#!/usr/bin/env php
<?php

/**
 * MQTT 配置下发测试工具
 *
 * 用法:
 *   php scripts/test-mqtt-config.php CH003        # 测试下发配置到 CH003
 *   php scripts/test-mqtt-config.php CH003 manual  # 测试手动控制到 CH003
 */

require __DIR__.'/../vendor/autoload.php';

$deviceCode = $argv[1] ?? 'CH003';
$testType = $argv[2] ?? 'auto'; // 'auto' 或 'manual'

use App\Services\MqttPublisher;

echo "MQTT 测试工具\n";
echo "==============\n";
echo "目标设备: {$deviceCode}\n";
echo "测试类型: {$testType}\n\n";

try {
    if ($testType === 'auto') {
        echo "正在发送自动控制配置...\n";
        echo "Topic: chambers/{$deviceCode}/config/auto\n";

        $configId = MqttPublisher::publishAutoConfig(
            $deviceCode,
            'humidification',
            [
                'mode' => 'auto_threshold',
                'is_enabled' => true,
                'threshold_upper' => 80,
                'threshold_lower' => 60,
                'test_time' => date('Y-m-d H:i:s'),
            ]
        );

        echo "✓ 配置发送成功!\n";
        echo "Config ID: {$configId}\n";

    } else {
        echo "正在发送手动控制命令...\n";
        echo "Topic: chambers/{$deviceCode}/command/manual\n";

        $commandId = MqttPublisher::publishManualControl(
            $deviceCode,
            ['humidification' => true],
            null
        );

        echo "✓ 命令发送成功!\n";
        echo "Command ID: {$commandId}\n";
    }

    echo "\n请检查设备模拟器是否收到消息。\n";

} catch (Exception $e) {
    echo '✗ 错误: '.$e->getMessage()."\n";
    echo "\n可能原因:\n";
    echo "1. EMQX Broker 未启动\n";
    echo "2. 设备模拟器未运行\n";
    echo "3. 网络连接问题\n";
}
