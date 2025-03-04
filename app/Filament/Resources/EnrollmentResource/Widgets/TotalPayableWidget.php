<?php

namespace App\Filament\Resources\EnrollmentResource\Widgets;

use App\Filament\Resources\EnrollmentResource\Pages\ListEnrollments;
use App\Models\Enrollment;
use Filament\Support\Enums\IconPosition;
use Filament\Widgets\Concerns\InteractsWithPageTable;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class TotalPayableWidget extends BaseWidget
{
    use InteractsWithPageTable;

    // protected static bool $isLazy = true;

    // protected static ?string $pollingInterval = null;

    protected function getTablePage(): string
    {
        return ListEnrollments::class;
    }

    private function calculateExpectedCollections(): string
    {
        // Get expected collections
        $collectionsTotal = $this->getPageTableQuery()
            ->leftJoin('collection_enrollment', 'enrollments.id', '=', 'collection_enrollment.enrollment_id')
            ->leftJoin('collections', 'collection_enrollment.collection_id', '=', 'collections.id')
            ->selectRaw('COALESCE(SUM(collections.amount), 0) as total')
            ->value('total');

        // Get expected yearlevelpayments
        $yearLevelTotal = $this->getPageTableQuery()
            ->leftJoin('enrollment_yearlevelpayments', 'enrollments.id', '=', 'enrollment_yearlevelpayments.enrollment_id')
            ->leftJoin('yearlevelpayments', 'enrollment_yearlevelpayments.yearlevelpayments_id', '=', 'yearlevelpayments.id')
            ->selectRaw('COALESCE(SUM(yearlevelpayments.amount), 0) as total')
            ->value('total');

        // Total expected amount
        $expectedTotal = $collectionsTotal + $yearLevelTotal;

        return '₱' . number_format($expectedTotal, 2, '.', ',');
    }

    private function calculateCollectedAmounts(): string
    {
        $totalAmount = $this->getPageTableQuery()
            ->leftJoin('pays', 'enrollments.id', '=', 'pays.enrollment_id')
            ->selectRaw('COALESCE(SUM(pays.amount), 0) as total')
            ->value('total');

        return '₱' . number_format($totalAmount ?? 0, 2, '.', ',');
    }

    private function calculateRemainingCollections(): string
    {
        // Get expected collections
        $collectionsTotal = $this->getPageTableQuery()
            ->leftJoin('collection_enrollment', 'enrollments.id', '=', 'collection_enrollment.enrollment_id')
            ->leftJoin('collections', 'collection_enrollment.collection_id', '=', 'collections.id')
            ->selectRaw('COALESCE(SUM(collections.amount), 0) as total')
            ->value('total');

        // Get expected yearlevelpayments
        $yearLevelTotal = $this->getPageTableQuery()
            ->leftJoin('enrollment_yearlevelpayments', 'enrollments.id', '=', 'enrollment_yearlevelpayments.enrollment_id')
            ->leftJoin('yearlevelpayments', 'enrollment_yearlevelpayments.yearlevelpayments_id', '=', 'yearlevelpayments.id')
            ->selectRaw('COALESCE(SUM(yearlevelpayments.amount), 0) as total')
            ->value('total');

        // Total expected amount
        $expectedTotal = $collectionsTotal + $yearLevelTotal;

        // Get collected payments
        $paidTotal = $this->getPageTableQuery()
            ->leftJoin('pays', 'enrollments.id', '=', 'pays.enrollment_id')
            ->selectRaw('COALESCE(SUM(pays.amount), 0) as total')
            ->value('total');

        // Ensure no negative values
        $remaining = max(0, $expectedTotal - $paidTotal);

        return '₱' . number_format($remaining, 2, '.', ',');
    }

    protected function getStats(): array
    {
        return [
            Stat::make('Total', $this->calculateExpectedCollections())
                ->description('Expected Collections')
                ->descriptionIcon('heroicon-m-banknotes', IconPosition::After)
                ->color('primary'),
            Stat::make('Total', $this->calculateCollectedAmounts())
                ->description('Collected Amount')
                ->descriptionIcon('heroicon-m-banknotes', IconPosition::After)
                ->color('success'),
            Stat::make('Total', $this->calculateRemainingCollections())
                ->description('Remaining Collections')
                ->descriptionIcon('heroicon-m-banknotes', IconPosition::After)
                ->color('danger'),
            // Stat::make('Total', Enrollment::summarizeAmounts())
            //     ->description('Amount Paid')
            //     ->descriptionIcon('heroicon-m-banknotes', IconPosition::Before)
            //     ->color('success'),
        ];
    }
}
