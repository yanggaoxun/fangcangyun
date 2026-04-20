<?php

namespace App\Console\Commands;

use App\Octane\Processes\MqttConsumerProcess;
use Illuminate\Console\Command;

class MqttConsumeCommand extends Command
{
    protected $signature = 'mqtt:consume';

    protected $description = 'Start MQTT consumer to receive data from edge devices';

    public function handle(): int
    {
        $this->info('Starting MQTT Consumer...');

        $process = new MqttConsumerProcess;
        $process->handle();

        return 0;
    }
}
