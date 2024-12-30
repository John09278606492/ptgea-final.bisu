<?php

namespace App\Filament\Resources\StudResource\Pages;

use App\Filament\Resources\StudResource;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Js;

class EditStud extends EditRecord
{
    protected static string $resource = StudResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    public function getTitle(): string|Htmlable
    {
        return __('Edit student information');
    }

    // protected function getSaveFormAction(): Action
    // {
    //     return Action::make('save')
    //         ->label(__('Update'))
    //         ->submit('save')
    //         ->keyBindings(['mod+s']);
    // }

    // protected function getCancelFormAction(): Action
    // {
    //     return Action::make('cancel')
    //         ->label(__('Close'))
    //         ->alpineClickHandler('document.referrer ? window.history.back() : (window.location.href = '.Js::from($this->previousUrl ?? static::getResource()::getUrl()).')')
    //         ->color('gray');
    // }

    protected function getRedirectUrl(): string
    {
        return $this->previousUrl ?? $this->getResource()::getUrl('index');
    }

    public function getContentTabLabel(): ?string
    {
        return 'Student Info';
    }

    public function hasCombinedRelationManagerTabsWithContent(): bool
    {
        return true;
    }
}
