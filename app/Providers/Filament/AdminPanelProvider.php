<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Auth\CustomLogin;
use App\Filament\Pages\Auth\Login;
use App\Filament\Widgets\AdminWidget;
use App\Filament\Widgets\CollegeWidget;
use App\Filament\Widgets\ProgramWidget;
use App\Http\Middleware\EnsureLoginRole;
use Filament\Enums\ThemeMode;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Notifications\Notification;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use DutchCodingCompany\FilamentSocialite\FilamentSocialitePlugin;
use DutchCodingCompany\FilamentSocialite\Provider;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->databaseNotifications()
            ->default()
            ->id('admin')
            ->path('admin')
            ->plugin(
                FilamentSocialitePlugin::make()
                    // (required) Add providers corresponding with providers in `config/services.php`.
                    ->providers([
                        // Create a provider 'gitlab' corresponding to the Socialite driver with the same name.
                        Provider::make('google')
                            ->label('Google')
                            ->icon('fab-google')
                            ->color(Color::hex('#4285F4'))
                            ->outlined(false)
                            ->stateless(false)
                        // ->scopes(['...'])
                        // ->with(['...']),
                    ])
                    ->registration(true)
            )
            // ->brandLogo(asset('images/ptgea_logo.png'))
            // ->brandLogo(fn () => view('brandname'))
            // ->brandLogoHeight('3rem')
            ->login(Login::class)
            ->favicon(asset('images/bisu_logo.png'))
            ->defaultThemeMode(ThemeMode::Light)
            ->maxContentWidth(MaxWidth::Full)
            ->colors([
                'cyan' => Color::Cyan,         // Stays the same for a neutral/calm tone
                'danger' => Color::Red,        // Red is more universally recognized for danger
                'gray' => Color::Zinc,         // Zinc is a more modern gray shade
                'info' => Color::Sky,          // Sky Blue is softer and fits information purposes
                'primary' => Color::Indigo,      // Blue is commonly used as a primary action color
                'success' => Color::Green,     // Green is more recognizable for success than Emerald
                'warning' => Color::Amber,     // Amber is a stronger warning color than Orange
            ])

            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                // Widgets\AccountWidget::class,
                // Widgets\FilamentInfoWidget::class,
                AdminWidget::class,
                CollegeWidget::class,
                ProgramWidget::class,
            ])
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
                // EnsureLoginRole::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->sidebarCollapsibleOnDesktop()
            ->passwordReset()
            ->emailVerification();
    }
}
