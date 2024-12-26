<?php

namespace App\Filament\Resources\EnrollmentResource\Widgets;

use App\Filament\Resources\EnrollmentResource\Pages\ListEnrollments;
use App\Models\Enrollment;
use Filament\Support\Enums\IconPosition;
use Filament\Widgets\Concerns\InteractsWithPageTable;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class TotalPayableWidget extends BaseWidget
{
    use InteractsWithPageTable;

    protected function getTablePage(): string
    {
        return ListEnrollments::class;
    }

    protected function getStats(): array
    {
        return [
            Stat::make('Total', function () {
                $totalAmount = $this->getPageTableRecords()
                    ->sum(function ($enrollment) {
                        $collectionsTotal = $enrollment->collections->sum('amount');
                        $yearlevelPaymentsTotal = $enrollment->yearlevelpayments->sum('amount');

                        return $collectionsTotal + $yearlevelPaymentsTotal;
                    });

                return '₱'.number_format($totalAmount, 2, '.', ',');
            })
                ->description('Expected Collections')
                ->descriptionIcon('heroicon-m-banknotes', IconPosition::Before)
                ->color('warning'),
            Stat::make('Total', function () {
                $totalAmount = $this->getPageTableRecords()
                    ->sum(function ($enrollment) {
                        $totalPayments = $enrollment->pays->sum('amount');

                        return $totalPayments;
                    });

                return '₱'.number_format($totalAmount, 2, '.', ',');
            })
                ->description('Collected Amount')
                ->descriptionIcon('heroicon-m-banknotes', IconPosition::Before)
                ->color('success'),
            Stat::make('Total', function () {
                $totalAmount = $this->getPageTableRecords()
                    ->sum(function ($enrollment) {
                        $collectionsTotal = $enrollment->collections->sum('amount');
                        $yearlevelPaymentsTotal = $enrollment->yearlevelpayments->sum('amount');
                        $totalPayments = $enrollment->pays->sum('amount');

                        $totals = $collectionsTotal + $yearlevelPaymentsTotal;
                        $totaBalance = $totals - $totalPayments;

                        return $totaBalance;
                    });

                return '₱'.number_format($totalAmount, 2, '.', ',');
            })
                ->description('Remaining Collections')
                ->descriptionIcon('heroicon-m-banknotes', IconPosition::Before)
                ->color('danger'),
            // Stat::make('Total', Enrollment::summarizeAmounts())
            //     ->description('Amount Paid')
            //     ->descriptionIcon('heroicon-m-banknotes', IconPosition::Before)
            //     ->color('success'),
        ];
    }
}
