<?php

require __DIR__.'/../vendor/autoload.php';

use App\Services\MqttPublisher;

if (!isset($argv[1])) {
    echo "错误: 必须提供设备编码\n";
    echo "用法: php test-auto-config.php <设备编码>\n";
    echo "示例: php test-auto-config.php CH003\n";
    exit(1);
}

$deviceCode = $argv[1];

echo "Testing MQTT Auto Config...\n";
echo "============================\n";

try {
    echo "\n1. Publishing config for {$deviceCode} (humidification)...\n";
    $configId = MqttPublisher::publishAutoConfig(
        $deviceCode,
        'humidification',
        [
            'mode' => 'auto_threshold',
            'is_enabled' => true,
            'threshold_upper' => 80,
            'threshold_lower' => 60,
        ]
    );
    echo "   ✓ Published successfully! Config ID: {$configId}\n";
    echo "   Topic: chambers/{$deviceCode}/config/auto\n";

} catch (Exception $e) {
    echo "\n   ✗ Error: ".$e->getMessage()."\n";
}
