<?php

namespace App\Jobs;

use App\Models\ChamberControlLog;
use App\Models\ChamberManualControl;
use App\Services\MqttPublisher;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendMqttControlCommand implements ShouldQueue
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
        public array $actions,
        public ?int $overrideMinutes = null,
        public ?int $userId = null,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // 1. 发送 MQTT 命令
            $commandId = MqttPublisher::publishManualControl(
                $this->deviceCode,
                $this->actions,
                $this->overrideMinutes
            );

            // 2. MQTT 发送成功后，更新数据库
            $record = ChamberManualControl::where('chamber_id', $this->chamberId)
                ->latest('recorded_at')
                ->first();

            if ($record) {
                $updateData = array_merge($this->actions, [
                    'recorded_at' => now(),
                ]);
                $record->update($updateData);
            }

            // 3. 记录控制日志（成功）
            foreach ($this->actions as $device => $state) {
                ChamberControlLog::create([
                    'chamber_id' => $this->chamberId,
                    'control_type' => $device,
                    'trigger_type' => 'manual',
                    'action' => $state ? 'turn_on' : 'turn_off',
                    'trigger_reason' => '用户手动控制（队列异步执行）',
                    'command_id' => $commandId,
                    'ack_status' => 'pending',
                    'executed_at' => now(),
                    'executed_by' => $this->userId,
                ]);
            }

            Log::info('MQTT 控制命令发送成功', [
                'chamber_id' => $this->chamberId,
                'device_code' => $this->deviceCode,
                'command_id' => $commandId,
                'actions' => $this->actions,
            ]);

        } catch (\Exception $e) {
            Log::error('MQTT 控制命令发送失败', [
                'chamber_id' => $this->chamberId,
                'device_code' => $this->deviceCode,
                'actions' => $this->actions,
                'error' => $e->getMessage(),
                'attempt' => $this->attempts(),
            ]);

            // 记录失败日志
            foreach ($this->actions as $device => $state) {
                ChamberControlLog::create([
                    'chamber_id' => $this->chamberId,
                    'control_type' => $device,
                    'trigger_type' => 'manual',
                    'action' => $state ? 'turn_on' : 'turn_off',
                    'trigger_reason' => 'MQTT 发送失败: '.$e->getMessage(),
                    'ack_status' => 'failed',
                    'executed_at' => now(),
                    'executed_by' => $this->userId,
                ]);
            }

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
        Log::error('MQTT 控制命令最终失败（无重试次数）', [
            'chamber_id' => $this->chamberId,
            'device_code' => $this->deviceCode,
            'actions' => $this->actions,
            'error' => $exception->getMessage(),
        ]);
    }
}
