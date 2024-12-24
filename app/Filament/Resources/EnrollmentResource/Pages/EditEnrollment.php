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

    protected static bool $saveChanges = false;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    public function getContentTabLabel(): ?string
    {
        return 'Payment';
    }

    public function getTitle(): string|Htmlable
    {
        return __('Payment');
    }

    protected function getSaveFormAction(): Action
    {
        return Action::make('save')
            ->label(__('Save enrollment'))
            ->submit('save')
            ->keyBindings(['mod+s'])
            ->hidden();
    }

    protected function getCancelFormAction(): Action
    {
        return Action::make('cancel')
            ->label(__('Return'))
            ->alpineClickHandler('document.referrer ? window.history.back() : (window.location.href = '.Js::from($this->previousUrl ?? static::getResource()::getUrl()).')')
            ->color('primary');
    }

    // public function getHeaderWidgets(): array
    // {
    //     return [
    //         TotalPayableWidget::class,
    //     ];
    // }
}
