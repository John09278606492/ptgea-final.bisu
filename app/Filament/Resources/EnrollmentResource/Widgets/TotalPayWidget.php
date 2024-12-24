<?php

namespace App\Filament\Resources\EnrollmentResource\Widgets;

use App\Models\Enrollment;
use Filament\Support\Enums\IconPosition;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class TotalPayWidget extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            // Stat::make('Total', Enrollment::totalPays())
            //     ->description('Amount Paid')
            //     ->descriptionIcon('heroicon-m-banknotes', IconPosition::Before)
            //     ->color('success'),
        ];
    }
}
