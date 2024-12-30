<?php

namespace App\Filament\Resources\SchoolyearResource\Pages;

use App\Filament\Resources\SchoolyearResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\Support\Htmlable;

class ListSchoolyears extends ListRecords
{
    protected static string $resource = SchoolyearResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('New school year'),
        ];
    }

    public function getTitle(): string|Htmlable
    {
        return __('School Year');
    }

    protected function getRedirectUrl(): string
    {
        return $this->previousUrl ?? $this->getResource()::getUrl('index');
    }
}
