<?php

namespace App\Filament\Resources\PayResource\Pages;

use App\Filament\Resources\PayResource;
use Filament\Actions;
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
        ];
    }

    public function getTitle(): string|Htmlable
    {
        return __('Payment Record');
    }
}
