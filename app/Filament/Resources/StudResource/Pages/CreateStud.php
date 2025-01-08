<?php

namespace App\Filament\Resources\StudResource\Pages;

use App\Filament\Resources\StudResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Contracts\Support\Htmlable;

class CreateStud extends CreateRecord
{
    protected static string $resource = StudResource::class;

    protected static bool $canCreateAnother = false;

    protected function getRedirectUrl(): string
    {
        return $this->previousUrl ?? $this->getResource()::getUrl('index');
    }

    public function getTitle(): string|Htmlable
    {
        return __('Create Student');
    }
}
