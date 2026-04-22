<?php

namespace App\Jobs;

use App\Models\ChamberControlLog;
use App\Services\MqttPublisher;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendMqttAutoConfig implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * 最大尝试次数
     */
    public $tries = 3;

    /**
     * 超时时间（秒）
     */
    public $timeout = 30;

    public function __construct(
        public int $chamberId,
        public string $deviceCode,
        public string $controlType,
        public array $config,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // 发送 MQTT 配置同步
            $configId = MqttPublisher::publishAutoConfig(
                $this->deviceCode,
                $this->controlType,
                $this->config
            );

            // 记录配置同步日志
            ChamberControlLog::create([
                'chamber_id' => $this->chamberId,
                'control_type' => $this->controlType,
                'trigger_type' => 'auto',
                'action' => 'config_sync',
                'trigger_reason' => '配置更新同步到边缘设备',
                'command_id' => $configId,
                'ack_status' => 'pending',
                'executed_at' => now(),
            ]);

            Log::info('MQTT 自动控制配置已同步到边缘设备', [
                'chamber_id' => $this->chamberId,
                'device_code' => $this->deviceCode,
                'control_type' => $this->controlType,
                'config_id' => $configId,
            ]);

        } catch (\Exception $e) {
            Log::error('MQTT 自动控制配置同步失败', [
                'chamber_id' => $this->chamberId,
                'device_code' => $this->deviceCode,
                'control_type' => $this->controlType,
                'error' => $e->getMessage(),
                'attempt' => $this->attempts(),
            ]);

            // 如果还有重试次数，抛出异常让队列重试
            if ($this->attempts() < $this->tries) {
                throw $e;
            }
        }
    }

    /**
     * 任务失败处理
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('MQTT 自动控制配置同步最终失败（无重试次数）', [
            'chamber_id' => $this->chamberId,
            'device_code' => $this->deviceCode,
            'control_type' => $this->controlType,
            'error' => $exception->getMessage(),
        ]);
    }
}
