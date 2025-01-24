<?php

namespace App\Filament\Resources\EnrollmentResource\Pages;

use App\Filament\Resources\EnrollmentResource;
use App\Filament\Resources\EnrollmentResource\Widgets\TotalPayableWidget;
use Filament\Actions;
use Filament\Pages\Concerns\ExposesTableToWidgets;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;

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

    // public function getHeaderWidgets(): array
    // {
    //     return [
    //         TotalPayableWidget::class,
    //     ];
    // }

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
    // }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make(),
            'unpaid' => Tab::make('Not Fully Paid')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', null)),
            'paid' => Tab::make()
                ->label('Fully Paid')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'paid')),
        ];
    }

    // protected function paginateTableQuery(Builder $query): Paginator
    // {
    //     return $query->simplePaginate(($this->getTableRecordsPerPage() === 'all') ? $query->count() : $this->getTableRecordsPerPage());
    // }
}
