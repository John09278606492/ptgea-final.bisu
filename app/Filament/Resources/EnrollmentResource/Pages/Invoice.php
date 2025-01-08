<?php

namespace App\Filament\Resources\EnrollmentResource\Pages;

use App\Filament\Resources\EnrollmentResource;
use App\Models\Enrollment;
use Filament\Actions\Action;
use Filament\Resources\Pages\Page;

class Invoice extends Page
{
    protected static string $resource = EnrollmentResource::class;

    protected static string $view = 'filament.resources.enrollment-resource.pages.invoice';

    public $record;

    public $payments;

    public function mount($record)
    {
        $this->record = $record;
        $this->payments = Enrollment::with('pays')->find($record);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('print')
                ->color('success')
                ->icon('heroicon-o-printer')
                ->label('Print')
                ->url(route('PRINT.INVOICE', ['id' => $this->record])),
        ];
    }
}
