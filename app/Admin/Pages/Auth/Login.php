<?php

namespace App\Admin\Pages\Auth;

use Filament\Auth\Pages\Login as BaseLogin;
use Illuminate\Contracts\Support\Htmlable;

class Login extends BaseLogin
{
    protected string $view = 'filament.pages.auth.login';

    protected \Filament\Support\Enums\Width|string|null $maxWidth = null;

    public function getTitle(): string|Htmlable
    {
        return 'AI智能菌菇方舱管理系统 - 登录';
    }

    public function getHeading(): string|Htmlable
    {
        if (filled($this->userUndertakingMultiFactorAuthentication)) {
            return parent::getHeading();
        }

        return '欢迎回来';
    }

    public function getSubheading(): string|Htmlable|null
    {
        if (filled($this->userUndertakingMultiFactorAuthentication)) {
            return parent::getSubheading();
        }

        return '登录到您的账户，开启智能管理';
    }
}
