<?php

namespace App\Providers\Filament;

use App\Http\Middleware\ActiveAccount;
use App\Http\Middleware\Administrator;
use App\Http\Middleware\FilamentAuthenticate;
use Filament\FontProviders\LocalFontProvider;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\MenuItem;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Auth\Middleware\EnsureEmailIsVerified;
use Illuminate\Auth\Middleware\RequirePassword;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel->default()->id('admin')->path('admin')->authGuard('web')
            ->defaultAvatarProvider(\App\Filament\Support\LocalAvatarProvider::class)
            ->brandName(fn () => config('platform.organization').' · Teaching desk')
            ->brandLogo(fn () => view('filament.brand'))->brandLogoHeight('2.2rem')->favicon(asset('favicon.svg'))
            ->font('Studio', url: asset('admin-studio.css').'?v='.filemtime(public_path('admin-studio.css')), provider: LocalFontProvider::class)
            ->navigationGroups(['Curriculum', 'Student records', 'Operations'])
            ->renderHook(\Filament\View\PanelsRenderHook::HEAD_START, fn () => view('shared.theme'))
            ->renderHook(\Filament\View\PanelsRenderHook::HEAD_END, fn () => view('filament.head'))
            ->colors(['primary' => Color::hex('#2448D8')])->sidebarCollapsibleOnDesktop()->maxContentWidth('7xl')
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->userMenuItems([
                'logout' => MenuItem::make()->label('Log out')->url('/logout'),
                MenuItem::make()->label('Student view')->url('/dashboard')->icon('heroicon-o-academic-cap'),
                MenuItem::make()->label('Account & authenticator')->url('/settings/security')->icon('heroicon-o-lock-closed'),
            ])
            ->middleware(['web', DisableBladeIconComponents::class, DispatchServingFilamentEvent::class])
            ->authMiddleware(['auth', EnsureEmailIsVerified::class, Administrator::class, RequirePassword::class, FilamentAuthenticate::class, ActiveAccount::class], isPersistent: true);
    }
}
