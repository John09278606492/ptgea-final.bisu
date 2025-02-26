<?php

namespace App\Filament\Widgets;

use App\Models\Enrollment;
use Filament\Support\Enums\IconPosition;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class AdminWidget extends BaseWidget
{
    use InteractsWithPageFilters;


    // protected static bool $isLazy = true;

    private function totalUnpaid(): int
    {
        $schoolyearId = $this->filters['schoolyear_id'] ?? null;

        if ($schoolyearId === '' || $schoolyearId === 'All') {
            $schoolyearId = null;
        }

        $totalUnpaidCount = Enrollment::query()
            ->when($schoolyearId, function ($query) use ($schoolyearId) {
                return $query->where('schoolyear_id', $schoolyearId); // Filter by school year if provided
            })
            ->with(['collections', 'yearlevelpayments', 'pays']) // Eager load all related models
            ->get()
            ->filter(function ($enrollment) {
                $collectionsTotal = $enrollment->collections->sum('amount');
                $yearLevelPaymentsTotal = $enrollment->yearlevelpayments->sum('amount');
                $totalPayments = $enrollment->pays->sum('amount');

                $totalDue = $collectionsTotal + $yearLevelPaymentsTotal;
                $remainingBalance = $totalDue - $totalPayments;

                return $remainingBalance > 0; // Keep only those with unpaid balance
            })
            ->count();

        return $totalUnpaidCount;
    }

    private function totalPaid(): int
    {
        $schoolyearId = $this->filters['schoolyear_id'] ?? null;

        if ($schoolyearId === '' || $schoolyearId === 'All') {
            $schoolyearId = null;
        }

        $totalUnpaidCount = Enrollment::query()
            ->when($schoolyearId, function ($query) use ($schoolyearId) {
                return $query->where('schoolyear_id', $schoolyearId); // Filter by school year if provided
            })
            ->with(['collections', 'yearlevelpayments', 'pays']) // Eager load all related models
            ->get()
            ->filter(function ($enrollment) {
                $collectionsTotal = $enrollment->collections->sum('amount');
                $yearLevelPaymentsTotal = $enrollment->yearlevelpayments->sum('amount');
                $totalPayments = $enrollment->pays->sum('amount');

                $totalDue = $collectionsTotal + $yearLevelPaymentsTotal;
                $remainingBalance = $totalDue - $totalPayments;

                return $remainingBalance <= 0; // Keep only those with unpaid balance
            })
            ->count();

        return $totalUnpaidCount;
    }

    private function calculateExpectedCollections(): string
    {
        $schoolyearId = $this->filters['schoolyear_id'] ?? null;

        if ($schoolyearId === '' || $schoolyearId === 'All') {
            $schoolyearId = null;
        }

        $totalAmount = Enrollment::query()
            ->when($schoolyearId, function ($query) use ($schoolyearId) {
                return $query->where('enrollments.schoolyear_id', $schoolyearId); // Filter by school year if provided
            })
            ->leftJoin('collection_enrollment', 'enrollments.id', '=', 'collection_enrollment.enrollment_id')
            ->leftJoin('collections', 'collection_enrollment.collection_id', '=', 'collections.id')
            ->leftJoin('enrollment_yearlevelpayments', 'enrollments.id', '=', 'enrollment_yearlevelpayments.enrollment_id')
            ->leftJoin('yearlevelpayments', 'enrollment_yearlevelpayments.yearlevelpayments_id', '=', 'yearlevelpayments.id')
            ->selectRaw('
                COALESCE(SUM(collections.amount), 0) + COALESCE(SUM(yearlevelpayments.amount), 0) as total
            ')
            ->value('total');

        return '₱' . number_format($totalAmount ?? 0, 2, '.', ',');
    }

    private function caculateTotalPays(): string
    {
        $schoolyearId = $this->filters['schoolyear_id'] ?? null;

        if ($schoolyearId === '' || $schoolyearId === 'All') {
            $schoolyearId = null;
        }

        $totalAmount = Enrollment::query()
            ->when($schoolyearId, function ($query) use ($schoolyearId) {
                return $query->where('schoolyear_id', $schoolyearId); // Filter by school year if provided
            })
            ->with(['pays']) // Eager load all related models
            ->select('enrollments.id') // Fetch only necessary columns
            ->get()
            ->sum(function ($enrollment) {
                $totalPayments = $enrollment->pays->sum('amount');

                return $totalPayments; // Calculate balance
            });

        return '₱' . number_format($totalAmount, 2, '.', ',');
    }

    private function calculateRemainingCollections(): string
    {
        $schoolyearId = $this->filters['schoolyear_id'] ?? null;

        if ($schoolyearId === '' || $schoolyearId === 'All') {
            $schoolyearId = null;
        }

        $totalAmount = Enrollment::query()
            ->when($schoolyearId, function ($query) use ($schoolyearId) {
                return $query->where('schoolyear_id', $schoolyearId); // Filter by school year if provided
            })
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


    // protected static ?string $pollingInterval = null;

    protected function getStats(): array
    {
        $schoolyearId = $this->filters['schoolyear_id'] ?? null;

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
            // Stat::make('Total', $studentCount)
            //     ->description('No. of students')
            //     ->descriptionIcon('heroicon-m-user-group', IconPosition::After)
            //     ->color('warning'),
            // Stat::make('Total', $this->totalPaid())
            //     ->description('No. of students fully paid')
            //     ->descriptionIcon('heroicon-m-user-group', IconPosition::After)
            //     ->color('success'),
            // Stat::make('Total', $this->totalUnpaid())
            //     ->description('No. of students not fully paid')
            //     ->descriptionIcon('heroicon-m-user-group', IconPosition::After)
            //     ->color('danger'),
            Stat::make('Total', $this->calculateExpectedCollections())
                ->description('Expected Collections')
                ->descriptionIcon('heroicon-m-banknotes', IconPosition::After)
                ->color('warning'),
            // Stat::make('Total', $this->caculateTotalPays())
            //     ->description('Collected Amounts')
            //     ->descriptionIcon('heroicon-m-banknotes', IconPosition::After)
            //     ->color('success'),
            // Stat::make('Total', $this->calculateRemainingCollections())
            //     ->description('Remaining Collections')
            //     ->descriptionIcon('heroicon-m-banknotes', IconPosition::After)
            //     ->color('danger'),
        ];
    }
}
