<?php

namespace App\Filament\Pages;

use App\Models\Schoolyear;
use Carbon\Carbon;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;

class Dashboard extends \Filament\Pages\Dashboard
{
    use HasFiltersForm;

    public function filtersForm(Form $form): Form
    {
        $today = Carbon::today(); // Get today's date
        $defaultSchoolYearId = Schoolyear::where('startDate', '<=', $today)
            ->where('endDate', '>=', $today)
            ->value('id'); // Get the school year ID where today falls between start and end dates

        return $form
            ->schema([
                Section::make()
                    ->schema([
                        Select::make('schoolyear_id')
                            ->inlineLabel(false)
                            ->label('School Year')
                            ->prefixIcon('heroicon-m-funnel')
                            ->selectablePlaceholder(false)
                            ->default($defaultSchoolYearId ?? 'All') // Use the found school year ID or default to 'All'
                            ->options(['All' => 'All'] + Schoolyear::all()->pluck('schoolyear', 'id')->toArray())
                            ->nullable()
                            ->columnSpanFull(), // Allow null value
                    ]),
            ]);
    }
}
