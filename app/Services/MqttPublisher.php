<?php

namespace App\Services;

use PhpMqtt\Client\ConnectionSettings;
use PhpMqtt\Client\MqttClient;

class MqttPublisher
{
    /**
     * 创建新的 MQTT 客户端连接（短连接模式）
     */
    protected static function createClient(): MqttClient
    {
        $config = config('mqtt');

        $connectionSettings = (new ConnectionSettings)
            ->setConnectTimeout($config['connect_timeout'])
            ->setSocketTimeout($config['socket_timeout'])
            ->setKeepAliveInterval($config['keep_alive'])
            ->setUsername($config['username'])
            ->setPassword($config['password']);

        // 每次创建唯一 client_id，避免冲突
        $clientId = $config['client_id'].'_pub_'.uniqid();

        $client = new MqttClient(
            $config['broker'],
            $config['port'],
            $clientId
        );

        $client->connect($connectionSettings, true);

        return $client;
    }

    /**
     * 发布手动控制命令
     *
     * @param string $deviceCode 设备编码 (dev_devices.code)
     */
    public static function publishManualControl(string $deviceCode, array $actions, ?int $overrideMinutes = null): string
    {
        $commandId = 'cmd_'.uniqid();

        $payload = [
            'command_id' => $commandId,
            'timestamp' => now()->toIso8601String(),
            'actions' => $actions,
        ];

        if ($overrideMinutes !== null) {
            $payload['override_minutes'] = $overrideMinutes;
        }

        self::publish("chambers/{$deviceCode}/command/manual", $payload);

        return $commandId;
    }

    /**
     * 发布自动控制配置
     *
     * @param string $deviceCode 设备编码 (dev_devices.code)
     */
    public static function publishAutoConfig(string $deviceCode, string $controlType, array $config): string
    {
        $configId = 'cfg_'.uniqid();

        $payload = [
            'config_id' => $configId,
            'timestamp' => now()->toIso8601String(),
            'control_type' => $controlType,
            'config' => $config,
        ];

        self::publish("chambers/{$deviceCode}/config/auto", $payload);

        return $configId;
    }

    /**
     * 发布完整配置
     *
     * @param string $deviceCode 设备编码 (dev_devices.code)
     */
    public static function publishFullConfig(string $deviceCode, array $configs): string
    {
        $configId = 'cfg_'.uniqid();

        $payload = [
            'config_id' => $configId,
            'timestamp' => now()->toIso8601String(),
            'configs' => $configs,
        ];

        self::publish("chambers/{$deviceCode}/config/full", $payload);

        return $configId;
    }

    /**
     * 通用发布方法（短连接模式）
     * 每次发布新建连接，发送后立即断开，避免长连接状态问题
     */
    public static function publish(string $topic, array $payload, int $qos = 1): void
    {
        $message = json_encode($payload);
        $client = null;

        try {
            $client = self::createClient();
            $client->publish($topic, $message, $qos);
        } finally {
            // 确保连接被关闭，避免连接泄漏
            if ($client !== null && $client->isConnected()) {
                $client->disconnect();
            }
        }
    }
}
