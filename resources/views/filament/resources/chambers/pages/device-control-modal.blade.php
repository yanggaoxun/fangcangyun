<div class="p-5" style="min-width: 850px;">
    @php
        $deviceNames = App\Models\ChamberManualControl::getDeviceNames();
        $chamber = App\Models\Chamber::find($chamberId);
        $latestData = App\Models\ChamberManualControl::where('chamber_id', $chamberId)->latest('recorded_at')->first();
    @endphp

    @if($chamber)
        {{-- 头部信息栏 --}}
        <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 10px; padding: 14px 18px; margin-bottom: 16px; color: white; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <div style="background: rgba(255,255,255,0.25); border-radius: 8px; padding: 8px; width: 36px; height: 36px; display: flex; align-items: center; justify-content: center;">
                        <svg style="width: 20px; height: 20px;" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"></path>
                        </svg>
                    </div>
                    <div>
                        <p style="font-size: 15px; font-weight: 600; margin: 0; line-height: 1.3;">{{ $chamber->base->name ?? '未命名基地' }} <span style="opacity: 0.7;">/</span> {{ $chamber->name }}</p>
                        @if($latestData)
                            <p style="font-size: 11px; opacity: 0.8; margin: 2px 0 0 0; display: flex; align-items: center; gap: 4px;">
                                <svg style="width: 12px; height: 12px;" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"></path>
                                </svg>
                                {{ $latestData->recorded_at->format('Y-m-d H:i:s') }}
                            </p>
                        @endif
                    </div>
                </div>
                
                @if($latestData)
                    <div style="background: rgba(255,255,255,0.2); border-radius: 20px; padding: 5px 14px; font-size: 11px; display: flex; align-items: center; gap: 6px;">
                        <span style="display: inline-block; width: 7px; height: 7px; background: #4ade80; border-radius: 50%;"></span>
                        在线
                    </div>
                @endif
            </div>
        </div>
    @endif

    @if($latestData)
        {{-- 实时环境数据卡片 --}}
        <div style="margin-bottom: 16px;">
            <div style="display: flex; align-items: center; gap: 6px; margin-bottom: 12px;">
                <div style="width: 3px; height: 16px; background: #3b82f6; border-radius: 2px;"></div>
                <h4 style="font-size: 14px; font-weight: 600; color: #374151; margin: 0;">📊 实时环境数据</h4>
            </div>
            
            <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px;">
                {{-- 温度 --}}
                <div style="background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%); border-radius: 10px; padding: 14px; position: relative; overflow: hidden; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);">
                    <div style="display: flex; align-items: center; gap: 6px; margin-bottom: 6px;">
                        <span style="font-size: 16px;">🌡️</span>
                        <span style="font-size: 11px; color: #92400e; font-weight: 600;">温度</span>
                    </div>
                    <div style="font-size: 24px; font-weight: 700; color: #92400e; line-height: 1;">{{ number_format($latestData->temperature, 1) }}<span style="font-size: 13px; font-weight: 500; margin-left: 2px;">°C</span></div>
                </div>

                {{-- 湿度 --}}
                <div style="background: linear-gradient(135deg, #dbeafe 0%, #93c5fd 100%); border-radius: 10px; padding: 14px; position: relative; overflow: hidden; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);">
                    <div style="display: flex; align-items: center; gap: 6px; margin-bottom: 6px;">
                        <span style="font-size: 16px;">💧</span>
                        <span style="font-size: 11px; color: #1e40af; font-weight: 600;">湿度</span>
                    </div>
                    <div style="font-size: 24px; font-weight: 700; color: #1e40af; line-height: 1;">{{ number_format($latestData->humidity, 1) }}<span style="font-size: 13px; font-weight: 500; margin-left: 2px;">%</span></div>
                </div>

                {{-- CO2浓度 --}}
                <div style="background: linear-gradient(135deg, #d1fae5 0%, #6ee7b7 100%); border-radius: 10px; padding: 14px; position: relative; overflow: hidden; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);">
                    <div style="display: flex; align-items: center; gap: 6px; margin-bottom: 6px;">
                        <span style="font-size: 16px;">🌿</span>
                        <span style="font-size: 11px; color: #065f46; font-weight: 600;">CO₂</span>
                    </div>
                    <div style="font-size: 24px; font-weight: 700; color: #065f46; line-height: 1;">{{ number_format($latestData->co2_level, 0) }}<span style="font-size: 13px; font-weight: 500; margin-left: 2px;">ppm</span></div>
                </div>

                {{-- 光照强度 --}}
                <div style="background: linear-gradient(135deg, #fef9c3 0%, #fde047 100%); border-radius: 10px; padding: 14px; position: relative; overflow: hidden; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);">
                    <div style="display: flex; align-items: center; gap: 6px; margin-bottom: 6px;">
                        <span style="font-size: 16px;">☀️</span>
                        <span style="font-size: 11px; color: #854d0e; font-weight: 600;">光照</span>
                    </div>
                    <div style="font-size: 24px; font-weight: 700; color: #854d0e; line-height: 1;">{{ number_format($latestData->light_intensity, 0) }}<span style="font-size: 13px; font-weight: 500; margin-left: 2px;">lux</span></div>
                </div>
            </div>
        </div>

        {{-- 设备开关控制 --}}
        <div style="background: white; border-radius: 12px; padding: 16px; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08); border: 1px solid #e5e7eb;">
            <div style="display: flex; align-items: center; gap: 6px; margin-bottom: 14px;">
                <div style="width: 3px; height: 16px; background: #8b5cf6; border-radius: 2px;"></div>
                <h4 style="font-size: 14px; font-weight: 600; color: #374151; margin: 0;">🔌 设备开关控制</h4>
            </div>
            
            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px;">
                @php
                    $devices = [
                        ['key' => 'inner_circulation', 'icon' => '🔄', 'name' => $deviceNames['inner_circulation'] ?? '内循环'],
                        ['key' => 'cooling', 'icon' => '❄️', 'name' => $deviceNames['cooling'] ?? '制冷'],
                        ['key' => 'heating', 'icon' => '🔥', 'name' => $deviceNames['heating'] ?? '制热'],
                        ['key' => 'fan', 'icon' => '💨', 'name' => $deviceNames['fan'] ?? '风机'],
                        ['key' => 'four_way_valve', 'icon' => '🔧', 'name' => $deviceNames['four_way_valve'] ?? '四通阀'],
                        ['key' => 'fresh_air', 'icon' => '🌬️', 'name' => $deviceNames['fresh_air'] ?? '新风'],
                        ['key' => 'humidification', 'icon' => '💧', 'name' => $deviceNames['humidification'] ?? '加湿'],
                        ['key' => 'lighting_supplement', 'icon' => '💡', 'name' => $deviceNames['lighting_supplement'] ?? '补光'],
                        ['key' => 'lighting', 'icon' => '☀️', 'name' => $deviceNames['lighting'] ?? '照明'],
                    ];
                @endphp
                
                @foreach($devices as $device)
                    @php
                        $state = $latestData->{$device['key']} ?? false;
                        $bgGradient = $state 
                            ? 'linear-gradient(135deg, #10b981 0%, #059669 100%)' 
                            : 'linear-gradient(135deg, #f9fafb 0%, #f3f4f6 100%)';
                        $borderColor = $state ? '#10b981' : '#e5e7eb';
                    @endphp
                    <div style="background: {{ $bgGradient }}; border: 1px solid {{ $borderColor }}; border-radius: 10px; padding: 12px 14px;">
                        <div style="display: flex; align-items: center; justify-content: space-between;">
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <div style="width: 32px; height: 32px; background: {{ $state ? 'rgba(255,255,255,0.95)' : 'white' }}; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 18px; box-shadow: 0 1px 2px rgba(0,0,0,0.08);">
                                    {{ $device['icon'] }}
                                </div>
                                <div>
                                    <div style="font-weight: 600; color: {{ $state ? '#ffffff' : '#374151' }}; font-size: 13px; line-height: 1.3;">{{ $device['name'] }}</div>
                                    <div style="font-size: 11px; color: {{ $state ? 'rgba(255,255,255,0.85)' : '#9ca3af' }}; margin-top: 1px;">{{ $state ? '开启' : '关闭' }}</div>
                                </div>
                            </div>
                            
                            {{-- 开关按钮 --}}
                            <button
                                type="button"
                                wire:click="toggleDevice({{ $chamberId }}, '{{ $device['key'] }}')"
                                wire:loading.attr="disabled"
                                style="
                                    position: relative;
                                    display: inline-flex;
                                    height: 22px;
                                    width: 40px;
                                    flex-shrink: 0;
                                    cursor: pointer;
                                    border-radius: 9999px;
                                    border: none;
                                    transition: all 0.2s ease;
                                    {{ $state ? 'background-color: rgba(255,255,255,0.9);' : 'background-color: #d1d5db;' }}
                                "
                            >
                                <span
                                    style="
                                        pointer-events: none;
                                        display: inline-block;
                                        height: 18px;
                                        width: 18px;
                                        transform: {{ $state ? 'translateX(20px)' : 'translateX(2px)' }};
                                        border-radius: 9999px;
                                        background-color: {{ $state ? '#10b981' : 'white' }};
                                        box-shadow: 0 1px 2px rgba(0,0,0,0.15);
                                        transition: all 0.2s ease;
                                        margin-top: 2px;
                                    "
                                    aria-hidden="true"
                                ></span>
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>        
        </div>
    @else
        <div style="background: #f9fafb; border-radius: 12px; padding: 40px; text-align: center; color: #6b7280;">
            <div style="background: white; border-radius: 50%; width: 64px; height: 64px; display: flex; align-items: center; justify-content: center; margin: 0 auto 12px; box-shadow: 0 2px 4px rgba(0,0,0,0.08);">
                <span style="font-size: 32px;">📭</span>
            </div>
            <p style="font-size: 15px; font-weight: 500; margin: 0;">暂无设备状态数据</p>
            <p style="font-size: 12px; color: #9ca3af; margin: 6px 0 0 0;">请检查数据库连接或添加初始数据</p>
        </div>
    @endif
</div>