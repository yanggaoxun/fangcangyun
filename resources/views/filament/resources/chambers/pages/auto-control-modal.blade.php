<div class="p-5" style="min-width: 900px; max-height: 80vh; overflow-y: auto;" 
     x-data="{
        loading: true,
        saving: false,
        error: null,
        activeTab: 'temperature',
        chamberId: {{ $chamberId }},
        configs: {
            temperature: { 
                mode: 'auto_schedule', 
                is_enabled: false, 
                delay_cooling_heating: 0,
                delay_stop_cycle: 0,
                inner_cycle_run: 0,
                inner_cycle_stop: 0,
                schedules: [
                    { start_time: '06:00', end_time: '22:00', cooling_upper: 28.0, cooling_lower: 26.0, heating_upper: 18.0, heating_lower: 16.0, is_enabled: true }
                ],
                threshold_upper: null, 
                threshold_lower: null, 
                cycle_run_duration: null, 
                cycle_run_unit: 'minutes', 
                cycle_stop_duration: null, 
                cycle_stop_unit: 'minutes', 
                linkage_config: {} 
            },
            humidification: { mode: 'threshold', is_enabled: false, threshold_upper: null, threshold_lower: null, cycle_run_duration: null, cycle_run_unit: 'seconds', cycle_stop_duration: null, cycle_stop_unit: 'seconds', linkage_config: { link_inner_circulation: true, link_exhaust: false, link_fresh_air: false }, schedules: [{ start_time: '06:00', end_time: '22:00', humidity_upper: 85.0, humidity_lower: 70.0, is_enabled: true }] },
            fresh_air: { mode: 'threshold', is_enabled: false, threshold_upper: null, threshold_lower: null, cycle_run_duration: null, cycle_run_unit: 'seconds', cycle_stop_duration: null, cycle_stop_unit: 'seconds', linkage_config: { link_inner_circulation: false }, delay_start: 0, inner_delay_start: 0, schedules: [{ start_time: '06:00', end_time: '22:00', co2_upper: 1500.0, co2_lower: 800.0, is_enabled: true }] },
            exhaust: { mode: 'threshold', is_enabled: false, threshold_upper: null, threshold_lower: null, cycle_run_duration: null, cycle_run_unit: 'seconds', cycle_stop_duration: null, cycle_stop_unit: 'seconds', linkage_config: { link_fresh_air: false }, delay_start: 0, fresh_air_delay_start: 0, schedules: [{ start_time: '06:00', end_time: '22:00', co2_upper: 1500.0, co2_lower: 800.0, is_enabled: true }] },
            lighting: { mode: 'cycle', is_enabled: false, threshold_upper: null, threshold_lower: null, cycle_run_duration: null, cycle_run_unit: 'minutes', cycle_stop_duration: null, cycle_stop_unit: 'minutes', linkage_config: {}, schedules: [{ start_time: '06:00', end_time: '22:00', led_on_duration: 30, led_on_unit: 'minutes', led_off_duration: 30, led_off_unit: 'minutes', is_enabled: true }] }
        },
        
        addSchedule() {
            if (this.configs.temperature.schedules.length < 5) {
                this.configs.temperature.schedules.push({
                    start_time: '00:00',
                    end_time: '00:00',
                    cooling_upper: 28.0,
                    cooling_lower: 26.0,
                    heating_upper: 18.0,
                    heating_lower: 16.0,
                    is_enabled: false
                });
            }
        },
        
        removeSchedule(index) {
            if (this.configs.temperature.schedules.length > 1) {
                this.configs.temperature.schedules.splice(index, 1);
            }
        },
        
        toggleSchedule(selectedIndex) {
            const schedule = this.configs.temperature.schedules[selectedIndex];
            if (schedule.is_enabled) {
                return;
            }
            this.configs.temperature.schedules.forEach((s, index) => {
                s.is_enabled = (index === selectedIndex);
            });
        },
        
        addHumidificationSchedule() {
            if (this.configs.humidification.schedules.length < 5) {
                this.configs.humidification.schedules.push({
                    start_time: '00:00',
                    end_time: '00:00',
                    humidity_upper: 80.0,
                    humidity_lower: 60.0,
                    is_enabled: false
                });
            }
        },
        
        removeHumidificationSchedule(index) {
            if (this.configs.humidification.schedules.length > 1) {
                this.configs.humidification.schedules.splice(index, 1);
            }
        },
        
        toggleHumidificationSchedule(selectedIndex) {
            const schedule = this.configs.humidification.schedules[selectedIndex];
            if (schedule.is_enabled) {
                return;
            }
            this.configs.humidification.schedules.forEach((s, index) => {
                s.is_enabled = (index === selectedIndex);
            });
        },
        
        addFreshAirSchedule() {
            if (this.configs.fresh_air.schedules.length < 5) {
                this.configs.fresh_air.schedules.push({
                    start_time: '00:00',
                    end_time: '00:00',
                    co2_upper: 1500.0,
                    co2_lower: 800.0,
                    is_enabled: false
                });
            }
        },
        
        removeFreshAirSchedule(index) {
            if (this.configs.fresh_air.schedules.length > 1) {
                this.configs.fresh_air.schedules.splice(index, 1);
            }
        },
        
        toggleFreshAirSchedule(selectedIndex) {
            const schedule = this.configs.fresh_air.schedules[selectedIndex];
            if (schedule.is_enabled) {
                return;
            }
            this.configs.fresh_air.schedules.forEach((s, index) => {
                s.is_enabled = (index === selectedIndex);
            });
        },
        
        addExhaustSchedule() {
            if (this.configs.exhaust.schedules.length < 5) {
                this.configs.exhaust.schedules.push({
                    start_time: '00:00',
                    end_time: '00:00',
                    co2_upper: 1500.0,
                    co2_lower: 800.0,
                    is_enabled: false
                });
            }
        },
        
        removeExhaustSchedule(index) {
            if (this.configs.exhaust.schedules.length > 1) {
                this.configs.exhaust.schedules.splice(index, 1);
            }
        },
        
        toggleExhaustSchedule(selectedIndex) {
            const schedule = this.configs.exhaust.schedules[selectedIndex];
            if (schedule.is_enabled) {
                return;
            }
            this.configs.exhaust.schedules.forEach((s, index) => {
                s.is_enabled = (index === selectedIndex);
            });
        },

        addLightingSchedule() {
            if (this.configs.lighting.schedules.length < 5) {
                this.configs.lighting.schedules.push({
                    start_time: '00:00',
                    end_time: '00:00',
                    led_on_duration: 30,
                    led_on_unit: 'minutes',
                    led_off_duration: 30,
                    led_off_unit: 'minutes',
                    is_enabled: false
                });
            }
        },

        removeLightingSchedule(index) {
            if (this.configs.lighting.schedules.length > 1) {
                this.configs.lighting.schedules.splice(index, 1);
            }
        },

        toggleLightingSchedule(selectedIndex) {
            const schedule = this.configs.lighting.schedules[selectedIndex];
            if (schedule.is_enabled) {
                return;
            }
            this.configs.lighting.schedules.forEach((s, index) => {
                s.is_enabled = (index === selectedIndex);
            });
        },

        async init() {
            this.loading = true;
            this.error = null;
            try {
                const response = await fetch(`/api/auto-control/${this.chamberId}`);
                if (!response.ok) throw new Error('获取配置失败');
                const data = await response.json();
                if (data.configs) {
                    this.configs = { ...this.configs, ...data.configs };
                    
                    // 确保所有控制类型都有 schedules 字段
                    const controlTypes = ['temperature', 'humidification', 'fresh_air', 'exhaust', 'lighting'];
                    controlTypes.forEach(type => {
                        if (this.configs[type] && !this.configs[type].schedules) {
                            this.configs[type].schedules = [{ 
                                start_time: '06:00', 
                                end_time: '22:00', 
                                is_enabled: true 
                            }];
                        }
                    });
                    
                    // 温度控制强制使用 auto_schedule 模式
                    if (this.configs.temperature) {
                        this.configs.temperature.mode = 'auto_schedule';
                    }

                    if (this.configs.temperature && this.configs.temperature.schedules) {
                        const enabledSchedules = this.configs.temperature.schedules.filter(s => s.is_enabled);
                        if (enabledSchedules.length > 1) {
                            let foundEnabled = false;
                            this.configs.temperature.schedules.forEach(schedule => {
                                if (schedule.is_enabled && !foundEnabled) {
                                    foundEnabled = true;
                                } else {
                                    schedule.is_enabled = false;
                                }
                            });
                        } else if (enabledSchedules.length === 0 && this.configs.temperature.schedules.length > 0) {
                            this.configs.temperature.schedules[0].is_enabled = true;
                        }
                    }
                }
            } catch (err) {
                this.error = err.message;
                console.error('加载配置失败:', err);
            } finally {
                this.loading = false;
            }
        },
        
        async saveConfig(controlType) {
            this.saving = true;
            this.error = null;
            try {
                let configToSave = { ...this.configs[controlType] };
                
                delete configToSave.current_state;
                delete configToSave.current_mode;
                delete configToSave.is_manual_override;
                
                if (configToSave.threshold_upper !== null && configToSave.threshold_upper !== '') {
                    configToSave.threshold_upper = parseFloat(configToSave.threshold_upper);
                } else {
                    configToSave.threshold_upper = null;
                }
                if (configToSave.threshold_lower !== null && configToSave.threshold_lower !== '') {
                    configToSave.threshold_lower = parseFloat(configToSave.threshold_lower);
                } else {
                    configToSave.threshold_lower = null;
                }
                
                if (configToSave.schedules && Array.isArray(configToSave.schedules)) {
                    configToSave.schedules = configToSave.schedules.map(schedule => ({
                        start_time: schedule.start_time,
                        end_time: schedule.end_time,
                        is_enabled: schedule.is_enabled,
                        cooling_upper: schedule.cooling_upper !== null && schedule.cooling_upper !== '' ? parseFloat(schedule.cooling_upper) : null,
                        cooling_lower: schedule.cooling_lower !== null && schedule.cooling_lower !== '' ? parseFloat(schedule.cooling_lower) : null,
                        heating_upper: schedule.heating_upper !== null && schedule.heating_upper !== '' ? parseFloat(schedule.heating_upper) : null,
                        heating_lower: schedule.heating_lower !== null && schedule.heating_lower !== '' ? parseFloat(schedule.heating_lower) : null,
                        humidity_upper: schedule.humidity_upper !== null && schedule.humidity_upper !== '' ? parseFloat(schedule.humidity_upper) : null,
                        humidity_lower: schedule.humidity_lower !== null && schedule.humidity_lower !== '' ? parseFloat(schedule.humidity_lower) : null,
                        co2_upper: schedule.co2_upper !== null && schedule.co2_upper !== '' ? parseFloat(schedule.co2_upper) : null,
                        co2_lower: schedule.co2_lower !== null && schedule.co2_lower !== '' ? parseFloat(schedule.co2_lower) : null,
                        led_on_duration: schedule.led_on_duration !== null && schedule.led_on_duration !== '' ? parseInt(schedule.led_on_duration) : 0,
                        led_on_unit: schedule.led_on_unit || 'minutes',
                        led_off_duration: schedule.led_off_duration !== null && schedule.led_off_duration !== '' ? parseInt(schedule.led_off_duration) : 0,
                        led_off_unit: schedule.led_off_unit || 'minutes',
                    }));
                }
                
                console.log('Saving config:', controlType, configToSave);
                
                const response = await fetch(`/api/auto-control/${this.chamberId}/${controlType}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                    },
                    body: JSON.stringify(configToSave)
                });
                
                const data = await response.json();
                console.log('Response:', data);
                
                if (!response.ok) {
                    const error = new Error(data.error || data.message || '保存配置失败');
                    error.messages = data.messages;
                    throw error;
                }
                
                // 使用 Filament 原生通知
                new FilamentNotification()
                    .title('操作成功')
                    .success()
                    .body('配置已保存')
                    .send();
                
            } catch (err) {
                this.error = err.message;
                let errorMsg = err.message;
                if (err.messages) {
                    errorMsg = Object.entries(err.messages).map(([field, msgs]) => {
                        return `${field}: ${msgs.join(', ')}`;
                    }).join('; ');
                }
                
                // 使用 Filament 原生通知
                new FilamentNotification()
                    .title('操作失败')
                    .danger()
                    .body(errorMsg)
                    .send();
                console.error('保存配置失败:', err);
            } finally {
                this.saving = false;
            }
        }
     }"
     x-init="init()">
    
    {{-- Loading State --}}
    <div x-show="loading" style="text-align: center; padding: 40px;">
        <div style="display: inline-block; width: 40px; height: 40px; border: 3px solid #f3f4f6; border-top-color: #3b82f6; border-radius: 50%; animation: spin 1s linear infinite;"></div>
        <p style="margin-top: 16px; color: #6b7280;">加载配置中...</p>
    </div>
    
    {{-- Error State --}}
    <div x-show="error && !loading" style="background: #fef2f2; border: 1px solid #fecaca; border-radius: 8px; padding: 16px; margin-bottom: 16px; color: #dc2626;">
        <p style="margin: 0;" x-text="error"></p>
        <button @click="init()" style="margin-top: 8px; padding: 6px 12px; background: #dc2626; color: white; border: none; border-radius: 4px; cursor: pointer;">重试</button>
    </div>
    
    {{-- Main Content --}}
    <div x-show="!loading">
        {{-- Tab Navigation --}}
        <div style="margin-bottom: 20px;">
            <div style="display: flex; gap: 8px;">
                <template x-for="tab in [
                    { key: 'temperature', label: '温度控制', icon: '🌡️', color: '#f97316' },
                    { key: 'humidification', label: '加湿控制', icon: '💧', color: '#3b82f6' },
                    { key: 'fresh_air', label: '新风控制', icon: '🌬️', color: '#22c55e' },
                    { key: 'exhaust', label: '排风控制', icon: '💨', color: '#a855f7' },
                    { key: 'lighting', label: '光照控制', icon: '☀️', color: '#f59e0b' }
                ]" :key="tab.key">
                    <div 
                        @click="activeTab = tab.key"
                        class="control-tab-compact"
                        :class="{ 'active': activeTab === tab.key }"
                        :style="activeTab === tab.key 
                            ? `--tab-color: ${tab.color}; --tab-border: ${tab.color}; --tab-bg: ${tab.color}10;` 
                            : `--tab-color: #64748b; --tab-border: #e2e8f0; --tab-bg: #ffffff;`">
                        
                        <div class="tab-icon-compact" 
                             :style="activeTab === tab.key 
                                ? `background: ${tab.color}; color: white;` 
                                : `background: #f1f5f9; color: #64748b;`"
                             x-text="tab.icon">
                        </div>
                        
                        <span class="tab-label-compact" x-text="tab.label"></span>
                        
                        <span class="tab-status-dot" 
                              :class="configs[tab.key].is_enabled ? 'status-on' : 'status-off'">
                        </span>
                    </div>
                </template>
            </div>
        </div>

        <style>
            .control-tab-compact {
                flex: 1;
                display: flex;
                flex-direction: row;
                align-items: center;
                gap: 8px;
                padding: 10px 14px;
                border-radius: 10px;
                cursor: pointer;
                background: var(--tab-bg);
                border: 2px solid var(--tab-border);
                transition: all 0.2s ease;
                min-width: 0;
            }
            
            .control-tab-compact:hover {
                border-color: var(--tab-color);
                background: color-mix(in srgb, var(--tab-color) 5%, white);
            }
            
            .control-tab-compact.active {
                background: color-mix(in srgb, var(--tab-color) 10%, white);
                border-color: var(--tab-color);
                box-shadow: 0 2px 8px color-mix(in srgb, var(--tab-color) 20%, transparent);
            }
            
            .control-tab-compact.active .tab-label-compact {
                color: var(--tab-color);
                font-weight: 700;
            }
            
            .tab-icon-compact {
                width: 28px;
                height: 28px;
                border-radius: 6px;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 14px;
                flex-shrink: 0;
            }
            
            .tab-label-compact {
                font-size: 13px;
                font-weight: 600;
                color: #475569;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
                flex: 1;
            }
            
            .tab-status-dot {
                width: 8px;
                height: 8px;
                border-radius: 50%;
                flex-shrink: 0;
            }
            
            .tab-status-dot.status-on {
                background: #22c55e;
                box-shadow: 0 0 4px #22c55e;
            }
            
            .tab-status-dot.status-off {
                background: #cbd5e1;
            }
        </style>
        
        {{-- Temperature Control Panel --}}
        <div x-show="activeTab === 'temperature'" style="background: white; border-radius: 12px; padding: 24px; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);">
            {{-- Main switch control --}}
            <div style="margin-bottom: 24px; padding: 16px 20px; background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%); border-radius: 10px; border: 2px solid #fcd34d;">
                <div style="display: flex; align-items: center; justify-content: space-between;">
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <div style="width: 40px; height: 40px; background: white; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">🌡️</div>
                        <div>
                            <div style="font-size: 15px; font-weight: 700; color: #374151;">温度自动控制</div>
                            <div style="font-size: 12px; color: #6b7280; margin-top: 2px;">
                                <span x-show="configs.temperature.is_enabled" style="color: #16a34a; font-weight: 600;">✓ 当前已启用自动控制</span>
                                <span x-show="!configs.temperature.is_enabled">✗ 当前未启用自动控制</span>
                            </div>
                        </div>
                    </div>
                    <label class="toggle-switch-large" style="cursor: pointer;">
                        <input type="checkbox" x-model="configs.temperature.is_enabled" style="display: none;">
                        <div class="toggle-slider" :class="configs.temperature.is_enabled ? 'on' : 'off'">
                            <span class="toggle-label" x-text="configs.temperature.is_enabled ? 'ON' : 'OFF'"></span>
                            <div class="toggle-thumb"></div>
                        </div>
                    </label>
                </div>
            </div>
            
            <div x-show="configs.temperature.is_enabled">
                {{-- 循环与延时配置 --}}
                <div style="margin-bottom: 24px; padding: 16px; background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%); border-radius: 10px; border: 2px solid #7dd3fc;">
                    <div style="font-size: 14px; font-weight: 600; color: #0369a1; margin-bottom: 12px;">⏱️ 循环与延时配置</div>
                    <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px;">
                        <div>
                            <label style="display: block; font-size: 12px; color: #0369a1; font-weight: 600; margin-bottom: 6px;">延时制冷/加热 (秒)</label>
                            <input type="number" x-model="configs.temperature.delay_cooling_heating" min="0" placeholder="秒" 
                                   style="width: 100%; padding: 8px 12px; border: 2px solid #7dd3fc; border-radius: 6px; font-size: 14px; background: white;">
                        </div>
                        <div>
                            <label style="display: block; font-size: 12px; color: #0369a1; font-weight: 600; margin-bottom: 6px;">延时停止循环 (秒)</label>
                            <input type="number" x-model="configs.temperature.delay_stop_cycle" min="0" placeholder="秒" 
                                   style="width: 100%; padding: 8px 12px; border: 2px solid #7dd3fc; border-radius: 6px; font-size: 14px; background: white;">
                        </div>
                        <div>
                            <label style="display: block; font-size: 12px; color: #0369a1; font-weight: 600; margin-bottom: 6px;">内循环运行 (分钟)</label>
                            <input type="number" x-model="configs.temperature.inner_cycle_run" min="0" placeholder="分钟" 
                                   style="width: 100%; padding: 8px 12px; border: 2px solid #7dd3fc; border-radius: 6px; font-size: 14px; background: white;">
                        </div>
                        <div>
                            <label style="display: block; font-size: 12px; color: #0369a1; font-weight: 600; margin-bottom: 6px;">内循环停止 (分钟)</label>
                            <input type="number" x-model="configs.temperature.inner_cycle_stop" min="0" placeholder="分钟" 
                                   style="width: 100%; padding: 8px 12px; border: 2px solid #7dd3fc; border-radius: 6px; font-size: 14px; background: white;">
                        </div>
                    </div>
                </div>

                {{-- 时段配置 --}}
                <div style="margin-bottom: 20px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                        <label style="font-size: 14px; font-weight: 500; color: #374151;">时段配置 (最多5个)</label>
                        <button 
                            type="button"
                            @click="addSchedule()"
                            :disabled="configs.temperature.schedules.length >= 5"
                            class="btn-add-schedule">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 16px; height: 16px;">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                            </svg>
                            添加时段
                        </button>
                    </div>
                    
                    <template x-for="(schedule, index) in configs.temperature.schedules" :key="index">
                        <div style="background: #f9fafb; border-radius: 8px; padding: 16px; margin-bottom: 12px; border: 2px solid; transition: all 0.3s ease;"
                             :style="schedule.is_enabled ? 'border-color: #22c55e; box-shadow: 0 0 0 3px rgba(34, 197, 94, 0.1);' : 'border-color: #e5e7eb;'">
                            <div style="display: flex; gap: 8px; align-items: flex-end; background: #f8fafc; border-radius: 8px; padding: 12px; border: 1px solid #e2e8f0;">
                                {{-- 启用开关 --}}
                                <div style="flex: 0 0 auto;">
                                    <div class="schedule-toggle" 
                                         @click="toggleSchedule(index)"
                                         :class="schedule.is_enabled ? 'active' : ''">
                                        <div class="schedule-toggle-slider" :class="schedule.is_enabled ? 'on' : 'off'">
                                            <span x-text="schedule.is_enabled ? 'ON' : 'OFF'"></span>
                                        </div>
                                    </div>
                                </div>
                                
                                {{-- 开始时间 --}}
                                <div style="flex: 1; min-width: 0;">
                                    <label style="display: block; font-size: 11px; color: #1e40af; font-weight: 600; margin-bottom: 4px;">开始时间</label>
                                    <input type="time" x-model="schedule.start_time" style="width: 100%; padding: 6px; border: 1px solid #93c5fd; border-radius: 4px; font-size: 13px;">
                                </div>
                                
                                {{-- 结束时间 --}}
                                <div style="flex: 1; min-width: 0;">
                                    <label style="display: block; font-size: 11px; color: #1e40af; font-weight: 600; margin-bottom: 4px;">结束时间</label>
                                    <input type="time" x-model="schedule.end_time" style="width: 100%; padding: 6px; border: 1px solid #93c5fd; border-radius: 4px; font-size: 13px;">
                                </div>
                                
                                {{-- 制冷上限 --}}
                                <div style="flex: 1; min-width: 0;">
                                    <label style="display: block; font-size: 11px; color: #92400e; font-weight: 600; margin-bottom: 4px;">制冷上限(°C)</label>
                                    <input type="number" step="0.1" x-model="schedule.cooling_upper" style="width: 100%; padding: 6px; border: 1px solid #fcd34d; border-radius: 4px; font-size: 13px;">
                                </div>
                                
                                {{-- 制冷下限 --}}
                                <div style="flex: 1; min-width: 0;">
                                    <label style="display: block; font-size: 11px; color: #92400e; font-weight: 600; margin-bottom: 4px;">制冷下限(°C)</label>
                                    <input type="number" step="0.1" x-model="schedule.cooling_lower" style="width: 100%; padding: 6px; border: 1px solid #fcd34d; border-radius: 4px; font-size: 13px;">
                                </div>
                                
                                {{-- 加热上限 --}}
                                <div style="flex: 1; min-width: 0;">
                                    <label style="display: block; font-size: 11px; color: #dc2626; font-weight: 600; margin-bottom: 4px;">加热上限(°C)</label>
                                    <input type="number" step="0.1" x-model="schedule.heating_upper" style="width: 100%; padding: 6px; border: 1px solid #fca5a5; border-radius: 4px; font-size: 13px;">
                                </div>
                                
                                {{-- 加热下限 --}}
                                <div style="flex: 1; min-width: 0;">
                                    <label style="display: block; font-size: 11px; color: #dc2626; font-weight: 600; margin-bottom: 4px;">加热下限(°C)</label>
                                    <input type="number" step="0.1" x-model="schedule.heating_lower" style="width: 100%; padding: 6px; border: 1px solid #fca5a5; border-radius: 4px; font-size: 13px;">
                                </div>
                                
                                {{-- 删除按钮 --}}
                                <div style="flex: 0 0 auto;">
                                    <button 
                                        type="button"
                                        @click="removeSchedule(index)"
                                        :disabled="configs.temperature.schedules.length <= 1"
                                        class="btn-delete-schedule">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width: 16px; height: 16px;">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
            
            {{-- Save Button for Temperature --}}
            <div style="display: flex; justify-content: flex-end; margin-top: 20px; padding-top: 20px; border-top: 1px solid #e5e7eb;">
                <button 
                    @click="saveConfig('temperature')"
                    :disabled="saving"
                    class="btn-save-config btn-save-temperature">
                    <svg x-show="saving" class="animate-spin" style="width: 16px; height: 16px;" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle style="opacity: 0.25;" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path style="opacity: 0.75;" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <svg x-show="!saving" style="width: 18px; height: 18px;" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span x-text="saving ? '保存中...' : '保存配置'"></span>
                </button>
            </div>
        </div>
        
        {{-- Humidification Control Panel --}}
        <div x-show="activeTab === 'humidification'" style="background: white; border-radius: 12px; padding: 24px; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);">
            {{-- Main switch control --}}
            <div style="margin-bottom: 24px; padding: 16px 20px; background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%); border-radius: 10px; border: 2px solid #60a5fa;">
                <div style="display: flex; align-items: center; justify-content: space-between;">
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <div style="width: 40px; height: 40px; background: white; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">💧</div>
                        <div>
                            <div style="font-size: 15px; font-weight: 700; color: #374151;">加湿自动控制</div>
                            <div style="font-size: 12px; color: #6b7280; margin-top: 2px;">
                                <span x-show="configs.humidification.is_enabled" style="color: #16a34a; font-weight: 600;">✓ 当前已启用自动控制</span>
                                <span x-show="!configs.humidification.is_enabled">✗ 当前未启用自动控制</span>
                            </div>
                        </div>
                    </div>
                    <label class="toggle-switch-large" style="cursor: pointer;">
                        <input type="checkbox" x-model="configs.humidification.is_enabled" style="display: none;">
                        <div class="toggle-slider" :class="configs.humidification.is_enabled ? 'on' : 'off'">
                            <span class="toggle-label" x-text="configs.humidification.is_enabled ? 'ON' : 'OFF'"></span>
                            <div class="toggle-thumb"></div>
                        </div>
                    </label>
                </div>
            </div>
            
            <div x-show="configs.humidification.is_enabled">
                {{-- 内循环联动开关和控制模式在同一行 --}}
                <div style="margin-bottom: 20px; display: flex; align-items: center; gap: 24px;">
                    {{-- 内循环联动 --}}
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <span style="font-size: 14px; font-weight: 500; color: #374151; white-space: nowrap;">🔄 内循环联动</span>
                        <label class="toggle-switch-small" style="cursor: pointer;">
                            <input type="checkbox" x-model="configs.humidification.linkage_config.link_inner_circulation" style="display: none;">
                            <div class="toggle-slider-small" :class="configs.humidification.linkage_config.link_inner_circulation ? 'on' : 'off'">
                                <span class="toggle-label-small" x-text="configs.humidification.linkage_config.link_inner_circulation ? 'ON' : 'OFF'"></span>
                                <div class="toggle-thumb-small"></div>
                            </div>
                        </label>
                    </div>
                    
                    {{-- 控制模式 --}}
                    <div style="display: flex; align-items: center; gap: 8px; flex: 1;">
                        <span style="font-size: 14px; font-weight: 500; color: #374151; white-space: nowrap;">控制模式</span>
                        <select x-model="configs.humidification.mode" style="flex: 1; padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px;">
                            <option value="threshold">阈值控制</option>
                            <option value="cycle">循环控制</option>
                            <option value="schedule">定时控制</option>
                        </select>
                    </div>
                </div>
                
                <div x-show="configs.humidification.mode === 'threshold'" style="margin-bottom: 20px;">
                    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px;">
                        <div>
                            <label style="display: block; font-size: 14px; font-weight: 500; color: #374151; margin-bottom: 8px;">上限阈值</label>
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <input type="number" step="0.1" x-model="configs.humidification.threshold_upper" style="flex: 1; padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 6px;">
                                <span style="font-size: 14px; color: #6b7280; font-weight: 500;">%</span>
                            </div>
                        </div>
                        <div>
                            <label style="display: block; font-size: 14px; font-weight: 500; color: #374151; margin-bottom: 8px;">下限阈值</label>
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <input type="number" step="0.1" x-model="configs.humidification.threshold_lower" style="flex: 1; padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 6px;">
                                <span style="font-size: 14px; color: #6b7280; font-weight: 500;">%</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div x-show="configs.humidification.mode === 'cycle'" style="margin-bottom: 20px;">
                    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px;">
                        <div>
                            <label style="display: block; font-size: 14px; font-weight: 500; color: #374151; margin-bottom: 8px;">加湿运行</label>
                            <div style="display: flex; gap: 8px;">
                                <input type="number" x-model="configs.humidification.cycle_run_duration" style="flex: 1; padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 6px;">
                                <select x-model="configs.humidification.cycle_run_unit" style="padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 6px;">
                                    <option value="seconds">秒</option>
                                    <option value="minutes">分钟</option>
                                </select>
                            </div>
                        </div>
                        <div>
                            <label style="display: block; font-size: 14px; font-weight: 500; color: #374151; margin-bottom: 8px;">加湿停止</label>
                            <div style="display: flex; gap: 8px;">
                                <input type="number" x-model="configs.humidification.cycle_stop_duration" style="flex: 1; padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 6px;">
                                <select x-model="configs.humidification.cycle_stop_unit" style="padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 6px;">
                                    <option value="seconds">秒</option>
                                    <option value="minutes">分钟</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                
                {{-- 加湿时段配置 --}}
                <div x-show="configs.humidification.mode === 'schedule'" style="margin-bottom: 20px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                        <label style="font-size: 14px; font-weight: 500; color: #374151;">时段配置 (最多5个)</label>
                        <button 
                            type="button"
                            @click="addHumidificationSchedule()"
                            :disabled="configs.humidification.schedules.length >= 5"
                            class="btn-add-schedule">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 16px; height: 16px;">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                            </svg>
                            添加时段
                        </button>
                    </div>
                    
                    <template x-for="(schedule, index) in configs.humidification.schedules" :key="index">
                        <div style="background: #f9fafb; border-radius: 8px; padding: 16px; margin-bottom: 12px; border: 2px solid; transition: all 0.3s ease;"
                             :style="schedule.is_enabled ? 'border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);' : 'border-color: #e5e7eb;'">
                            <div style="display: flex; gap: 8px; align-items: flex-end; background: #f8fafc; border-radius: 8px; padding: 12px; border: 1px solid #e2e8f0;">
                                {{-- 启用开关 --}}
                                <div style="flex: 0 0 auto;">
                                    <div class="schedule-toggle" 
                                         @click="toggleHumidificationSchedule(index)"
                                         :class="schedule.is_enabled ? 'active' : ''">
                                        <div class="schedule-toggle-slider" :class="schedule.is_enabled ? 'on' : 'off'">
                                            <span x-text="schedule.is_enabled ? 'ON' : 'OFF'"></span>
                                        </div>
                                    </div>
                                </div>
                                
                                {{-- 开始时间 --}}
                                <div style="flex: 1; min-width: 0;">
                                    <label style="display: block; font-size: 11px; color: #1e40af; font-weight: 600; margin-bottom: 4px;">开始时间</label>
                                    <input type="time" x-model="schedule.start_time" style="width: 100%; padding: 6px; border: 1px solid #93c5fd; border-radius: 4px; font-size: 13px;">
                                </div>
                                
                                {{-- 结束时间 --}}
                                <div style="flex: 1; min-width: 0;">
                                    <label style="display: block; font-size: 11px; color: #1e40af; font-weight: 600; margin-bottom: 4px;">结束时间</label>
                                    <input type="time" x-model="schedule.end_time" style="width: 100%; padding: 6px; border: 1px solid #93c5fd; border-radius: 4px; font-size: 13px;">
                                </div>
                                
                                {{-- 湿度上限 --}}
                                <div style="flex: 1; min-width: 0;">
                                    <label style="display: block; font-size: 11px; color: #2563eb; font-weight: 600; margin-bottom: 4px;">湿度上限(%)</label>
                                    <input type="number" step="0.1" x-model="schedule.humidity_upper" style="width: 100%; padding: 6px; border: 1px solid #60a5fa; border-radius: 4px; font-size: 13px;">
                                </div>
                                
                                {{-- 湿度下限 --}}
                                <div style="flex: 1; min-width: 0;">
                                    <label style="display: block; font-size: 11px; color: #2563eb; font-weight: 600; margin-bottom: 4px;">湿度下限(%)</label>
                                    <input type="number" step="0.1" x-model="schedule.humidity_lower" style="width: 100%; padding: 6px; border: 1px solid #60a5fa; border-radius: 4px; font-size: 13px;">
                                </div>
                                
                                {{-- 删除按钮 --}}
                                <div style="flex: 0 0 auto;">
                                    <button 
                                        type="button"
                                        @click="removeHumidificationSchedule(index)"
                                        :disabled="configs.humidification.schedules.length <= 1"
                                        class="btn-delete-schedule">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width: 16px; height: 16px;">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
            
            {{-- Save Button --}}
            <div style="display: flex; justify-content: flex-end; margin-top: 20px; padding-top: 20px; border-top: 1px solid #e5e7eb;">
                <button 
                    @click="saveConfig('humidification')"
                    :disabled="saving"
                    class="btn-save-config btn-save-other">
                    <svg x-show="saving" class="animate-spin" style="width: 16px; height: 16px;" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle style="opacity: 0.25;" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path style="opacity: 0.75;" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <svg x-show="!saving" style="width: 18px; height: 18px;" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span x-text="saving ? '保存中...' : '保存配置'"></span>
                </button>
            </div>
        </div>
        
        {{-- Fresh Air Control Panel --}}
        <div x-show="activeTab === 'fresh_air'" style="background: white; border-radius: 12px; padding: 24px; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);">
            {{-- Main switch control --}}
            <div style="margin-bottom: 24px; padding: 16px 20px; background: linear-gradient(135deg, #dcfce7 0%, #bbf7d0 100%); border-radius: 10px; border: 2px solid #4ade80;">
                <div style="display: flex; align-items: center; justify-content: space-between;">
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <div style="width: 40px; height: 40px; background: white; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">🌬️</div>
                        <div>
                            <div style="font-size: 15px; font-weight: 700; color: #374151;">新风自动控制</div>
                            <div style="font-size: 12px; color: #6b7280; margin-top: 2px;">
                                <span x-show="configs.fresh_air.is_enabled" style="color: #16a34a; font-weight: 600;">✓ 当前已启用自动控制</span>
                                <span x-show="!configs.fresh_air.is_enabled">✗ 当前未启用自动控制</span>
                            </div>
                        </div>
                    </div>
                    <label class="toggle-switch-large" style="cursor: pointer;">
                        <input type="checkbox" x-model="configs.fresh_air.is_enabled" style="display: none;">
                        <div class="toggle-slider" :class="configs.fresh_air.is_enabled ? 'on' : 'off'">
                            <span class="toggle-label" x-text="configs.fresh_air.is_enabled ? 'ON' : 'OFF'"></span>
                            <div class="toggle-thumb"></div>
                        </div>
                    </label>
                </div>
            </div>
            
            <div x-show="configs.fresh_air.is_enabled">
                {{-- 内循环联动、延时启动、控制模式在同一行 --}}
                <div style="margin-bottom: 20px; display: flex; align-items: center; gap: 16px;">
                    {{-- 内循环联动 --}}
                    <div style="display: flex; align-items: center; gap: 8px; flex-shrink: 0;">
                        <span style="font-size: 14px; font-weight: 500; color: #374151; white-space: nowrap;">🔄 内循环联动</span>
                        <label class="toggle-switch-small" style="cursor: pointer;">
                            <input type="checkbox" x-model="configs.fresh_air.linkage_config.link_inner_circulation" style="display: none;">
                            <div class="toggle-slider-small" :class="configs.fresh_air.linkage_config.link_inner_circulation ? 'on' : 'off'">
                                <span class="toggle-label-small" x-text="configs.fresh_air.linkage_config.link_inner_circulation ? 'ON' : 'OFF'"></span>
                                <div class="toggle-thumb-small"></div>
                            </div>
                        </label>
                    </div>
                    
                    {{-- 新风延迟启动 --}}
                    <div style="display: flex; align-items: center; gap: 6px; flex-shrink: 0;">
                        <span style="font-size: 13px; color: #0369a1; font-weight: 600; white-space: nowrap;">新风延迟启动</span>
                        <input type="number" x-model="configs.fresh_air.delay_start" min="0" placeholder="0" 
                               :disabled="!configs.fresh_air.linkage_config.link_inner_circulation"
                               style="width: 50px; padding: 6px 2px; border: 2px solid #0ea5e9; border-bottom: 1px solid; border-radius: 4px; font-size: 13px; background: white; text-align: center; font-weight: 600; box-sizing: border-box;"
                               :style="!configs.fresh_air.linkage_config.link_inner_circulation ? 'background: #e5e7eb; border-color: #9ca3af; color: #9ca3af; cursor: not-allowed;' : ''">
                        <span style="font-size: 12px; color: #6b7280; font-weight: 500;">秒</span>
                    </div>
                    
                    {{-- 内循环延时启动 --}}
                    <div style="display: flex; align-items: center; gap: 6px; flex-shrink: 0;">
                        <span style="font-size: 13px; color: #0369a1; font-weight: 600; white-space: nowrap;">内循环延时启动</span>
                        <input type="number" x-model="configs.fresh_air.inner_delay_start" min="0" placeholder="0" 
                               :disabled="!configs.fresh_air.linkage_config.link_inner_circulation"
                               style="width: 50px; padding: 6px 2px; border: 2px solid #0ea5e9; border-bottom: 1px solid; border-radius: 4px; font-size: 13px; background: white; text-align: center; font-weight: 600; box-sizing: border-box;"
                               :style="!configs.fresh_air.linkage_config.link_inner_circulation ? 'background: #e5e7eb; border-color: #9ca3af; color: #9ca3af; cursor: not-allowed;' : ''">
                        <span style="font-size: 12px; color: #6b7280; font-weight: 500;">秒</span>
                    </div>
                    
                    {{-- 控制模式 --}}
                    <div style="display: flex; align-items: center; gap: 8px; flex: 1; min-width: 0;">
                        <span style="font-size: 14px; font-weight: 500; color: #374151; white-space: nowrap;">控制模式</span>
                        <select x-model="configs.fresh_air.mode" style="flex: 1; padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px;">
                            <option value="threshold">阈值控制</option>
                            <option value="cycle">循环控制</option>
                            <option value="schedule">定时控制</option>
                        </select>
                    </div>
                </div>
                
                <div x-show="configs.fresh_air.mode === 'threshold'" style="margin-bottom: 20px;">
                    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px;">
                        <div>
                            <label style="display: block; font-size: 14px; font-weight: 500; color: #374151; margin-bottom: 8px;">CO2上限</label>
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <input type="number" step="0.1" x-model="configs.fresh_air.threshold_upper" style="flex: 1; padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 6px;">
                                <span style="font-size: 14px; color: #6b7280; font-weight: 500;">PPM</span>
                            </div>
                        </div>
                        <div>
                            <label style="display: block; font-size: 14px; font-weight: 500; color: #374151; margin-bottom: 8px;">CO2下限</label>
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <input type="number" step="0.1" x-model="configs.fresh_air.threshold_lower" style="flex: 1; padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 6px;">
                                <span style="font-size: 14px; color: #6b7280; font-weight: 500;">PPM</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div x-show="configs.fresh_air.mode === 'cycle'" style="margin-bottom: 20px;">
                    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px;">
                        <div>
                            <label style="display: block; font-size: 14px; font-weight: 500; color: #374151; margin-bottom: 8px;">运行时长</label>
                            <div style="display: flex; gap: 8px;">
                                <input type="number" x-model="configs.fresh_air.cycle_run_duration" style="flex: 1; padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 6px;">
                                <select x-model="configs.fresh_air.cycle_run_unit" style="padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 6px;">
                                    <option value="minutes">分钟</option>
                                    <option value="seconds">秒</option>
                                </select>
                            </div>
                        </div>
                        <div>
                            <label style="display: block; font-size: 14px; font-weight: 500; color: #374151; margin-bottom: 8px;">停止时长</label>
                            <div style="display: flex; gap: 8px;">
                                <input type="number" x-model="configs.fresh_air.cycle_stop_duration" style="flex: 1; padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 6px;">
                                <select x-model="configs.fresh_air.cycle_stop_unit" style="padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 6px;">
                                    <option value="minutes">分钟</option>
                                    <option value="seconds">秒</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- 新风时段配置 --}}
                <div x-show="configs.fresh_air.mode === 'schedule'" style="margin-bottom: 20px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                        <label style="font-size: 14px; font-weight: 500; color: #374151;">时段配置 (最多5个)</label>
                        <button 
                            type="button"
                            @click="addFreshAirSchedule()"
                            :disabled="configs.fresh_air.schedules.length >= 5"
                            class="btn-add-schedule">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 16px; height: 16px;">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                            </svg>
                            添加时段
                        </button>
                    </div>
                    
                    <template x-for="(schedule, index) in configs.fresh_air.schedules" :key="index">
                        <div style="background: #f9fafb; border-radius: 8px; padding: 16px; margin-bottom: 12px; border: 2px solid; transition: all 0.3s ease;"
                             :style="schedule.is_enabled ? 'border-color: #22c55e; box-shadow: 0 0 0 3px rgba(34, 197, 94, 0.1);' : 'border-color: #e5e7eb;'">
                            <div style="display: flex; gap: 8px; align-items: flex-end; background: #f8fafc; border-radius: 8px; padding: 12px; border: 1px solid #e2e8f0;">
                                {{-- 启用开关 --}}
                                <div style="flex: 0 0 auto;">
                                    <div class="schedule-toggle" 
                                         @click="toggleFreshAirSchedule(index)"
                                         :class="schedule.is_enabled ? 'active' : ''">
                                        <div class="schedule-toggle-slider" :class="schedule.is_enabled ? 'on' : 'off'">
                                            <span x-text="schedule.is_enabled ? 'ON' : 'OFF'"></span>
                                        </div>
                                    </div>
                                </div>
                                
                                {{-- 开始时间 --}}
                                <div style="flex: 1; min-width: 0;">
                                    <label style="display: block; font-size: 11px; color: #1e40af; font-weight: 600; margin-bottom: 4px;">开始时间</label>
                                    <input type="time" x-model="schedule.start_time" style="width: 100%; padding: 6px; border: 1px solid #93c5fd; border-radius: 4px; font-size: 13px;">
                                </div>
                                
                                {{-- 结束时间 --}}
                                <div style="flex: 1; min-width: 0;">
                                    <label style="display: block; font-size: 11px; color: #1e40af; font-weight: 600; margin-bottom: 4px;">结束时间</label>
                                    <input type="time" x-model="schedule.end_time" style="width: 100%; padding: 6px; border: 1px solid #93c5fd; border-radius: 4px; font-size: 13px;">
                                </div>
                                
                                {{-- CO2上限 --}}
                                <div style="flex: 1; min-width: 0;">
                                    <label style="display: block; font-size: 11px; color: #059669; font-weight: 600; margin-bottom: 4px;">CO2上限(PPM)</label>
                                    <input type="number" step="10" x-model="schedule.co2_upper" style="width: 100%; padding: 6px; border: 1px solid #34d399; border-radius: 4px; font-size: 13px;">
                                </div>
                                
                                {{-- CO2下限 --}}
                                <div style="flex: 1; min-width: 0;">
                                    <label style="display: block; font-size: 11px; color: #059669; font-weight: 600; margin-bottom: 4px;">CO2下限(PPM)</label>
                                    <input type="number" step="10" x-model="schedule.co2_lower" style="width: 100%; padding: 6px; border: 1px solid #34d399; border-radius: 4px; font-size: 13px;">
                                </div>
                                
                                {{-- 删除按钮 --}}
                                <div style="flex: 0 0 auto;">
                                    <button 
                                        type="button"
                                        @click="removeFreshAirSchedule(index)"
                                        :disabled="configs.fresh_air.schedules.length <= 1"
                                        class="btn-delete-schedule">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width: 16px; height: 16px;">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
            
            {{-- Save Button --}}
            <div style="display: flex; justify-content: flex-end; margin-top: 20px; padding-top: 20px; border-top: 1px solid #e5e7eb;">
                <button 
                    @click="saveConfig('fresh_air')"
                    :disabled="saving"
                    class="btn-save-config btn-save-other">
                    <svg x-show="saving" class="animate-spin" style="width: 16px; height: 16px;" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle style="opacity: 0.25;" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path style="opacity: 0.75;" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <svg x-show="!saving" style="width: 18px; height: 18px;" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span x-text="saving ? '保存中...' : '保存配置'"></span>
                </button>
            </div>
        </div>
        
        {{-- Exhaust Control Panel --}}
        <div x-show="activeTab === 'exhaust'" style="background: white; border-radius: 12px; padding: 20px; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);">
            {{-- Main switch control --}}
            <div style="margin-bottom: 24px; padding: 16px 20px; background: linear-gradient(135deg, #f5f3ff 0%, #ede9fe 100%); border-radius: 10px; border: 2px solid #c4b5fd;">
                <div style="display: flex; align-items: center; justify-content: space-between;">
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <div style="width: 44px; height: 44px; background: white; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 22px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">💨</div>
                        <div>
                            <div style="font-size: 16px; font-weight: 700; color: #374151;">排风自动控制</div>
                            <div style="font-size: 12px; color: #6b7280; margin-top: 2px;">
                                <span x-show="configs.exhaust.is_enabled" style="color: #16a34a; font-weight: 600;">✓ 当前已启用自动控制</span>
                                <span x-show="!configs.exhaust.is_enabled">✗ 当前未启用自动控制</span>
                            </div>
                        </div>
                    </div>
                    {{-- 大开关 --}}
                    <label class="toggle-switch-large" style="cursor: pointer;">
                        <input type="checkbox" x-model="configs.exhaust.is_enabled" style="display: none;">
                        <div class="toggle-slider" :class="configs.exhaust.is_enabled ? 'on' : 'off'">
                            <span class="toggle-label" x-text="configs.exhaust.is_enabled ? 'ON' : 'OFF'"></span>
                            <div class="toggle-thumb"></div>
                        </div>
                    </label>
                </div>
            </div>

            <div x-show="configs.exhaust.is_enabled">
                {{-- 新风联动、延时启动、控制模式在同一行 --}}
                <div style="margin-bottom: 20px; display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
                    {{-- 新风联动 --}}
                    <div style="display: flex; align-items: center; gap: 8px; flex-shrink: 0;">
                        <span style="font-size: 14px; font-weight: 500; color: #374151; white-space: nowrap;">🔄 新风联动</span>
                        <label class="toggle-switch-small" style="cursor: pointer;">
                            <input type="checkbox" x-model="configs.exhaust.linkage_config.link_fresh_air" style="display: none;">
                            <div class="toggle-slider-small" :class="configs.exhaust.linkage_config.link_fresh_air ? 'on' : 'off'">
                                <span class="toggle-label-small" x-text="configs.exhaust.linkage_config.link_fresh_air ? 'ON' : 'OFF'"></span>
                                <div class="toggle-thumb-small"></div>
                            </div>
                        </label>
                    </div>
                    
                    {{-- 排风延迟启动 --}}
                    <div style="display: flex; align-items: center; gap: 6px; flex-shrink: 0;">
                        <span style="font-size: 13px; color: #0369a1; font-weight: 600; white-space: nowrap;">排风延迟启动</span>
                        <input type="number" x-model="configs.exhaust.delay_start" min="0" placeholder="0" 
                               :disabled="!configs.exhaust.linkage_config.link_fresh_air"
                               style="width: 50px; padding: 6px 2px; border: 2px solid #0ea5e9; border-bottom: 1px solid; border-radius: 4px; font-size: 13px; background: white; text-align: center; font-weight: 600; box-sizing: border-box;"
                               :style="!configs.exhaust.linkage_config.link_fresh_air ? 'background: #e5e7eb; border-color: #9ca3af; color: #9ca3af; cursor: not-allowed;' : ''">
                        <span style="font-size: 12px; color: #6b7280; font-weight: 500;">秒</span>
                    </div>
                    
                    {{-- 新风延迟启动 --}}
                    <div style="display: flex; align-items: center; gap: 6px; flex-shrink: 0;">
                        <span style="font-size: 13px; color: #0369a1; font-weight: 600; white-space: nowrap;">新风延迟启动</span>
                        <input type="number" x-model="configs.exhaust.fresh_air_delay_start" min="0" placeholder="0" 
                               :disabled="!configs.exhaust.linkage_config.link_fresh_air"
                               style="width: 50px; padding: 6px 2px; border: 2px solid #0ea5e9; border-bottom: 1px solid; border-radius: 4px; font-size: 13px; background: white; text-align: center; font-weight: 600; box-sizing: border-box;"
                               :style="!configs.exhaust.linkage_config.link_fresh_air ? 'background: #e5e7eb; border-color: #9ca3af; color: #9ca3af; cursor: not-allowed;' : ''">
                        <span style="font-size: 12px; color: #6b7280; font-weight: 500;">秒</span>
                    </div>
                    
                    {{-- 控制模式 --}}
                    <div style="display: flex; align-items: center; gap: 6px; flex: 1; min-width: 200px;">
                        <span style="font-size: 13px; font-weight: 500; color: #374151; white-space: nowrap;">控制模式</span>
                        <select x-model="configs.exhaust.mode" style="flex: 1; padding: 6px 10px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 13px; background: white;">
                            <option value="threshold">阈值控制</option>
                            <option value="cycle">循环控制</option>
                            <option value="schedule">定时控制</option>
                        </select>
                    </div>
                </div>
                
                {{-- CO2阈值控制 --}}
                <div x-show="configs.exhaust.mode === 'threshold'" style="margin-bottom: 20px;">
                    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px;">
                        <div>
                            <label style="display: block; font-size: 13px; font-weight: 500; color: #374151; margin-bottom: 6px;">CO2上限</label>
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <input type="number" step="10" x-model="configs.exhaust.threshold_upper" placeholder="1500" style="flex: 1; padding: 8px 12px; border: 1px solid #c4b5fd; border-radius: 6px; font-size: 13px;">
                                <span style="font-size: 13px; color: #6b7280; font-weight: 500;">PPM</span>
                            </div>
                        </div>
                        <div>
                            <label style="display: block; font-size: 13px; font-weight: 500; color: #374151; margin-bottom: 6px;">CO2下限</label>
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <input type="number" step="10" x-model="configs.exhaust.threshold_lower" placeholder="800" style="flex: 1; padding: 8px 12px; border: 1px solid #c4b5fd; border-radius: 6px; font-size: 13px;">
                                <span style="font-size: 13px; color: #6b7280; font-weight: 500;">PPM</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                {{-- 循环控制 --}}
                <div x-show="configs.exhaust.mode === 'cycle'" style="margin-bottom: 20px;">
                    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px;">
                        <div>
                            <label style="display: block; font-size: 13px; font-weight: 500; color: #374151; margin-bottom: 6px;">运行时长</label>
                            <div style="display: flex; gap: 8px;">
                                <input type="number" x-model="configs.exhaust.cycle_run_duration" placeholder="时长" style="flex: 1; padding: 8px 12px; border: 1px solid #c4b5fd; border-radius: 6px; font-size: 13px;">
                                <select x-model="configs.exhaust.cycle_run_unit" style="width: 90px; padding: 8px 10px; border: 1px solid #c4b5fd; border-radius: 6px; font-size: 13px; background: white;">
                                    <option value="minutes">分钟</option>
                                    <option value="seconds">秒</option>
                                </select>
                            </div>
                        </div>
                        <div>
                            <label style="display: block; font-size: 13px; font-weight: 500; color: #374151; margin-bottom: 6px;">停止时长</label>
                            <div style="display: flex; gap: 8px;">
                                <input type="number" x-model="configs.exhaust.cycle_stop_duration" placeholder="时长" style="flex: 1; padding: 8px 12px; border: 1px solid #c4b5fd; border-radius: 6px; font-size: 13px;">
                                <select x-model="configs.exhaust.cycle_stop_unit" style="width: 90px; padding: 8px 10px; border: 1px solid #c4b5fd; border-radius: 6px; font-size: 13px; background: white;">
                                    <option value="minutes">分钟</option>
                                    <option value="seconds">秒</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- 定时控制（时段配置） --}}
                <div x-show="configs.exhaust.mode === 'schedule'" style="margin-bottom: 20px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                        <label style="font-size: 14px; font-weight: 500; color: #374151;">时段配置 (最多5个)</label>
                        <button 
                            type="button"
                            @click="addExhaustSchedule()"
                            :disabled="configs.exhaust.schedules.length >= 5"
                            style="display: flex; align-items: center; gap: 4px; padding: 6px 12px; background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%); color: white; border: none; border-radius: 6px; font-size: 12px; font-weight: 600; cursor: pointer; box-shadow: 0 2px 4px rgba(124, 58, 237, 0.3); opacity: configs.exhaust.schedules.length >= 5 ? 0.5 : 1;">
                            <svg style="width: 14px; height: 14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                            </svg>
                            添加时段
                        </button>
                    </div>
                    
                    <template x-for="(schedule, index) in configs.exhaust.schedules" :key="index">
                        <div style="background: #f9fafb; border-radius: 8px; padding: 16px; margin-bottom: 12px; border: 2px solid; transition: all 0.3s ease;"
                             :style="schedule.is_enabled ? 'border-color: #22c55e; box-shadow: 0 0 0 3px rgba(34, 197, 94, 0.1);' : 'border-color: #e5e7eb;'">
                            <div style="display: flex; gap: 8px; align-items: flex-end; background: #f8fafc; border-radius: 8px; padding: 12px; border: 1px solid #e2e8f0;">
                                {{-- 启用开关 --}}
                                <div style="flex: 0 0 auto;">
                                    <div class="schedule-toggle" 
                                         @click="toggleExhaustSchedule(index)"
                                         :class="schedule.is_enabled ? 'active' : ''">
                                        <div class="schedule-toggle-slider" :class="schedule.is_enabled ? 'on' : 'off'">
                                            <span x-text="schedule.is_enabled ? 'ON' : 'OFF'"></span>
                                        </div>
                                    </div>
                                </div>
                                
                                {{-- 开始时间 --}}
                                <div style="flex: 1; min-width: 0;">
                                    <label style="display: block; font-size: 11px; color: #1e40af; font-weight: 600; margin-bottom: 4px;">开始时间</label>
                                    <input type="time" x-model="schedule.start_time" style="width: 100%; padding: 6px; border: 1px solid #93c5fd; border-radius: 4px; font-size: 13px;">
                                </div>
                                
                                {{-- 结束时间 --}}
                                <div style="flex: 1; min-width: 0;">
                                    <label style="display: block; font-size: 11px; color: #1e40af; font-weight: 600; margin-bottom: 4px;">结束时间</label>
                                    <input type="time" x-model="schedule.end_time" style="width: 100%; padding: 6px; border: 1px solid #93c5fd; border-radius: 4px; font-size: 13px;">
                                </div>
                                
                                {{-- CO2上限 --}}
                                <div style="flex: 1; min-width: 0;">
                                    <label style="display: block; font-size: 11px; color: #059669; font-weight: 600; margin-bottom: 4px;">CO2上限(PPM)</label>
                                    <input type="number" step="10" x-model="schedule.co2_upper" style="width: 100%; padding: 6px; border: 1px solid #34d399; border-radius: 4px; font-size: 13px;">
                                </div>
                                
                                {{-- CO2下限 --}}
                                <div style="flex: 1; min-width: 0;">
                                    <label style="display: block; font-size: 11px; color: #059669; font-weight: 600; margin-bottom: 4px;">CO2下限(PPM)</label>
                                    <input type="number" step="10" x-model="schedule.co2_lower" style="width: 100%; padding: 6px; border: 1px solid #34d399; border-radius: 4px; font-size: 13px;">
                                </div>
                                
                                {{-- 删除按钮 --}}
                                <div style="flex: 0 0 auto;">
                                    <button 
                                        type="button"
                                        @click="removeExhaustSchedule(index)"
                                        :disabled="configs.exhaust.schedules.length <= 1"
                                        class="btn-delete-schedule">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width: 16px; height: 16px;">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
            
            {{-- Save Button --}}
            <div style="display: flex; justify-content: flex-end; margin-top: 20px; padding-top: 16px; border-top: 1px solid #e5e7eb;">
                <button 
                    @click="saveConfig('exhaust')" 
                    :disabled="saving" 
                    style="display: flex; align-items: center; gap: 6px; background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%); color: white; border: none; border-radius: 8px; padding: 10px 24px; font-size: 14px; font-weight: 600; cursor: pointer; box-shadow: 0 4px 6px -1px rgba(124, 58, 237, 0.4); opacity: saving ? 0.7 : 1;">
                    <svg x-show="!saving" style="width: 16px; height: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    <span x-text="saving ? '保存中...' : '保存配置'"></span>
                </button>
            </div>
        </div>
        
        {{-- Lighting Control Panel --}}
        <div x-show="activeTab === 'lighting'" style="background: white; border-radius: 12px; padding: 24px; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);">
            {{-- Main switch control --}}
            <div style="margin-bottom: 24px; padding: 16px 20px; background: linear-gradient(135deg, #fef9c3 0%, #fde047 100%); border-radius: 10px; border: 2px solid #facc15;">
                <div style="display: flex; align-items: center; justify-content: space-between;">
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <div style="width: 40px; height: 40px; background: white; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">☀️</div>
                        <div>
                            <div style="font-size: 15px; font-weight: 700; color: #374151;">照明自动控制</div>
                            <div style="font-size: 12px; color: #6b7280; margin-top: 2px;">
                                <span x-show="configs.lighting.is_enabled" style="color: #16a34a; font-weight: 600;">✓ 当前已启用自动控制</span>
                                <span x-show="!configs.lighting.is_enabled">✗ 当前未启用自动控制</span>
                            </div>
                        </div>
                    </div>
                    <label class="toggle-switch-large" style="cursor: pointer;">
                        <input type="checkbox" x-model="configs.lighting.is_enabled" style="display: none;">
                        <div class="toggle-slider" :class="configs.lighting.is_enabled ? 'on' : 'off'">
                            <span class="toggle-label" x-text="configs.lighting.is_enabled ? 'ON' : 'OFF'"></span>
                            <div class="toggle-thumb"></div>
                        </div>
                    </label>
                </div>
            </div>
            
            <div x-show="configs.lighting.is_enabled">
                <div style="margin-bottom: 20px; display: flex; align-items: center; gap: 12px;">
                    <label style="font-size: 14px; font-weight: 500; color: #374151; white-space: nowrap;">控制模式</label>
                    <select x-model="configs.lighting.mode" style="flex: 1; padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px;">
                        <option value="cycle">循环控制</option>
                        <option value="schedule">定时控制</option>
                    </select>
                </div>
                
                <div x-show="configs.lighting.mode === 'cycle'" style="margin-bottom: 20px;">
                    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px;">
                        <div>
                            <label style="display: block; font-size: 14px; font-weight: 500; color: #374151; margin-bottom: 8px;">LED开启</label>
                            <div style="display: flex; gap: 8px;">
                                <input type="number" x-model="configs.lighting.cycle_run_duration" style="flex: 1; padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 6px;">
                                <select x-model="configs.lighting.cycle_run_unit" style="padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 6px;">
                                    <option value="minutes">分钟</option>
                                    <option value="hours">小时</option>
                                </select>
                            </div>
                        </div>
                        <div>
                            <label style="display: block; font-size: 14px; font-weight: 500; color: #374151; margin-bottom: 8px;">LED关闭</label>
                            <div style="display: flex; gap: 8px;">
                                <input type="number" x-model="configs.lighting.cycle_stop_duration" style="flex: 1; padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 6px;">
                                <select x-model="configs.lighting.cycle_stop_unit" style="padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 6px;">
                                    <option value="minutes">分钟</option>
                                    <option value="hours">小时</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- 照明时段配置 --}}
                <div x-show="configs.lighting.mode === 'schedule'" style="margin-bottom: 20px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                        <label style="font-size: 14px; font-weight: 500; color: #374151;">时段配置 (最多5个)</label>
                        <button 
                            type="button"
                            @click="addLightingSchedule()"
                            :disabled="configs.lighting.schedules.length >= 5"
                            class="btn-add-schedule">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 16px; height: 16px;">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                            </svg>
                            添加时段
                        </button>
                    </div>
                    
                    <template x-for="(schedule, index) in configs.lighting.schedules" :key="index">
                        <div style="background: #f9fafb; border-radius: 8px; padding: 16px; margin-bottom: 12px; border: 2px solid; transition: all 0.3s ease;"
                             :style="schedule.is_enabled ? 'border-color: #22c55e; box-shadow: 0 0 0 3px rgba(34, 197, 94, 0.1);' : 'border-color: #e5e7eb;'">
                            <div style="display: flex; gap: 8px; align-items: flex-end; background: #f8fafc; border-radius: 8px; padding: 12px; border: 1px solid #e2e8f0;">
                                {{-- 启用开关 --}}
                                <div style="flex: 0 0 auto;">
                                    <div class="schedule-toggle" 
                                         @click="toggleLightingSchedule(index)"
                                         :class="schedule.is_enabled ? 'active' : ''">
                                        <div class="schedule-toggle-slider" :class="schedule.is_enabled ? 'on' : 'off'">
                                            <span x-text="schedule.is_enabled ? 'ON' : 'OFF'"></span>
                                        </div>
                                    </div>
                                </div>
                                
                                {{-- 开始时间 --}}
                                <div style="flex: 1; min-width: 0;">
                                    <label style="display: block; font-size: 11px; color: #1e40af; font-weight: 600; margin-bottom: 4px;">开始时间</label>
                                    <input type="time" x-model="schedule.start_time" style="width: 100%; padding: 6px; border: 1px solid #93c5fd; border-radius: 4px; font-size: 13px;">
                                </div>
                                
                                {{-- 结束时间 --}}
                                <div style="flex: 1; min-width: 0;">
                                    <label style="display: block; font-size: 11px; color: #1e40af; font-weight: 600; margin-bottom: 4px;">结束时间</label>
                                    <input type="time" x-model="schedule.end_time" style="width: 100%; padding: 6px; border: 1px solid #93c5fd; border-radius: 4px; font-size: 13px;">
                                </div>
                                
                                {{-- LED开启 --}}
                                <div style="flex: 0 0 auto;">
                                    <label style="display: block; font-size: 11px; color: #ca8a04; font-weight: 600; margin-bottom: 4px;">LED开启</label>
                                    <div style="display: flex; gap: 4px; align-items: center;">
                                        <input type="number" x-model="schedule.led_on_duration" style="width: 50px; padding: 6px; border: 1px solid #facc15; border-radius: 4px; font-size: 13px;">
                                        <select x-model="schedule.led_on_unit" style="width: 60px; padding: 6px; border: 1px solid #facc15; border-radius: 4px; font-size: 13px; background: white;">
                                            <option value="minutes">分钟</option>
                                            <option value="hours">小时</option>
                                        </select>
                                    </div>
                                </div>
                                
                                {{-- LED关闭 --}}
                                <div style="flex: 0 0 auto;">
                                    <label style="display: block; font-size: 11px; color: #ca8a04; font-weight: 600; margin-bottom: 4px;">LED关闭</label>
                                    <div style="display: flex; gap: 4px; align-items: center;">
                                        <input type="number" x-model="schedule.led_off_duration" style="width: 50px; padding: 6px; border: 1px solid #facc15; border-radius: 4px; font-size: 13px;">
                                        <select x-model="schedule.led_off_unit" style="width: 60px; padding: 6px; border: 1px solid #facc15; border-radius: 4px; font-size: 13px; background: white;">
                                            <option value="minutes">分钟</option>
                                            <option value="hours">小时</option>
                                        </select>
                                    </div>
                                </div>
                                
                                {{-- 删除按钮 --}}
                                <div style="flex: 0 0 auto;">
                                    <button 
                                        type="button"
                                        @click="removeLightingSchedule(index)"
                                        :disabled="configs.lighting.schedules.length <= 1"
                                        class="btn-delete-schedule">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width: 16px; height: 16px;">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
            
            {{-- Save Button --}}
            <div style="display: flex; justify-content: flex-end; margin-top: 20px; padding-top: 20px; border-top: 1px solid #e5e7eb;">
                <button 
                    @click="saveConfig('lighting')"
                    :disabled="saving"
                    class="btn-save-config btn-save-other">
                    <svg x-show="saving" class="animate-spin" style="width: 16px; height: 16px;" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle style="opacity: 0.25;" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path style="opacity: 0.75;" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <svg x-show="!saving" style="width: 18px; height: 18px;" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span x-text="saving ? '保存中...' : '保存配置'"></span>
                </button>
            </div>
        </div>
    </div>
    
    <style>
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        .animate-spin {
            animation: spin 1s linear infinite;
        }
        
        /* Toggle switch styles */
        .toggle-switch-large {
            position: relative;
            display: inline-block;
        }
        
        .toggle-switch-large input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        
        .toggle-slider {
            position: relative;
            display: flex;
            align-items: center;
            width: 80px;
            height: 36px;
            border-radius: 18px;
            transition: all 0.3s ease;
            cursor: pointer;
            overflow: hidden;
        }
        
        .toggle-slider.off {
            background: linear-gradient(135deg, #e5e7eb 0%, #d1d5db 100%);
            border: 2px solid #9ca3af;
        }
        
        .toggle-slider.on {
            background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
            border: 2px solid #16a34a;
            box-shadow: 0 4px 12px rgba(34, 197, 94, 0.4);
        }
        
        .toggle-label {
            font-size: 12px;
            font-weight: 800;
            color: white;
            position: absolute;
            transition: all 0.3s ease;
        }
        
        .toggle-slider.off .toggle-label {
            right: 10px;
            color: #6b7280;
        }
        
        .toggle-slider.on .toggle-label {
            left: 10px;
            color: white;
        }
        
        .toggle-thumb {
            position: absolute;
            top: 2px;
            width: 28px;
            height: 28px;
            border-radius: 50%;
            background: white;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
            transition: all 0.3s cubic-bezier(0.68, -0.55, 0.265, 1.55);
        }
        
        .toggle-slider.off .toggle-thumb {
            left: 2px;
        }
        
        .toggle-slider.on .toggle-thumb {
            left: calc(100% - 30px);
            box-shadow: 0 2px 8px rgba(0,0,0,0.3);
        }
        
        /* Small toggle switch for inline use */
        .toggle-switch-small {
            position: relative;
            display: inline-block;
        }
        
        .toggle-switch-small input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        
        .toggle-slider-small {
            position: relative;
            display: flex;
            align-items: center;
            width: 60px;
            height: 28px;
            border-radius: 14px;
            transition: all 0.3s ease;
            cursor: pointer;
            overflow: hidden;
        }
        
        .toggle-slider-small.off {
            background: linear-gradient(135deg, #e5e7eb 0%, #d1d5db 100%);
            border: 2px solid #9ca3af;
        }
        
        .toggle-slider-small.on {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            border: 2px solid #2563eb;
            box-shadow: 0 2px 8px rgba(59, 130, 246, 0.4);
        }
        
        .toggle-label-small {
            font-size: 10px;
            font-weight: 700;
            color: white;
            position: absolute;
            transition: all 0.3s ease;
        }
        
        .toggle-slider-small.off .toggle-label-small {
            right: 8px;
            color: #6b7280;
        }
        
        .toggle-slider-small.on .toggle-label-small {
            left: 8px;
            color: white;
        }
        
        .toggle-thumb-small {
            position: absolute;
            top: 2px;
            width: 22px;
            height: 22px;
            border-radius: 50%;
            background: white;
            box-shadow: 0 2px 6px rgba(0,0,0,0.2);
            transition: all 0.3s cubic-bezier(0.68, -0.55, 0.265, 1.55);
        }
        
        .toggle-slider-small.off .toggle-thumb-small {
            left: 2px;
        }
        
        .toggle-slider-small.on .toggle-thumb-small {
            left: calc(100% - 24px);
            box-shadow: 0 2px 6px rgba(0,0,0,0.3);
        }
        
        /* Schedule toggle */
        .schedule-toggle-slider {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 50px;
            height: 24px;
            border-radius: 12px;
            font-size: 10px;
            font-weight: 700;
            transition: all 0.2s ease;
            cursor: pointer;
        }
        
        .schedule-toggle-slider.on {
            background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
            color: white;
            box-shadow: 0 2px 6px rgba(34, 197, 94, 0.4);
        }
        
        .schedule-toggle-slider.off {
            background: #e5e7eb;
            color: #6b7280;
        }
        
        .schedule-toggle:hover .schedule-toggle-slider.off {
            background: #d1d5db;
        }
        
        /* Add schedule button */
        .btn-add-schedule {
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 8px 14px;
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            box-shadow: 0 2px 4px rgba(59, 130, 246, 0.3);
        }
        
        .btn-add-schedule:hover:not(:disabled) {
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(59, 130, 246, 0.4);
        }
        
        .btn-add-schedule:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        /* Delete button */
        .btn-delete-schedule {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 6px;
            background: #e5e7eb;
            color: #6b7280;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .btn-delete-schedule:hover:not(:disabled) {
            background: #d1d5db;
            color: #475569;
            transform: translateY(-1px);
        }
        
        .btn-delete-schedule:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        /* Save button styles - BEAUTIFUL */
        .btn-save-config {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 12px 28px;
            border: none;
            border-radius: 10px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }
        
        .btn-save-config:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }
        
        .btn-save-config:active:not(:disabled) {
            transform: translateY(0);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        
        .btn-save-config:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }
        
        /* Temperature save button - Orange gradient */
        .btn-save-temperature {
            background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
            color: white;
            box-shadow: 0 4px 6px -1px rgba(249, 115, 22, 0.4), 0 2px 4px -1px rgba(249, 115, 22, 0.2);
        }
        
        .btn-save-temperature:hover:not(:disabled) {
            background: linear-gradient(135deg, #ea580c 0%, #c2410c 100%);
            box-shadow: 0 10px 15px -3px rgba(249, 115, 22, 0.5), 0 4px 6px -2px rgba(249, 115, 22, 0.3);
        }
        
        /* Other controls save button - Green gradient */
        .btn-save-other {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            box-shadow: 0 4px 6px -1px rgba(16, 185, 129, 0.4), 0 2px 4px -1px rgba(16, 185, 129, 0.2);
        }
        
        .btn-save-other:hover:not(:disabled) {
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
            box-shadow: 0 10px 15px -3px rgba(16, 185, 129, 0.5), 0 4px 6px -2px rgba(16, 185, 129, 0.3);
        }
        
        /* x-cloak - 隐藏未初始化的 Alpine 元素 */
        [x-cloak] {
            display: none !important;
        }
        
        /* 通知动画增强 */
        @keyframes slideInDown {
            from {
                opacity: 0;
                transform: translateY(-30px) scale(0.95);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }
        
        @keyframes slideOutUp {
            from {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
            to {
                opacity: 0;
                transform: translateY(-30px) scale(0.95);
            }
        }
    </style>
</div>