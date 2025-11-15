<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Panel;
use Filament\Widgets;
use Filament\Pages;
use Filament\PanelProvider;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Spatie\Permission\Middleware\RoleMiddleware;


class UserPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('user')
            ->path('user')
            ->login()
            ->brandName('LPPM STMIK')
            ->favicon(asset('storage/images/logo-stmik-bandung.png'))
            ->colors([
                'primary' => '#3b82f6',
            ])
            ->discoverResources(
                in: app_path('Filament/User/Resources'),
                for: 'App\\Filament\\User\\Resources'
            )
            ->discoverPages(
                in: app_path('Filament/Pages'),
                for: 'App\\Filament\\Pages'
            )
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(
                in: app_path('Filament/User/Widgets'),
                for: 'App\\Filament\\User\\Widgets'
            )
            ->widgets([
                \App\Filament\User\Widgets\DashboardOverview::class,
                \App\Filament\User\Widgets\ProposalStatusChart::class,
                Widgets\AccountWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
            ])
            ->authGuard('web')
            ->authMiddleware([
                Authenticate::class,
                RoleMiddleware::class . ':mahasiswa|dosen', 
            ]);
    }
}
