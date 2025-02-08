<?php

namespace App\Filament\Resources\EnrollmentResource\Pages;

use App\Filament\Exports\EnrollmentExporter;
use App\Filament\Imports\EnrollmentImporter;
use App\Filament\Resources\EnrollmentResource;
use App\Filament\Resources\EnrollmentResource\Widgets\TotalPayableWidget;
use App\Models\Enrollment;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Actions\ExportAction;
use Filament\Actions\Exports\Enums\ExportFormat;
use Filament\Pages\Concerns\ExposesTableToWidgets;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Concerns\InteractsWithTable;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Livewire\Attributes\On;
use Filament\Actions\Exports\Models\Export;

class ListEnrollments extends ListRecords
{
    use ExposesTableToWidgets;

    protected static string $resource = EnrollmentResource::class;

    protected ?string $maxContentWidth = 'full';

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->hidden(),
            ExportAction::make()
                ->exporter(EnrollmentExporter::class)
                ->color('success')
                ->formats([
                    ExportFormat::Csv,
                    ExportFormat::Xlsx,
                ])
                ->columnMapping(false)
                ->icon('heroicon-m-arrow-down-on-square-stack')
                ->label('Export')
                ->modalHeading('Export Student Payment Information'),
            Action::make('print')
                ->color('primary')
                ->icon('heroicon-m-printer')
                ->label('Export to PDF')
                ->livewireClickHandlerEnabled()
                ->url(fn () =>
                    // Check if `schoolyear_id` exists in the filters. If it does, pass it, otherwise don't pass the id.
                    $this->tableFilters['course_filter']['schoolyear_id']
                        ? route('EXPORT.RECORDS', ['id' => $this->tableFilters['course_filter']['schoolyear_id']])
                        : route('EXPORT.RECORDS.ALL') // No `id` passed if it's null
                ),

        ];
    }

    protected function getTableQuery(): ?Builder
    {
        return static::getResource()::getEloquentQuery();
    }

    public function getTitle(): string|Htmlable
    {
        return __('Student Payment');
    }

    public function getHeaderWidgets(): array
    {
        return [
            TotalPayableWidget::class,
        ];
    }

    // public function mount(): void
    // {
    //     parent::mount();

    //     // Retrieve applied filters
    //     $appliedFilters = $this->getTableFilters();

    //     // Log or use the filters
    //     Log::info('Filters applied:', $appliedFilters);
    // }

    // public function getTableFilters(): array
    // {
    //     return $this->table->getFilters();
    //

    // public function getTabs(): array
    // {
    //     return [
    //         'all' => Tab::make(),
    //         'unpaid' => Tab::make('Not Fully Paid')
    //             ->badgeColor('danger')
    //             ->badge(fn () => $this->getFilteredTableQuery()->clone()->where('status', null)->count())
    //             ->modifyQueryUsing(fn (Builder $query) => $query->where('status', NULL)),
    //         'paid' => Tab::make()
    //             ->label('Fully Paid')
    //             ->badgeColor('success')
    //             ->badge(fn () => $this->getFilteredTableQuery()->clone()->where('status', 'paid')->count())
    //             ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'paid')),
    //     ];
    // }

    // #[On('refresh')]
    // public function getTabs(): array
    // {
    //     $schoolyearId = $this->tableFilters['course_filter']['schoolyear_id'] ?? null;

    //     // Get filter values dynamically
    //     $collegeId = $this->tableFilters['course_filter']['college_id'] ?? null;
    //     $programId = $this->tableFilters['course_filter']['program_id'] ?? null;
    //     $yearlevelId = $this->tableFilters['course_filter']['yearlevel_id'] ?? null;

    //     $baseQuery = Enrollment::query();

    //     // Apply filters dynamically
    //     $baseQuery
    //         ->when($schoolyearId, fn ($query) => $query->where('schoolyear_id', $schoolyearId))
    //         ->when($collegeId, fn ($query) => $query->where('college_id', $collegeId))
    //         ->when($programId, fn ($query) => $query->where('program_id', $programId))
    //         ->when($yearlevelId, fn ($query) => $query->where('yearlevel_id', $yearlevelId));

    //     return [
    //         'all' => Tab::make()
    //             ->label('All')
    //             ->badgeColor('info')
    //             ->badge($baseQuery->count()),

    //         'unpaid' => Tab::make('Not Fully Paid')
    //             ->badgeColor('danger')
    //             ->badge($baseQuery->clone()->whereNull('status')->count())
    //             ->modifyQueryUsing(fn (Builder $query) => $query->whereNull('status')),

    //         'paid' => Tab::make('Fully Paid')
    //             ->badgeColor('success')
    //             ->badge($baseQuery->clone()->where('status', 'paid')->count())
    //             ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'paid')),
    //     ];
    // }


    // protected function paginateTableQuery(Builder $query): Paginator
    // {
    //     return $query->simplePaginate(($this->getTableRecordsPerPage() === 'all') ? $query->count() : $this->getTableRecordsPerPage());
    // }
}
