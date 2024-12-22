<?php

namespace App\Filament\Resources\StudResource\Pages;

use App\Filament\Resources\StudResource;
use App\Filament\Resources\StudResource\Widgets\TotalWidget;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListStuds extends ListRecords
{
    protected static string $resource = StudResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
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

    public function getHeaderWidgets(): array
    {
        return [
            TotalWidget::class,
        ];
    }
}
