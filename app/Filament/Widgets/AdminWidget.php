<?php

namespace App\Filament\Widgets;

use App\Models\Enrollment;
use App\Models\Yearlevelpayments;
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

        // Get total collections amount
        $collectionsTotal = Enrollment::query()
            ->when($schoolyearId, function ($query) use ($schoolyearId) {
                return $query->where('enrollments.schoolyear_id', $schoolyearId);
            })
            ->leftJoin('collection_enrollment', 'enrollments.id', '=', 'collection_enrollment.enrollment_id')
            ->leftJoin('collections', 'collection_enrollment.collection_id', '=', 'collections.id')
            ->selectRaw('enrollments.id, COALESCE(SUM(collections.amount), 0) as total_collections')
            ->groupBy('enrollments.id');

        // Get total year level payments (without duplication)
        $yearLevelTotal = Enrollment::query()
            ->when($schoolyearId, function ($query) use ($schoolyearId) {
                return $query->where('enrollments.schoolyear_id', $schoolyearId);
            })
            ->selectRaw('enrollments.id, (
            SELECT COALESCE(SUM(yearlevelpayments.amount), 0)
            FROM yearlevelpayments
            JOIN enrollment_yearlevelpayments ON yearlevelpayments.id = enrollment_yearlevelpayments.yearlevelpayments_id
            WHERE enrollment_yearlevelpayments.enrollment_id = enrollments.id
        ) as total_yearlevel')
            ->groupBy('enrollments.id');

        // Get total paid amount
        $paidTotal = Enrollment::query()
            ->when($schoolyearId, function ($query) use ($schoolyearId) {
                return $query->where('enrollments.schoolyear_id', $schoolyearId);
            })
            ->leftJoin('pays', 'enrollments.id', '=', 'pays.enrollment_id')
            ->selectRaw('enrollments.id, COALESCE(SUM(pays.amount), 0) as total_paid')
            ->groupBy('enrollments.id');

        // Combine all three calculations and count unpaid enrollments
        $totalUnpaidCount = Enrollment::query()
            ->when($schoolyearId, function ($query) use ($schoolyearId) {
                return $query->where('enrollments.schoolyear_id', $schoolyearId);
            })
            ->joinSub($collectionsTotal, 'collections', 'enrollments.id', '=', 'collections.id')
            ->joinSub($yearLevelTotal, 'yearlevels', 'enrollments.id', '=', 'yearlevels.id')
            ->joinSub($paidTotal, 'payments', 'enrollments.id', '=', 'payments.id')
            ->selectRaw('
            enrollments.id,
            (collections.total_collections + yearlevels.total_yearlevel) - payments.total_paid as remaining_balance
        ')
            ->havingRaw('remaining_balance > 0') // Only unpaid enrollments
            ->count();

        return $totalUnpaidCount;
    }

    private function totalPaid(): int
    {
        $schoolyearId = $this->filters['schoolyear_id'] ?? null;

        if ($schoolyearId === '' || $schoolyearId === 'All') {
            $schoolyearId = null;
        }

        // Get total collections amount
        $collectionsTotal = Enrollment::query()
            ->when($schoolyearId, function ($query) use ($schoolyearId) {
                return $query->where('enrollments.schoolyear_id', $schoolyearId);
            })
            ->leftJoin('collection_enrollment', 'enrollments.id', '=', 'collection_enrollment.enrollment_id')
            ->leftJoin('collections', 'collection_enrollment.collection_id', '=', 'collections.id')
            ->selectRaw('enrollments.id, COALESCE(SUM(collections.amount), 0) as total_collections')
            ->groupBy('enrollments.id');

        // Get total year level payments (without duplication)
        $yearLevelTotal = Enrollment::query()
            ->when($schoolyearId, function ($query) use ($schoolyearId) {
                return $query->where('enrollments.schoolyear_id', $schoolyearId);
            })
            ->selectRaw('enrollments.id, (
            SELECT COALESCE(SUM(yearlevelpayments.amount), 0)
            FROM yearlevelpayments
            JOIN enrollment_yearlevelpayments ON yearlevelpayments.id = enrollment_yearlevelpayments.yearlevelpayments_id
            WHERE enrollment_yearlevelpayments.enrollment_id = enrollments.id
        ) as total_yearlevel')
            ->groupBy('enrollments.id');

        // Get total paid amount
        $paidTotal = Enrollment::query()
            ->when($schoolyearId, function ($query) use ($schoolyearId) {
                return $query->where('enrollments.schoolyear_id', $schoolyearId);
            })
            ->leftJoin('pays', 'enrollments.id', '=', 'pays.enrollment_id')
            ->selectRaw('enrollments.id, COALESCE(SUM(pays.amount), 0) as total_paid')
            ->groupBy('enrollments.id');

        // Combine all three calculations and count fully paid enrollments
        $totalPaidCount = Enrollment::query()
            ->when($schoolyearId, function ($query) use ($schoolyearId) {
                return $query->where('enrollments.schoolyear_id', $schoolyearId);
            })
            ->joinSub($collectionsTotal, 'collections', 'enrollments.id', '=', 'collections.id')
            ->joinSub($yearLevelTotal, 'yearlevels', 'enrollments.id', '=', 'yearlevels.id')
            ->joinSub($paidTotal, 'payments', 'enrollments.id', '=', 'payments.id')
            ->selectRaw('
            enrollments.id,
            (collections.total_collections + yearlevels.total_yearlevel) - payments.total_paid as remaining_balance
        ')
            ->havingRaw('remaining_balance <= 0') // Fully paid enrollments
            ->count();

        return $totalPaidCount;
    }


    private function calculateExpectedCollections(): string
    {
        $schoolyearId = $this->filters['schoolyear_id'] ?? null;

        if ($schoolyearId === '' || $schoolyearId === 'All') {
            $schoolyearId = null;
        }

        // Query for collections total (excluding yearlevelpayments)
        $collectionsTotal = Enrollment::query()
            ->when($schoolyearId, function ($query) use ($schoolyearId) {
                return $query->where('enrollments.schoolyear_id', $schoolyearId);
            })
            ->leftJoin('collection_enrollment', 'enrollments.id', '=', 'collection_enrollment.enrollment_id')
            ->leftJoin('collections', 'collection_enrollment.collection_id', '=', 'collections.id')
            ->selectRaw('COALESCE(SUM(collections.amount), 0) as total')
            ->value('total');

        // Query for yearlevelpayments separately to avoid duplicates
        $yearLevelTotal = YearLevelPayments::query()
            ->whereHas('enrollments', function ($query) use ($schoolyearId) {
                if ($schoolyearId) {
                    $query->where('schoolyear_id', $schoolyearId);
                }
            })
            ->selectRaw('COALESCE(SUM(amount), 0) as total')
            ->value('total');

        // Calculate total expected collections
        $totalAmount = $collectionsTotal + $yearLevelTotal;

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
                return $query->where('enrollments.schoolyear_id', $schoolyearId); // Filter by school year if provided
            })
            ->leftJoin('pays', 'enrollments.id', '=', 'pays.enrollment_id')
            ->selectRaw('COALESCE(SUM(pays.amount), 0) as total')
            ->value('total');

        return '₱' . number_format($totalAmount ?? 0, 2, '.', ',');
    }

    private function calculateRemainingCollections(): string
    {
        $schoolyearId = $this->filters['schoolyear_id'] ?? null;

        if ($schoolyearId === '' || $schoolyearId === 'All') {
            $schoolyearId = null;
        }

        // Query for expected collections (excluding yearlevelpayments)
        $collectionsTotal = Enrollment::query()
            ->when($schoolyearId, function ($query) use ($schoolyearId) {
                return $query->where('enrollments.schoolyear_id', $schoolyearId);
            })
            ->leftJoin('collection_enrollment', 'enrollments.id', '=', 'collection_enrollment.enrollment_id')
            ->leftJoin('collections', 'collection_enrollment.collection_id', '=', 'collections.id')
            ->selectRaw('COALESCE(SUM(collections.amount), 0) as total')
            ->value('total');

        // Query for yearlevelpayments separately to avoid duplicate calculations
        $yearLevelTotal = Yearlevelpayments::query()
            ->whereHas('enrollments', function ($query) use ($schoolyearId) {
                if ($schoolyearId) {
                    $query->where('schoolyear_id', $schoolyearId);
                }
            })
            ->selectRaw('COALESCE(SUM(amount), 0) as total')
            ->value('total');

        // Query for collected payments
        $paidTotal = Enrollment::query()
            ->when($schoolyearId, function ($query) use ($schoolyearId) {
                return $query->where('enrollments.schoolyear_id', $schoolyearId);
            })
            ->leftJoin('pays', 'enrollments.id', '=', 'pays.enrollment_id')
            ->selectRaw('COALESCE(SUM(pays.amount), 0) as total')
            ->value('total');

        // Calculate expected total
        $expectedTotal = $collectionsTotal + $yearLevelTotal;

        // Calculate remaining collections
        $remaining = max(0, $expectedTotal - $paidTotal);

        return '₱' . number_format($remaining, 2, '.', ',');
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
            Stat::make('Total', $studentCount)
                ->description('No. of students')
                ->descriptionIcon('heroicon-m-user-group', IconPosition::After)
                ->color('warning'),
            Stat::make('Total', $this->totalPaid())
                ->description('No. of students fully paid')
                ->descriptionIcon('heroicon-m-user-group', IconPosition::After)
                ->color('success'),
            Stat::make('Total', $this->totalUnpaid())
                ->description('No. of students not fully paid')
                ->descriptionIcon('heroicon-m-user-group', IconPosition::After)
                ->color('danger'),
            Stat::make('Total', $this->calculateExpectedCollections())
                ->description('Expected Collections')
                ->descriptionIcon('heroicon-m-banknotes', IconPosition::After)
                ->color('warning'),
            Stat::make('Total', $this->caculateTotalPays())
                ->description('Collected Amounts')
                ->descriptionIcon('heroicon-m-banknotes', IconPosition::After)
                ->color('success'),
            Stat::make('Total', $this->calculateRemainingCollections())
                ->description('Remaining Collections')
                ->descriptionIcon('heroicon-m-banknotes', IconPosition::After)
                ->color('danger'),
        ];
    }
}
