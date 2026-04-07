<?php

namespace App\Console\Commands;

use App\Models\Chamber;
use App\Models\ChamberControlConfig;
use App\Models\ChamberControlState;
use Illuminate\Console\Command;

class InitAutoControlConfigs extends Command
{
    protected $signature = 'auto-control:init {--chamber= : 指定方舱ID} {--all : 初始化所有方舱}';

    protected $description = '初始化方舱自动控制配置';

    public function handle()
    {
        // 注意：数据库枚举值使用 'humidity' 而不是 'humidification'
        $controlTypes = ['temperature', 'humidity', 'fresh_air', 'exhaust', 'lighting'];

        if ($this->option('chamber')) {
            // 初始化指定方舱
            $chamber = Chamber::find($this->option('chamber'));
            if (! $chamber) {
                $this->error('方舱不存在');

                return 1;
            }

            $this->initChamber($chamber, $controlTypes);
            $this->info("方舱 [{$chamber->name}] 的自动控制配置已初始化");
        } elseif ($this->option('all')) {
            // 初始化所有方舱
            $chambers = Chamber::all();
            $bar = $this->output->createProgressBar($chambers->count());
            $bar->start();

            foreach ($chambers as $chamber) {
                $this->initChamber($chamber, $controlTypes);
                $bar->advance();
            }

            $bar->finish();
            $this->newLine();
            $this->info("共初始化 {$chambers->count()} 个方舱的自动控制配置");
        } else {
            $this->info('请使用以下选项之一：');
            $this->line('  --chamber=ID  指定方舱ID');
            $this->line('  --all         初始化所有方舱');
            $this->newLine();
            $this->warn('示例：php artisan auto-control:init --all');
        }

        return 0;
    }

    protected function initChamber($chamber, $controlTypes)
    {
        foreach ($controlTypes as $controlType) {
            ChamberControlConfig::getOrCreate($chamber->id, $controlType);
            ChamberControlState::getOrCreate($chamber->id, $controlType);
        }
    }
}
