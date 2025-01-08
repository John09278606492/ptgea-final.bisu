<?php

namespace App\Filament\Resources\CollegeResource\Pages;

use App\Filament\Resources\CollegeResource;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListColleges extends ListRecords
{
    protected static string $resource = CollegeResource::class;

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
                        ->title('College added successfully!')),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->previousUrl ?? $this->getResource()::getUrl('index');
    }
}
