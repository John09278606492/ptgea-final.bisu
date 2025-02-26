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
        $totalAmount = $this->getPageTableQuery()
            ->with(['collections', 'yearlevelpayments']) // Eager load related models
            ->select('enrollments.id') // Fetch only necessary columns
            ->get()
            ->sum(function ($enrollment) {
                $collectionsTotal = $enrollment->collections->sum('amount');
                $yearlevelPaymentsTotal = $enrollment->yearlevelpayments->sum('amount');

                return $collectionsTotal + $yearlevelPaymentsTotal;
            });

        return '₱' . number_format($totalAmount, 2, '.', ',');
    }

    private function calculateCollectedAmounts(): string
    {
        $totalAmount = $this->getPageTableQuery()
            ->with('pays') // Eager load the pays relationship
            ->select('enrollments.id') // Fetch only necessary columns
            ->get()
            ->sum(function ($enrollment) {
                return $enrollment->pays->sum('amount');
            });

        return '₱' . number_format($totalAmount, 2, '.', ',');
    }

    private function calculateRemainingCollections(): string
    {
        $totalAmount = $this->getPageTableQuery()
            ->with(['collections', 'yearlevelpayments', 'pays']) // Eager load all related models
            ->select('enrollments.id') // Fetch only necessary columns
            ->get()
            ->sum(function ($enrollment) {
                $collectionsTotal = $enrollment->collections->sum('amount');
                $yearlevelPaymentsTotal = $enrollment->yearlevelpayments->sum('amount');
                $totalPayments = $enrollment->pays->sum('amount');

                $totals = $collectionsTotal + $yearlevelPaymentsTotal;

                return $totals - $totalPayments; // Calculate balance
            });

        return '₱' . number_format($totalAmount, 2, '.', ',');
    }

    private function calculateRemainingCollectionsUsingQuery(): string
    {
        $totalAmount = $this->getPageTableQuery()
            ->join('collections', 'enrollments.id', '=', 'collections.enrollment_id')
            ->join('yearlevelpayments', 'enrollments.id', '=', 'yearlevelpayments.enrollment_id')
            ->join('pays', 'enrollments.id', '=', 'pays.enrollment_id')
            ->sum(DB::raw('COALESCE(collections.amount, 0) + COALESCE(yearlevelpayments.amount, 0) - COALESCE(pays.amount, 0)'));

        return '₱' . number_format($totalAmount, 2, '.', ',');
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
