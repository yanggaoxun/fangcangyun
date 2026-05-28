<?php

namespace App\Providers\Filament;

use App\Admin\Pages\Auth\Login;
use App\Admin\Pages\Dashboard;
use App\Admin\Pages\Profile;
use App\Admin\Resources\System\Users\PermissionResource;
use App\Admin\Resources\System\Users\RoleResource;
use App\Admin\Resources\System\Users\UserResource;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\MenuItem;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login(Login::class)
            ->darkMode(false)
            ->colors([
                'primary' => Color::Amber,
            ])
            ->sidebarCollapsibleOnDesktop()
            ->sidebarWidth('12rem')
            ->navigationGroups([
                '方舱管理',
                '菌菇管理',
                '设备管理',
                '系统管理',
            ])
            ->breadcrumbs(false)
            ->discoverResources(in: app_path('Admin/Resources'), for: 'App\Admin\Resources')
            ->resources([
                UserResource::class,
                RoleResource::class,
                PermissionResource::class,
            ])
            ->discoverPages(in: app_path('Admin/Pages'), for: 'App\Admin\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Admin/Widgets'), for: 'App\Admin\Widgets')
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->userMenuItems([
                MenuItem::make()
                    ->label('个人设置')
                    ->icon('heroicon-o-user')
                    ->url(fn () => Profile::getUrl()),
            ])
            ->renderHook(
                'panels::topbar.logo.after',
                fn () => view('filament.components.dashboard-top-nav'),
            )
            ->assets([
                \Filament\Support\Assets\Css::make('custom', __DIR__.'/../../../resources/css/filament/admin/custom.css'),
            ]);
    }
}
