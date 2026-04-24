<?php

require __DIR__.'/../vendor/autoload.php';

use App\Services\MqttPublisher;

echo "Testing MQTT Publisher...\n";
echo "========================\n";

try {
    // Test 1: Basic connectivity
    echo "\n1. Testing basic MQTT connection...\n";
    $client = MqttPublisher::getClient();
    echo "   ✓ Connected to MQTT Broker\n";

    // Test 2: Publish to manual control topic
    echo "\n2. Publishing test message to chambers/CH003/command/manual...\n";
    $commandId = MqttPublisher::publishManualControl(
        'CH003',
        ['cooling' => true],
        null
    );
    echo "   ✓ Published successfully! Command ID: {$commandId}\n";

    // Test 3: Publish auto config
    echo "\n3. Publishing test config to chambers/CH003/config/auto...\n";
    $configId = MqttPublisher::publishAutoConfig(
        'CH003',
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
