<?php

namespace App\Filament\Widgets;

use App\Models\Enrollment;
use App\Models\Stud;
use Filament\Support\Enums\IconPosition;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AdminWidget extends BaseWidget
{
    use InteractsWithPageFilters;

    protected function getStats(): array
    {
        $schoolyearId = $this->filters['schoolyear_id'] ?? null;

        // Convert empty string or 'All' to null
        if ($schoolyearId === '' || $schoolyearId === 'All') {
            $schoolyearId = null;
        }

        return [
            Stat::make('Total', Stud::countBySchoolYear($schoolyearId))
                ->description('No. of students')
                ->descriptionIcon('heroicon-m-user-group', IconPosition::After)
                ->color('warning'),
            Stat::make('Total', Enrollment::summarizeAmounts($schoolyearId))
                ->description('Expected Collections')
                ->descriptionIcon('heroicon-m-banknotes', IconPosition::After)
                ->color('primary'),
            Stat::make('Total', Enrollment::summarizePaysAmount($schoolyearId))
                ->description('Collected Amounts')
                ->descriptionIcon('heroicon-m-banknotes', IconPosition::After)
                ->color('success'),
            Stat::make('Total', Enrollment::summarizeBalance($schoolyearId))
                ->description('Remaining Collections')
                ->descriptionIcon('heroicon-m-banknotes', IconPosition::After)
                ->color('danger'),
        ];

    }
}
