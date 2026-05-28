@push('styles')
<link rel="stylesheet" href="{{ asset('css/login.css') }}">
@endpush

<div class="login-container">
    <!-- 左侧：AI视觉展示区 -->
    <div class="login-visual">
        <canvas id="particle-canvas"></canvas>
        
        <!-- 数据流动光束 -->
        <div class="data-flow-container">
            <div class="data-flow-line" style="--delay: 0s; --duration: 4s; --top: 20%;"></div>
            <div class="data-flow-line" style="--delay: 1s; --duration: 5s; --top: 40%;"></div>
            <div class="data-flow-line" style="--delay: 2s; --duration: 3.5s; --top: 60%;"></div>
            <div class="data-flow-line" style="--delay: 0.5s; --duration: 4.5s; --top: 80%;"></div>
        </div>
        
        <!-- 品牌信息 -->
        <div class="brand-info">
            <div class="brand-logo">
                <svg viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <rect x="8" y="8" width="48" height="48" rx="12" fill="#4ade80" opacity="0.2"/>
                    <rect x="16" y="16" width="32" height="32" rx="8" fill="#4ade80" opacity="0.4"/>
                    <circle cx="32" cy="32" r="8" fill="#4ade80"/>
                    <line x1="32" y1="8" x2="32" y2="24" stroke="#4ade80" stroke-width="2" opacity="0.5"/>
                    <line x1="32" y1="40" x2="32" y2="56" stroke="#4ade80" stroke-width="2" opacity="0.5"/>
                    <line x1="8" y1="32" x2="24" y2="32" stroke="#4ade80" stroke-width="2" opacity="0.5"/>
                    <line x1="40" y1="32" x2="56" y2="32" stroke="#4ade80" stroke-width="2" opacity="0.5"/>
                </svg>
            </div>
            <h1 class="brand-title">AI智能菌菇方舱管理系统</h1>
            <p class="brand-slogan">智能感知 · 精准控制 · 高效培育</p>
        </div>
    </div>
    
    <!-- 右侧：登录表单区 -->
    <div class="login-form-section">
        <div class="login-form-card">
            <div class="form-header">
                <h2 class="form-heading">{{ $this->getHeading() }}</h2>
                <p class="form-subheading">{{ $this->getSubheading() }}</p>
            </div>
            
            <form wire:submit="authenticate">
                @csrf
                
                <!-- 邮箱输入框 -->
                <div class="form-group">
                    <label for="email" class="form-label">邮箱</label>
                    <div class="input-wrapper">
                        <svg class="input-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" />
                        </svg>
                        <input
                            type="email"
                            id="email"
                            wire:model="data.email"
                            class="form-input"
                            placeholder="请输入邮箱地址"
                            required
                            autofocus
                            autocomplete="email"
                        >
                    </div>
                    @error('data.email')
                        <span class="error-message">{{ $message }}</span>
                    @enderror
                </div>
                
                <!-- 密码输入框 -->
                <div class="form-group">
                    <div class="label-row">
                        <label for="password" class="form-label">密码</label>
                        @if (filament()->hasPasswordReset())
                            <a href="{{ filament()->getRequestPasswordResetUrl() }}" class="forgot-link">忘记密码？</a>
                        @endif
                    </div>
                    <div class="input-wrapper">
                        <svg class="input-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                        </svg>
                        <input
                            type="password"
                            id="password"
                            wire:model="data.password"
                            class="form-input"
                            placeholder="请输入密码"
                            required
                            autocomplete="current-password"
                        >
                    </div>
                    @error('data.password')
                        <span class="error-message">{{ $message }}</span>
                    @enderror
                </div>
                
                <!-- 记住我 -->
                <div class="form-group checkbox-group">
                    <label class="checkbox-label">
                        <input
                            type="checkbox"
                            wire:model="data.remember"
                            class="form-checkbox"
                        >
                        <span class="checkbox-text">记住我</span>
                    </label>
                </div>
                
                <!-- 登录按钮 -->
                <button type="submit" class="login-button" wire:loading.attr="disabled">
                    <span wire:loading.remove>登录</span>
                    <span wire:loading>登录中...</span>
                </button>
            </form>
            
            @if (filament()->hasRegistration())
                <div class="form-footer">
                    <span>还没有账户？</span>
                    <a href="{{ filament()->getRegistrationUrl() }}" class="register-link">立即注册</a>
                </div>
            @endif
            
            <div class="copyright">
                © {{ date('Y') }} AI智能菌菇方舱管理系统
            </div>
        </div>
    </div>
    
    <script>
        {!! file_get_contents(public_path('js/login/particles.js')) !!}
    </script>
</div>