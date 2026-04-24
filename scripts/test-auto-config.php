<?php

require __DIR__.'/../vendor/autoload.php';

use App\Services\MqttPublisher;

echo "Testing MQTT Auto Config...\n";
echo "============================\n";

try {
    echo "\n1. Publishing config for CH003 (humidification)...\n";
    $configId = MqttPublisher::publishAutoConfig(
        'CH003',
        'humidification',
        [
            'mode' => 'auto_threshold',
            'is_enabled' => true,
            'threshold_upper' => 80,
            'threshold_lower' => 60,
        ]
    );
    echo "   ✓ Published successfully! Config ID: {$configId}\n";
    echo "   Topic: chambers/CH003/config/auto\n";

} catch (Exception $e) {
    echo "\n   ✗ Error: ".$e->getMessage()."\n";
}
