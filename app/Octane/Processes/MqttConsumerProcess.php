<?php

namespace App\Octane\Processes;

use App\Models\ChamberManualControl;
use App\Models\DevDevice;
use App\Models\SysAlert;
use App\Services\ChamberAutoControlService;
use PhpMqtt\Client\ConnectionSettings;
use PhpMqtt\Client\MqttClient;

class MqttConsumerProcess
{
    public function handle(): void
    {
        $config = config('mqtt');

        $connectionSettings = (new ConnectionSettings)
            ->setConnectTimeout($config['connect_timeout'])
            ->setSocketTimeout($config['socket_timeout'])
            ->setKeepAliveInterval($config['keep_alive'])
            ->setUsername($config['username'])
            ->setPassword($config['password'])
            ->setLastWillTopic($config['last_will']['topic'])
            ->setLastWillMessage($config['last_will']['message'])
            ->setLastWillQualityOfService($config['last_will']['qos'])
            ->setRetainLastWill($config['last_will']['retain']);

        $client = new MqttClient(
            $config['broker'],
            $config['port'],
            $config['client_id'].'_consumer_'.getmypid().'_'.uniqid()
        );

        try {
            $client->connect($connectionSettings, true);

            echo "MQTT Consumer connected to {$config['broker']}:{$config['port']}\n";

            // 订阅所有方舱的数据上报
            $client->subscribe('chambers/+/data', function ($topic, $message) {
                $this->handleData($topic, $message);
            }, $config['qos']);

            // 订阅设备状态上报
            $client->subscribe('chambers/+/status', function ($topic, $message) {
                $this->handleStatus($topic, $message);
            }, $config['qos']);

            // 订阅设备 ACK
            $client->subscribe('chambers/+/ack', function ($topic, $message) {
                $this->handleAck($topic, $message);
            }, $config['qos']);

            // 订阅报警
            $client->subscribe('chambers/+/alarm', function ($topic, $message) {
                $this->handleAlarm($topic, $message);
            }, $config['qos']);

            echo "Subscribed to MQTT topics\n";

            // 发布服务器在线状态
            $client->publish('server/status', json_encode([
                'status' => 'online',
                'time' => now()->toIso8601String(),
            ]), 1, true);

            // 保持连接，持续消费消息
            $client->loop(true);

        } catch (\Exception $e) {
            echo 'MQTT Consumer error: '.$e->getMessage()."\n";
            sleep(5);
            // 重连
            $this->handle();
        }
    }

    protected function handleData(string $topic, string $message): void
    {
        try {
            $data = json_decode($message, true);
            if (! $data) {
                return;
            }

            // 提取设备编号（从 topic: chambers/{device_code}/data）
            preg_match('/chambers\/(.+)\/data/', $topic, $matches);
            $deviceCode = $matches[1] ?? null;

            if (! $deviceCode) {
                return;
            }

            // 通过 dev_devices.code 查找设备，获取 chamber_id
            $device = DevDevice::where('code', $deviceCode)->first();
            if (! $device) {
                \Log::warning("Device not found in dev_devices: {$deviceCode}");

                return;
            }

            $chamberId = $device->chamber_id;
            if (! $chamberId) {
                \Log::warning("Device {$deviceCode} has no chamber_id");

                return;
            }

            // 准备数据
            $recordData = [
                'temperature' => $data['temperature'] ?? null,
                'humidity' => $data['humidity'] ?? null,
                'co2_level' => $data['co2_level'] ?? null,
                'ph_level' => $data['ph_level'] ?? null,
                'light_intensity' => $data['light_intensity'] ?? null,
                'soil_moisture' => $data['soil_moisture'] ?? null,
                'recorded_at' => $data['timestamp'] ?? now(),
            ];

            // 添加设备状态字段
            if (isset($data['devices'])) {
                foreach ($data['devices'] as $deviceKey => $state) {
                    if (in_array($deviceKey, [
                        'inner_circulation', 'cooling', 'heating', 'fan',
                        'four_way_valve', 'fresh_air', 'humidification',
                        'lighting_supplement', 'lighting',
                    ])) {
                        $recordData[$deviceKey] = $state;
                    }
                }
            }

            // 确保所有设备控制字段都有默认值（false = 关闭）
            $deviceFields = [
                'inner_circulation', 'cooling', 'heating', 'fan',
                'four_way_valve', 'fresh_air', 'humidification',
                'lighting_supplement', 'lighting',
            ];
            foreach ($deviceFields as $field) {
                if (! array_key_exists($field, $recordData)) {
                    $recordData[$field] = false;
                }
            }

            // 存在则更新，不存在则创建
            ChamberManualControl::updateOrCreate(
                ['chamber_id' => $chamberId],
                $recordData
            );
            // 触发自动控制
            $this->processAutoControl($chamberId, $data);

        } catch (\Exception $e) {
            \Log::error('MQTT data processing error: '.$e->getMessage(), [
                'topic' => $topic,
                'message' => $message,
            ]);
        }
    }

    protected function handleStatus(string $topic, string $message): void
    {
        try {
            $data = json_decode($message, true);
            if (! $data) {
                return;
            }

            preg_match('/chambers\/(.+)\/status/', $topic, $matches);
            $deviceCode = $matches[1] ?? null;

            if (! $deviceCode) {
                return;
            }

            // 通过 dev_devices.code 查找设备
            $device = DevDevice::where('code', $deviceCode)->first();
            if (! $device) {
                \Log::warning("Device not found in dev_devices: {$deviceCode}");

                return;
            }

            $chamberId = $device->chamber_id;
            if (! $chamberId) {
                \Log::warning("Device {$deviceCode} has no chamber_id");

                return;
            }

            $isOnline = $data['online'] ?? true;
            $heartbeatAt = $data['timestamp'] ?? now();

            // 更新设备在线状态
            ChamberManualControl::updateOrCreate(
                ['chamber_id' => $chamberId],
                [
                    'is_online' => $isOnline,
                    'last_heartbeat_at' => $heartbeatAt,
                ]
            );

            \Log::info('Device status updated', [
                'device' => $deviceCode,
                'chamber_id' => $chamberId,
                'is_online' => $isOnline,
            ]);

        } catch (\Exception $e) {
            \Log::error('MQTT status processing error: '.$e->getMessage());
        }
    }

    protected function handleAck(string $topic, string $message): void
    {
        try {
            $data = json_decode($message, true);
            if (! $data) {
                return;
            }

            preg_match('/chambers\/(.+)\/ack/', $topic, $matches);
            $deviceCode = $matches[1] ?? null;

            if (! $deviceCode) {
                return;
            }

            // 通过 dev_devices.code 验证设备是否存在
            $device = DevDevice::where('code', $deviceCode)->first();
            if (! $device) {
                \Log::warning("Device not found in dev_devices: {$deviceCode}");

                return;
            }

            $commandId = $data['command_id'] ?? null;
            $status = $data['status'] ?? 'unknown';

            \Log::info('Command ACK received', [
                'device' => $deviceCode,
                'command_id' => $commandId,
                'status' => $status,
            ]);

            // 更新命令执行状态
            if ($commandId) {
                \App\Models\ChamberControlLog::updateAckStatus($commandId, $status);
            }

        } catch (\Exception $e) {
            \Log::error('MQTT ACK processing error: '.$e->getMessage());
        }
    }

    protected function handleAlarm(string $topic, string $message): void
    {
        try {
            $data = json_decode($message, true);
            if (! $data) {
                return;
            }

            preg_match('/chambers\/(.+)\/alarm/', $topic, $matches);
            $deviceCode = $matches[1] ?? null;

            if (! $deviceCode) {
                return;
            }

            // 通过 dev_devices.code 查找设备
            $device = DevDevice::where('code', $deviceCode)->first();
            if (! $device) {
                \Log::warning("Device not found in dev_devices: {$deviceCode}");

                return;
            }

            $chamberId = $device->chamber_id;
            if (! $chamberId) {
                \Log::warning("Device {$deviceCode} has no chamber_id");

                return;
            }

            $alarmType = $data['type'] ?? 'device_error';
            $level = $data['level'] ?? 'warning';
            $title = $data['title'] ?? '设备报警';
            $messageText = $data['message'] ?? json_encode($data);
            $triggerValue = $data['trigger_value'] ?? null;
            $thresholdValue = $data['threshold_value'] ?? null;

            // 创建报警记录
            try {
                SysAlert::create([
                    'chamber_id' => $chamberId,
                    'type' => $alarmType,
                    'level' => $level,
                    'title' => $title,
                    'message' => $messageText,
                    'trigger_value' => $triggerValue,
                    'threshold_value' => $thresholdValue,
                    'is_acknowledged' => false,
                    'is_resolved' => false,
                ]);
                
                \Log::warning('Alarm record created', [
                    'device' => $deviceCode,
                    'chamber_id' => $chamberId,
                    'type' => $alarmType,
                    'level' => $level,
                ]);
            } catch (\Exception $e) {
                \Log::error('Failed to create alarm record: ' . $e->getMessage(), [
                    'chamber_id' => $chamberId,
                    'type' => $alarmType,
                ]);
            }

        } catch (\Exception $e) {
            \Log::error('MQTT alarm processing error: '.$e->getMessage());
        }
    }

    protected function processAutoControl(int $chamberId, array $data): void
    {
        try {
            $service = new ChamberAutoControlService;

            // 检查每个控制类型是否需要自动调节
            $controlTypes = ['temperature', 'humidity', 'fresh_air', 'exhaust', 'lighting'];

            foreach ($controlTypes as $controlType) {
                if (! isset($data[$controlType])) {
                    continue;
                }

                $config = \App\Models\ChamberControlConfig::where('chamber_id', $chamberId)
                    ->where('control_type', $controlType)
                    ->where('is_enabled', true)
                    ->first();

                if (! $config) {
                    continue;
                }

                // 根据控制模式处理
                switch ($config->mode) {
                    case 'auto_threshold':
                        $this->processThresholdControl($chamberId, $controlType, $data[$controlType], $config);
                        break;
                    case 'auto_schedule':
                        $this->processScheduleControl($chamberId, $controlType, $config);
                        break;
                    case 'auto_cycle':
                        $this->processCycleControl($chamberId, $controlType, $config);
                        break;
                }
            }

        } catch (\Exception $e) {
            \Log::error('Auto control processing error: '.$e->getMessage());
        }
    }

    protected function processThresholdControl(int $chamberId, string $controlType, $currentValue, $config): void
    {
        if (! $config->threshold_upper || ! $config->threshold_lower) {
            return;
        }

        $service = new ChamberAutoControlService;

        if ($currentValue > $config->threshold_upper) {
            // 超过上限，启动降温/除湿等
            $service->manualControl($chamberId, $controlType, true);
        } elseif ($currentValue < $config->threshold_lower) {
            // 低于下限，启动加热/加湿等
            $service->manualControl($chamberId, $controlType, true);
        } else {
            // 在范围内，关闭
            $service->manualControl($chamberId, $controlType, false);
        }
    }

    protected function processScheduleControl(int $chamberId, string $controlType, $config): void
    {
        $now = now();
        $currentTime = $now->format('H:i:s');

        $schedule = \App\Models\ChamberSchedule::where('chamber_id', $chamberId)
            ->where('control_type', $controlType)
            ->where('is_enabled', true)
            ->where('start_time', '<=', $currentTime)
            ->where('end_time', '>=', $currentTime)
            ->first();

        if (! $schedule) {
            return;
        }

        $service = new ChamberAutoControlService;
        $service->manualControl($chamberId, $controlType, true);
    }

    protected function processCycleControl(int $chamberId, string $controlType, $config): void
    {
        if (! $config->cycle_run_duration || ! $config->cycle_stop_duration) {
            return;
        }

        // 简单的循环控制：根据当前时间判断运行还是停止
        $totalCycle = $config->cycle_run_duration + $config->cycle_stop_duration;
        $currentMinute = now()->minute;
        $positionInCycle = $currentMinute % $totalCycle;

        $service = new ChamberAutoControlService;
        $shouldRun = $positionInCycle < $config->cycle_run_duration;

        $service->manualControl($chamberId, $controlType, $shouldRun);
    }
}
