<x-filament-widgets::widget class="fi-wi-quick-actions">
    <style>
        .quick-actions-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1rem;
        }
        
        .quick-action-card {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            padding: 1.5rem 1rem;
            border-radius: 12px;
            text-decoration: none;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            border: 1px solid rgba(0, 0, 0, 0.06);
            background: #ffffff;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
        }
        
        .quick-action-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 32px rgba(0, 0, 0, 0.1);
            border-color: rgba(0, 0, 0, 0.1);
        }
        
        .quick-action-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 48px;
            height: 48px;
            border-radius: 12px;
            background: rgba(0, 0, 0, 0.04);
            overflow: hidden;
            flex-shrink: 0;
        }
        
        .quick-action-icon svg {
            width: 24px !important;
            height: 24px !important;
            max-width: 24px !important;
            max-height: 24px !important;
            min-width: 24px !important;
            min-height: 24px !important;
            flex-shrink: 0;
            display: block;
        }
        
        .quick-action-primary .quick-action-icon {
            background: rgba(245, 158, 11, 0.12);
            color: #f59e0b;
        }
        
        .quick-action-success .quick-action-icon {
            background: rgba(16, 185, 129, 0.12);
            color: #10b981;
        }
        
        .quick-action-info .quick-action-icon {
            background: rgba(59, 130, 246, 0.12);
            color: #3b82f6;
        }
        
        .quick-action-warning .quick-action-icon {
            background: rgba(245, 158, 11, 0.12);
            color: #f59e0b;
        }
        
        .quick-action-label {
            font-size: 0.875rem;
            font-weight: 500;
            color: #374151;
        }
        
        @media (max-width: 768px) {
            .quick-actions-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media (max-width: 480px) {
            .quick-actions-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
    
    <div class="quick-actions-grid">
        @foreach ($this->getActions() as $action)
            <a
                href="{{ $action['url'] }}"
                class="quick-action-card quick-action-{{ $action['color'] }}"
            >
                <div class="quick-action-icon">
                    @if($action['icon'] === 'heroicon-m-plus-circle')
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" style="width:24px;height:24px;">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm.75-11.25a.75.75 0 00-1.5 0v2.5h-2.5a.75.75 0 000 1.5h2.5v2.5a.75.75 0 001.5 0v-2.5h2.5a.75.75 0 000-1.5h-2.5v-2.5z" clip-rule="evenodd" />
                        </svg>
                    @elseif($action['icon'] === 'heroicon-m-video-camera')
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" style="width:24px;height:24px;">
                            <path d="M4.5 2A2.5 2.5 0 002 4.5v11A2.5 2.5 0 004.5 18h7a2.5 2.5 0 002.5-2.5v-11A2.5 2.5 0 0011.5 2h-7zM14 9.5a.5.5 0 01.5-.5h2a.5.5 0 01.5.5v1a.5.5 0 01-.5.5h-2a.5.5 0 01-.5-.5v-1z" />
                        </svg>
                    @elseif($action['icon'] === 'heroicon-m-cog-6-tooth')
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" style="width:24px;height:24px;">
                            <path fill-rule="evenodd" d="M7.84 1.804A1 1 0 018.82 1h2.36a1 1 0 01.98.804l.331 1.652a6.993 6.993 0 011.929 1.115l1.598-.54a1 1 0 011.186.447l1.18 2.044a1 1 0 01-.205 1.251l-1.267 1.113a7.047 7.047 0 010 2.228l1.267 1.113a1 1 0 01.206 1.25l-1.18 2.045a1 1 0 01-1.186.447l-1.598-.54a6.993 6.993 0 01-1.929 1.115l-.33 1.652a1 1 0 01-.98.804H8.82a1 1 0 01-.98-.804l-.331-1.652a6.993 6.993 0 01-1.929-1.115l-1.598.54a1 1 0 01-1.186-.447l-1.18-2.044a1 1 0 01.205-1.251l1.267-1.113a7.047 7.047 0 010-2.228L1.821 6.43a1 1 0 01-.206-1.25l1.18-2.045a1 1 0 011.186-.447l1.598.54a6.993 6.993 0 011.929-1.115l.331-1.652zM10 13a3 3 0 100-6 3 3 0 000 6z" clip-rule="evenodd" />
                        </svg>
                    @elseif($action['icon'] === 'heroicon-m-bell-alert')
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" style="width:24px;height:24px;">
                            <path fill-rule="evenodd" d="M10 2a6 6 0 00-6 6v3.586l-.707.707A1 1 0 004 14h12a1 1 0 00.707-1.707L16 11.586V8a6 6 0 00-6-6zM10 18a3 3 0 01-3-3h6a3 3 0 01-3 3z" clip-rule="evenodd" />
                        </svg>
                    @endif
                </div>
                <span class="quick-action-label">{{ $action['label'] }}</span>
            </a>
        @endforeach
    </div>
</x-filament-widgets::widget>
