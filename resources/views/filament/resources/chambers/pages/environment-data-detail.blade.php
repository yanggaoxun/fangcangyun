<div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; width: 100%;">
    <!-- 第一列：位置信息 -->
    <div style="background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 8px; padding: 16px;">
        <div style="display: flex; align-items: center; margin-bottom: 12px; border-bottom: 2px solid #bfdbfe; padding-bottom: 8px;">
            <span style="font-size: 20px; margin-right: 8px;">📍</span>
            <span style="font-weight: 600; color: #1f2937;">位置信息</span>
        </div>
        
        <div style="margin-bottom: 12px;">
            <div style="font-size: 12px; color: #6b7280; margin-bottom: 4px;">所属基地</div>
            <div style="font-weight: 600; color: #111827; font-size: 16px; background: white; padding: 8px; border-radius: 6px;">{{ $record->chamber->base->name ?? '-' }}</div>
        </div>
        
        <div>
            <div style="font-size: 12px; color: #6b7280; margin-bottom: 4px;">方舱</div>
            <div style="font-weight: 600; color: #111827; font-size: 16px; background: white; padding: 8px; border-radius: 6px;">{{ $record->chamber->name ?? '-' }}</div>
        </div>
    </div>
    
    <!-- 第二列：环境参数 -->
    <div style="background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 8px; padding: 16px;">
        <div style="display: flex; align-items: center; margin-bottom: 12px; border-bottom: 2px solid #e5e7eb; padding-bottom: 8px;">
            <span style="font-size: 20px; margin-right: 8px;">🌡️</span>
            <span style="font-weight: 600; color: #1f2937;">环境参数</span>
        </div>
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-bottom: 12px;">
            <div style="background: #fef2f2; border: 1px solid #fecaca; border-radius: 6px; padding: 12px; text-align: center;">
                <div style="font-size: 11px; color: #6b7280;">温度</div>
                <div style="font-size: 24px; font-weight: 700; color: #dc2626;">{{ number_format($record->temperature, 1) }}</div>
                <div style="font-size: 11px; color: #9ca3af;">°C</div>
            </div>
            
            <div style="background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 6px; padding: 12px; text-align: center;">
                <div style="font-size: 11px; color: #6b7280;">湿度</div>
                <div style="font-size: 24px; font-weight: 700; color: #2563eb;">{{ number_format($record->humidity, 1) }}</div>
                <div style="font-size: 11px; color: #9ca3af;">%</div>
            </div>
            
            <div style="background: #f5f3ff; border: 1px solid #ddd6fe; border-radius: 6px; padding: 12px; text-align: center;">
                <div style="font-size: 11px; color: #6b7280;">CO₂</div>
                <div style="font-size: 24px; font-weight: 700; color: #7c3aed;">{{ number_format($record->co2_level, 0) }}</div>
                <div style="font-size: 11px; color: #9ca3af;">ppm</div>
            </div>
            
            <div style="background: #fefce8; border: 1px solid #fde047; border-radius: 6px; padding: 12px; text-align: center;">
                <div style="font-size: 11px; color: #6b7280;">光照</div>
                <div style="font-size: 20px; font-weight: 700; color: #ca8a04;">{{ $record->light_intensity ? number_format($record->light_intensity, 0) : '-' }}</div>
                <div style="font-size: 11px; color: #9ca3af;">lux</div>
            </div>
        </div>
        
        <div style="background: white; border: 1px solid #e5e7eb; border-radius: 6px; padding: 8px;">
            <div style="font-size: 11px; color: #6b7280;">记录时间</div>
            <div style="font-weight: 500; color: #374151; font-size: 13px;">{{ $record->recorded_at?->format('Y-m-d H:i') ?? '-' }}</div>
        </div>
    </div>
    
    <!-- 第三列：状态信息 -->
    <div style="background: {{ $record->is_anomaly ? '#fef2f2' : '#f0fdf4' }}; border: 1px solid {{ $record->is_anomaly ? '#fecaca' : '#86efac' }}; border-radius: 8px; padding: 16px;">
        <div style="display: flex; align-items: center; margin-bottom: 12px; border-bottom: 2px solid {{ $record->is_anomaly ? '#fecaca' : '#86efac' }}; padding-bottom: 8px;">
            <span style="font-size: 20px; margin-right: 8px;">⚡</span>
            <span style="font-weight: 600; color: #1f2937;">状态信息</span>
        </div>
        
        <div style="display: flex; flex-direction: column; align-items: center; padding: 20px 0;">
            @if($record->is_anomaly)
                <div style="width: 64px; height: 64px; background: #fecaca; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-bottom: 12px;">
                    <span style="font-size: 32px;">⚠️</span>
                </div>
                <div style="font-size: 18px; font-weight: 700; color: #dc2626;">检测到异常</div>
                <div style="font-size: 13px; color: #dc2626; margin-top: 4px;">请检查设备状态</div>
            @else
                <div style="width: 64px; height: 64px; background: #86efac; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-bottom: 12px;">
                    <span style="font-size: 32px;">✅</span>
                </div>
                <div style="font-size: 18px; font-weight: 700; color: #16a34a;">运行正常</div>
                <div style="font-size: 13px; color: #16a34a; margin-top: 4px;">系统运行良好</div>
            @endif
        </div>
        
        @if($record->notes)
            <div style="background: white; border: 1px solid #e5e7eb; border-radius: 6px; padding: 10px; margin-top: 8px;">
                <div style="font-size: 11px; color: #6b7280; margin-bottom: 4px;">📝 备注</div>
                <div style="font-size: 13px; color: #374151; line-height: 1.4;">{{ $record->notes }}</div>
            </div>
        @endif
    </div>
</div>
