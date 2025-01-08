<?php

namespace App\Filament\Resources\ProgramResource\Pages;

use App\Filament\Resources\ProgramResource;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListPrograms extends ListRecords
{
    protected static string $resource = ProgramResource::class;

    protected ?string $maxContentWidth = 'full';

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->successNotification(
                    Notification::make()
                        ->success()
                        ->color('success')
                        ->icon('heroicon-o-check-circle')
                        ->title('Program added successfully!')),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->previousUrl ?? $this->getResource()::getUrl('index');
    }
}
