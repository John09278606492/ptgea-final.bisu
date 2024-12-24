<?php

namespace App\Providers;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\Section as ComponentsSection;
use Filament\Infolists\Components\TextEntry;
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
        TextInput::configureUsing(function (TextInput $textInput) {
            $textInput->inlineLabel();
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

        DatePicker::configureUsing(function (DatePicker $datePicker) {
            $datePicker->inlineLabel();
        });

        Select::configureUsing(function (Select $select) {
            $select->inlineLabel();
        });

        Section::configureUsing(function (Section $section) {
            $section
                ->columns()
                ->compact();
        });
    }
}
