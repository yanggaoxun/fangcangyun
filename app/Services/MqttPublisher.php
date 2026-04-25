<?php

namespace App\Services;

use PhpMqtt\Client\ConnectionSettings;
use PhpMqtt\Client\MqttClient;

class MqttPublisher
{
    private static ?MqttClient $client = null;

    public static function getClient(): MqttClient
    {
        if (self::$client === null || ! self::$client->isConnected()) {
            self::$client = self::createClient();
        }

        return self::$client;
    }

    protected static function createClient(): MqttClient
    {
        $config = config('mqtt');

        $connectionSettings = (new ConnectionSettings)
            ->setConnectTimeout($config['connect_timeout'])
            ->setSocketTimeout($config['socket_timeout'])
            ->setKeepAliveInterval($config['keep_alive'])
            ->setUsername($config['username'])
            ->setPassword($config['password']);

        $client = new MqttClient(
            $config['broker'],
            $config['port'],
            $config['client_id'].'_publisher'
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
     * 通用发布方法
     */
    public static function publish(string $topic, array $payload, int $qos = 1): void
    {
        $message = json_encode($payload);

        try {
            $client = self::getClient();
            $client->publish($topic, $message, $qos);
        } catch (\PhpMqtt\Client\Exceptions\DataTransferException $e) {
            // 连接可能已断开，重新创建连接并重试
            self::disconnect();
            self::$client = null;

            $client = self::getClient();
            $client->publish($topic, $message, $qos);
        }
    }

    /**
     * 断开连接
     */
    public static function disconnect(): void
    {
        if (self::$client !== null && self::$client->isConnected()) {
            self::$client->disconnect();
        }
    }
}
