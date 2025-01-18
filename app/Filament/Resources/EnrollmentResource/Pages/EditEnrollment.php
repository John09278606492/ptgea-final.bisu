<?php

namespace App\Filament\Resources\EnrollmentResource\Pages;

use App\Filament\Resources\EnrollmentResource;
use App\Filament\Resources\EnrollmentResource\Widgets\TotalPayableWidget;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Js;

class EditEnrollment extends EditRecord
{
    protected static string $resource = EnrollmentResource::class;

    // protected static bool $saveChanges = true;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('return')
                ->color('primary')
                ->icon('heroicon-m-arrow-left-circle')
                ->label('Go back')
                ->url(fn () => static::getResource()::getUrl('index')),
            // Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->previousUrl ?? $this->getResource()::getUrl('index');
    }

    public function hasCombinedRelationManagerTabsWithContent(): bool
    {
        return true;
    }

    public function getContentTabLabel(): ?string
    {
        return 'Enrollment Info';
    }

    public function getTitle(): string|Htmlable
    {
        return __('Edit Student Payment');
    }

    // protected function getSaveFormAction(): Action
    // {
    //     return Action::make('save')
    //         ->label(__('Save enrollment'))
    //         ->submit('save')
    //         ->keyBindings(['mod+s'])
    //         ->hidden();
    // }

    // protected function getCancelFormAction(): Action
    // {
    //     return Action::make('cancel')
    //         ->label(__('Return'))
    //         ->alpineClickHandler('document.referrer ? window.history.back() : (window.location.href = '.Js::from($this->previousUrl ?? static::getResource()::getUrl()).')')
    //         ->color('primary');
    // }

    // public function getHeaderWidgets(): array
    // {
    //     return [
    //         TotalPayableWidget::class,
    //     ];
    // }
}
