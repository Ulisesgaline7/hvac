<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        \Filament\Support\Facades\FilamentTimezone::set('America/Tegucigalpa');

        \Filament\Support\Facades\FilamentView::registerRenderHook(
            \Filament\View\PanelsRenderHook::HEAD_END,
            fn () => '<style>
                .fi-no-notification-unread{background:rgb(254 243 199 / .7) !important;border-left:4px solid rgb(245 158 11) !important;font-weight:600}
                .dark .fi-no-notification-unread{background:rgb(245 158 11 / .18) !important}
            </style>'
        );
    }
}
