<?php

namespace App\Filament\Resources\StudResource\Pages;

use App\Filament\Resources\StudResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateStud extends CreateRecord
{
    protected static string $resource = StudResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->previousUrl ?? $this->getResource()::getUrl('index');
    }
}
