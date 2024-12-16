<?php

namespace App\Filament\Resources\YearlevelResource\Pages;

use App\Filament\Resources\YearlevelResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListYearlevels extends ListRecords
{
    protected static string $resource = YearlevelResource::class;

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
}
