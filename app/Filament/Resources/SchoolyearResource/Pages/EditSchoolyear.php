<?php

namespace App\Filament\Resources\SchoolyearResource\Pages;

use App\Filament\Resources\SchoolyearResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Contracts\Support\Htmlable;

class EditSchoolyear extends EditRecord
{
    protected static string $resource = SchoolyearResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->previousUrl ?? $this->getResource()::getUrl('index');
    }

    public function getTitle(): string|Htmlable
    {
        return __(' Edit School Year');
    }

    public function getContentTabLabel(): ?string
    {
        return 'School Year';
    }

    public function hasCombinedRelationManagerTabsWithContent(): bool
    {
        return true;
    }
}
