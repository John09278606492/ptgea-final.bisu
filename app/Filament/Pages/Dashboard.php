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

    protected ?string $maxContentWidth = 'full';

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()->role === 'admin';
    }

    public static function canAccess(): bool
    {
        return auth()->user()->role === 'admin';
    }

    public function filtersForm(Form $form): Form
    {
        $today = Carbon::today();
        $defaultSchoolYearId = Schoolyear::where('startDate', '<=', $today)
            ->where('endDate', '>=', $today)
            ->value('id');

        return $form
            ->schema([
                Section::make()
                    ->schema([
                        Select::make('schoolyear_id')
                            ->inlineLabel(false)
                            ->label('School Year')
                            ->prefixIcon('heroicon-m-funnel')
                            ->selectablePlaceholder(false)
                            ->default($defaultSchoolYearId ?? 'All')
                            ->options(['All' => 'All'] + Schoolyear::all()->pluck('schoolyear', 'id')->toArray())
                            ->nullable()
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
