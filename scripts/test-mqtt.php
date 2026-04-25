<?php

require __DIR__.'/../vendor/autoload.php';

use App\Services\MqttPublisher;

if (!isset($argv[1])) {
    echo "错误: 必须提供设备编码\n";
    echo "用法: php test-mqtt.php <设备编码>\n";
    echo "示例: php test-mqtt.php CH003\n";
    exit(1);
}

$deviceCode = $argv[1];

echo "Testing MQTT Publisher...\n";
echo "========================\n";

try {
    // Test 1: Basic connectivity
    echo "\n1. Testing basic MQTT connection...\n";
    $client = MqttPublisher::getClient();
    echo "   ✓ Connected to MQTT Broker\n";

    // Test 2: Publish to manual control topic
    echo "\n2. Publishing test message to chambers/{$deviceCode}/command/manual...\n";
    $commandId = MqttPublisher::publishManualControl(
        $deviceCode,
        ['cooling' => true],
        null
    );
    echo "   ✓ Published successfully! Command ID: {$commandId}\n";

    // Test 3: Publish auto config
    echo "\n3. Publishing test config to chambers/{$deviceCode}/config/auto...\n";
    $configId = MqttPublisher::publishAutoConfig(
        $deviceCode,
        'fresh_air',
        ['mode' => 'auto_threshold', 'is_enabled' => true]
    );
    echo "   ✓ Published successfully! Config ID: {$configId}\n";

    echo "\n========================\n";
    echo "All tests passed!\n";

} catch (Exception $e) {
    echo "\n   ✗ Error: ".$e->getMessage()."\n";
    echo "   Stack trace:\n".$e->getTraceAsString()."\n";
}
