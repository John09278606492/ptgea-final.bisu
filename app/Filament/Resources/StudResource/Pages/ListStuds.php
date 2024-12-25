<?php

namespace App\Filament\Resources\StudResource\Pages;

use App\Filament\Resources\StudResource;
use App\Filament\Resources\StudResource\Widgets\TotalWidget;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\Support\Htmlable;

class ListStuds extends ListRecords
{
    protected static string $resource = StudResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Add student'),
        ];
    }

    public function getTitle(): string|Htmlable
    {
        return __('Student Information');
    }

    protected function getRedirectUrl(): string
    {
        return $this->previousUrl ?? $this->getResource()::getUrl('index');
    }

    public function getWidgets(): array
    {
        return [
            TotalWidget::class,
        ];
    }

    // public function getHeaderWidgets(): array
    // {
    //     return [
    //         TotalWidget::class,
    //     ];
    // }
}
