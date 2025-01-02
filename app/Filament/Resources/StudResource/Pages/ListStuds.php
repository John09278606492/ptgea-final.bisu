<?php

namespace App\Filament\Resources\StudResource\Pages;

use App\Filament\Imports\EnrollmentImporter;
use App\Filament\Imports\StudImporter;
use App\Filament\Resources\StudResource;
use App\Filament\Resources\StudResource\Widgets\TotalWidget;
use Filament\Actions;
use Filament\Actions\ImportAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\Support\Htmlable;

class ListStuds extends ListRecords
{
    protected static string $resource = StudResource::class;

    protected ?string $maxContentWidth = 'full';

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Add student'),
            ImportAction::make('importStud')
                ->label('Bulk add student')
                ->color('warning')
                ->icon('heroicon-m-user-group')
                ->importer(StudImporter::class),
            ImportAction::make('importEnrollment')
                ->label('Bulk enroll student')
                ->color('success')
                ->icon('heroicon-m-user-group')
                ->importer(EnrollmentImporter::class),
        ];
    }

    public function getTitle(): string|Htmlable
    {
        return __('Student Information');
    }

    protected function getRedirectUrl(): string
    {
        return $this->previousUrl ?? $this->getResource()::getUrl('index');
    }

    public function getWidgets(): array
    {
        return [
            TotalWidget::class,
        ];
    }

    // public function getHeaderWidgets(): array
    // {
    //     return [
    //         TotalWidget::class,
    //     ];
    // }
}
