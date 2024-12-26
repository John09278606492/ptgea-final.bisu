<?php

namespace App\Filament\Resources\EnrollmentResource\Pages;

use App\Filament\Resources\EnrollmentResource;
use App\Filament\Resources\EnrollmentResource\Widgets\TotalPayableWidget;
use Filament\Actions;
use Filament\Pages\Concerns\ExposesTableToWidgets;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;

class ListEnrollments extends ListRecords
{
    use ExposesTableToWidgets;

    protected static string $resource = EnrollmentResource::class;

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

    public function getHeaderWidgets(): array
    {
        return [
            TotalPayableWidget::class,
        ];
    }
}
