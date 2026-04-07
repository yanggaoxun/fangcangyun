<?php

namespace App\Console\Commands;

use App\Services\ChamberAutoControlService;
use Illuminate\Console\Command;

class AutoControlRun extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'auto-control:run 
                            {--chamber= : 指定方舱ID}
                            {--debug : 调试模式，输出详细信息}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '执行方舱自动控制检查';

    /**
     * Execute the console command.
     */
    public function handle(ChamberAutoControlService $service): int
    {
        $this->info('开始执行自动控制检查...');

        try {
            if ($this->option('chamber')) {
                $chamberId = $this->option('chamber');
                $this->info("处理方舱ID: {$chamberId}");
                $service->processChamber($chamberId);
            } else {
                $this->info('处理所有方舱...');
                $service->processAllChambers();
            }

            $this->info('自动控制检查完成！');

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('执行出错: '.$e->getMessage());
            if ($this->option('debug')) {
                $this->error($e->getTraceAsString());
            }

            return Command::FAILURE;
        }
    }
}
