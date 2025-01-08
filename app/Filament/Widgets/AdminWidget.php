<?php

namespace App\Filament\Widgets;

use App\Models\Enrollment;
use Filament\Support\Enums\IconPosition;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AdminWidget extends BaseWidget
{
    use InteractsWithPageFilters;

    protected static bool $isLazy = false;

    protected static ?string $pollingInterval = null;

    protected function getStats(): array
    {
        $schoolyearId = $this->filters['schoolyear_id'] ?? null;

        // Convert empty string or 'All' to null
        if ($schoolyearId === '' || $schoolyearId === 'All') {
            $schoolyearId = null;
        }

        $this->cachedStats = null;

        $studentCount = Enrollment::query()
            ->when($schoolyearId, function ($query) use ($schoolyearId) {
                return $query->where('schoolyear_id', $schoolyearId); // Filter by school year if provided
            })
            ->count();

        return [
            Stat::make('Total', $studentCount)
                ->description('No. of students')
                ->descriptionIcon('heroicon-m-user-group', IconPosition::After)
                ->color('warning'),
            Stat::make('Total', Enrollment::countFullyPaidStudents($schoolyearId))
                ->description('No. of students fully paid')
                ->descriptionIcon('heroicon-m-user-group', IconPosition::After)
                ->color('success'),
            Stat::make('Total', Enrollment::countUnpaidStudents($schoolyearId))
                ->description('No. of students not fully paid')
                ->descriptionIcon('heroicon-m-user-group', IconPosition::After)
                ->color('danger'),
            Stat::make('Total', Enrollment::summarizeAmounts($schoolyearId))
                ->description('Expected Collections')
                ->descriptionIcon('heroicon-m-banknotes', IconPosition::After)
                ->color('warning'),
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
