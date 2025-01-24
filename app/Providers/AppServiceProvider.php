<?php

namespace App\Providers;

use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\Section as ComponentsSection;
use Filament\Infolists\Components\TextEntry;
use Filament\Support\Facades\FilamentView;
use Filament\Tables\Actions\CreateAction as ActionsCreateAction;
use Filament\View\PanelsRenderHook;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
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
        FilamentView::registerRenderHook(
            PanelsRenderHook::FOOTER,
            fn (): View => view('footer'),
        );
        FilamentView::registerRenderHook(
            PanelsRenderHook::AUTH_LOGIN_FORM_BEFORE,
            fn (): View => view('login_button'),
        );
        FilamentView::registerRenderHook(
            PanelsRenderHook::AUTH_LOGIN_FORM_BEFORE,
            fn (): View => view('title'),
        );
        Gate::before(function ($user) {
            // This ensures that the user model is correctly loaded
        });
        Action::configureUsing(function (Action $action) {
            if ($action->getName() === 'save') {
                // Configure the "save" action
                $action
                    ->label(__('Update'))
                    ->submit('save')
                    ->icon('heroicon-m-check-circle')
                    ->keyBindings(['mod+s']);
            } elseif ($action->getName() === 'cancel') {
                // Configure the "cancel" action
                $action
                    ->color('secondary')
                    ->label(__('Close'))
                    ->icon('heroicon-m-x-circle');
            } elseif ($action->getName() === 'create') {
                // Configure the "cancel" action
                $action
                    ->keyBindings(['mod+shift+s'])
                    ->icon('heroicon-m-check-circle');
            }
        });

        ActionsCreateAction::configureUsing(function (ActionsCreateAction $createAction) {
            $createAction
                ->icon('heroicon-m-plus-circle');
        });
        CreateAction::configureUsing(function (CreateAction $createAction) {
            $createAction
                ->icon('heroicon-m-plus-circle');
        });
        TextInput::configureUsing(function (TextInput $textInput) {
            // $textInput->inlineLabel();
        });

        // TextEntry::configureUsing(function (TextEntry $textEntry) {
        //     $textEntry
        //         ->inlineLabel();
        // });

        ComponentsSection::configureUsing(function (ComponentsSection $section) {
            $section
                ->columns()
                ->compact();
        });

        // Repeater::configureUsing(function (Repeater $repeater) {
        //     $repeater->inlineLabel();
        // });

        // DatePicker::configureUsing(function (DatePicker $datePicker) {
        //     $datePicker->inlineLabel();
        // });

        // DateTimePicker::configureUsing(function (DateTimePicker $dateTimePicker) {
        //     $dateTimePicker->inlineLabel();
        // });

        // Select::configureUsing(function (Select $select) {
        //     $select->inlineLabel();
        // });

        Section::configureUsing(function (Section $section) {
            $section
                ->columns()
                ->compact();
        });

    }
}
