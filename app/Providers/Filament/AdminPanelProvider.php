<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use App\Http\Middleware\DivisionActivaAdmin;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
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
            ->navigationGroups([
                'Comercial',
                'Clientes y equipos',
                'Operación',
                'Finanzas',
                'Inventario',
                'Documentos',
                'Tarifario',
                'Sitio web',
                'Configuración',
                'Sistema',
            ])
            ->databaseNotifications()
            ->databaseNotificationsPolling('5s')
            ->domain(\App\Support\Dominios::de('admin'))
            ->path(\App\Support\Dominios::de('admin') ? '' : 'admin')
            ->login(\App\Filament\Pages\Auth\Login::class)
            ->passwordReset()
            ->renderHook(\Filament\View\PanelsRenderHook::AUTH_LOGIN_FORM_AFTER, fn () => view('filament.recaptcha'))
            ->renderHook(\Filament\View\PanelsRenderHook::CONTENT_START, fn () => view('filament.selector-division'))
            ->renderHook(\Filament\View\PanelsRenderHook::CONTENT_START, fn () => view('filament.aviso-suplantacion'))
            ->colors([
                'primary' => \App\Support\Marca::primario(),
            ])
            ->brandName(\App\Support\Marca::nombre())
            ->profile(\App\Filament\Pages\Auth\EditarPerfil::class)
            ->brandLogo(\App\Support\Marca::logoClaro())
            ->darkModeBrandLogo(\App\Support\Marca::logoOscuro())
            ->brandLogoHeight(\App\Support\Marca::alturaLogo())
            ->favicon(\App\Support\Marca::favicon())
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                AccountWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
                DivisionActivaAdmin::class,
                \App\Http\Middleware\ForzarCambioPassword::class,
            ]);
    }
}
