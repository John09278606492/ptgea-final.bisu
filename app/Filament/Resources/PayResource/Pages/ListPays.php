<?php

namespace App\Filament\Resources\PayResource\Pages;

use App\Filament\Exports\PayExporter;
use App\Filament\Resources\PayResource;
use Filament\Actions;
use Filament\Actions\ExportAction;
use Filament\Actions\Exports\Enums\ExportFormat;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\Support\Htmlable;

class ListPays extends ListRecords
{
    protected static string $resource = PayResource::class;

    protected ?string $maxContentWidth = 'full';

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->hidden(),
            ExportAction::make()
                ->exporter(PayExporter::class)
                ->color('success')
                ->formats([
                    ExportFormat::Csv,
                ])
                ->icon('heroicon-m-arrow-down-on-square-stack')
                ->label('Export Payment Record')
        ];
    }

    public function getTitle(): string|Htmlable
    {
        return __('Payment Record');
    }
}
