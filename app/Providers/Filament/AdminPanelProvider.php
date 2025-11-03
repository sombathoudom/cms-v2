<?php

namespace App\Providers\Filament;

use Filament\Navigation\MenuItem;
use Filament\Panel;
use Filament\PanelProvider;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->plugins([])
            ->sidebarCollapsibleOnDesktop()
            ->topNavigation(false)
            ->userMenuItems([
                MenuItem::make()
                    ->label('Documentation')
                    ->url('https://filamentphp.com/docs'),
            ])
            ->renderHook('panels::styles.after', fn (): string => '<style>:root { --filament-primary-color: 30 64 175; }</style>');
    }
}
