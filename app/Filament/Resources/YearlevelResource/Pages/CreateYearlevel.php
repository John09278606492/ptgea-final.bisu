<?php

namespace App\Filament\Resources\YearlevelResource\Pages;

use App\Filament\Resources\YearlevelResource;
use Filament\Resources\Pages\CreateRecord;

class CreateYearlevel extends CreateRecord
{
    protected static string $resource = YearlevelResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->previousUrl ?? $this->getResource()::getUrl('index');
    }

    protected static bool $canCreateAnother = false;
}
