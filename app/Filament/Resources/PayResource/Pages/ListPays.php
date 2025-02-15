<?php

namespace App\Filament\Resources\PayResource\Pages;

use App\Filament\Exports\PayExporter;
use App\Filament\Resources\PayResource;
use App\Models\Pay;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Actions\ExportAction;
use Filament\Actions\Exports\Enums\ExportFormat;
use Filament\Actions\Exports\Models\Export;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Log;

class ListPays extends ListRecords
{
    protected static string $resource = PayResource::class;

    protected ?string $maxContentWidth = 'full';

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->hidden(),
            // ExportAction::make()
            //     ->exporter(PayExporter::class)
            //     ->color('success')
            //     ->formats([
            //         ExportFormat::Csv,
            //     ])
            //     ->columnMapping(false)
            //     ->icon('heroicon-m-arrow-down-on-square-stack')
            //     ->label('Export Payment Record')
            //     ->modalHeading('Export Student Payment Record')
            //     ->fileName(fn (Export $export): string => "student-payment-record-{$export->getKey()}.csv")
            Action::make('export-to-excel')
                ->color('success')
                ->icon('heroicon-m-printer')
                ->label('Export to EXCEL')
                ->livewireClickHandlerEnabled()
                ->url(function () {
                    // Retrieve the date filter from the table filters
                    $dateFilter = $this->tableFilters['created_at']['created_at'] ?? null;

                    // Initialize an error flag to avoid duplicate notifications
                    $errorFlag = false;

                    // Check if date filter is empty
                    if (empty($dateFilter)) {
                        if (!$errorFlag) {
                            Notification::make()
                                ->title('Date Range Not Specified')
                                ->body('Please specify the date range to filter the data.')
                                ->danger()
                                ->send();
                            $errorFlag = true; // Set the error flag to true to avoid duplicate notifications
                        }

                        return null; // Exit if no date range is specified
                    }

                    // If date filter exists, split it into start and end dates
                    if (strpos($dateFilter, ' - ') !== false) {
                        [$startDate, $endDate] = explode(' - ', $dateFilter);

                        try {
                            // Trim spaces and reformat from DD/MM/YYYY to YYYY-MM-DD
                            $startDate = \Carbon\Carbon::createFromFormat('d/m/Y', trim($startDate))->format('Y-m-d');
                            $endDate = \Carbon\Carbon::createFromFormat('d/m/Y', trim($endDate))->format('Y-m-d');
                        } catch (\Exception $e) {
                            // Show a notification if the date format is incorrect
                            if (!$errorFlag) {
                                Notification::make()
                                    ->title('Invalid Date Format')
                                    ->body('Please ensure the date range is in the correct format (DD/MM/YYYY - DD/MM/YYYY).')
                                    ->danger()
                                    ->send();
                                $errorFlag = true; // Set the error flag to true to avoid duplicate notifications
                            }

                            return null; // Exit if the date format is invalid
                        }

                        // Check if there are payment records for the specified date range
                        $payments = Pay::whereBetween('created_at', [$startDate, $endDate])->get();

                        if ($payments->isEmpty()) {
                            // Show a notification if no records are found
                            if (!$errorFlag) {
                                Notification::make()
                                    ->title('No Records Found')
                                    ->body('No payment records found for the specified date range.')
                                    ->danger()
                                    ->send();
                                $errorFlag = true; // Set the error flag to true to avoid duplicate notifications
                            }

                            return null; // Exit if no records are found
                        }

                        // If everything is valid, return the route with the date range
                        return route('EXPORT.PAYMENT.RECORDS', array_filter([
                            'date_from' => $startDate,
                            'date_to' => $endDate,
                        ]));
                    }

                    // Show a notification if the date filter does not have the correct format
                    if (!$errorFlag) {
                        Notification::make()
                            ->title('Invalid Date Range Format')
                            ->body('Please specify the date range in the correct format (DD/MM/YYYY - DD/MM/YYYY).')
                            ->danger()
                            ->send();
                        $errorFlag = true; // Set the error flag to true to avoid duplicate notifications
                    }

                    return null;
                }),
            Action::make('export-to-pdf')
                ->color('danger')
                ->icon('heroicon-m-printer')
                ->label('Export to PDF')
                ->livewireClickHandlerEnabled()
                ->url(function () {
                    // Check if date filter exists in table filters
                    $dateFilter = $this->tableFilters['created_at']['created_at'] ?? null;

                    // Initialize an error flag to ensure only one notification is shown
                    $errorFlag = false;

                    // Check if the date filter is empty and show the error message only if clicked
                    if (empty($dateFilter)) {
                        if (!$errorFlag) {
                            // Show a Filament notification if the date filter is not specified
                            Notification::make()
                                ->title('Date Range Not Specified')
                                ->body('Please specify the date range to filter the data.')
                                ->danger()
                                ->send();
                            $errorFlag = true; // Set the error flag to true to avoid duplicate messages
                        }

                        return null; // Exit if the filter is not specified
                    }

                    // If date filter exists, split it into start and end dates
                    if (strpos($dateFilter, ' - ') !== false) {
                        [$startDate, $endDate] = explode(' - ', $dateFilter);

                        try {
                            // Trim spaces and reformat from DD/MM/YYYY to YYYY-MM-DD
                            $startDate = \Carbon\Carbon::createFromFormat('d/m/Y', trim($startDate))->format('Y-m-d');
                            $endDate = \Carbon\Carbon::createFromFormat('d/m/Y', trim($endDate))->format('Y-m-d');
                        } catch (\Exception $e) {
                            // Show a notification if the date format is incorrect
                            if (!$errorFlag) {
                                Notification::make()
                                    ->title('Invalid Date Format')
                                    ->body('Please ensure the date range is in the correct format (DD/MM/YYYY - DD/MM/YYYY).')
                                    ->danger()
                                    ->send();
                                $errorFlag = true; // Set the error flag to true to avoid duplicate messages
                            }

                            return null; // Exit if the date format is invalid
                        }

                        // Check if there are payment records for the specified date range
                        $payments = Pay::whereBetween('created_at', [$startDate, $endDate])->get();

                        if ($payments->isEmpty()) {
                            // Show a notification if no records are found
                            if (!$errorFlag) {
                                Notification::make()
                                    ->title('No Records Found')
                                    ->body('No payment records found for the specified date range.')
                                    ->danger()
                                    ->send();
                                $errorFlag = true; // Set the error flag to true to avoid duplicate messages
                            }

                            return null; // Exit if no records are found
                        }

                        // If everything is valid, return the route with the date range
                        return route('EXPORT.PAYMENT.RECORDS.PDF', array_filter([
                            'startDate' => $startDate,
                            'endDate' => $endDate,
                        ]));
                    }

                    // Show a notification if the date filter does not have the correct format
                    if (!$errorFlag) {
                        Notification::make()
                            ->title('Invalid Date Range Format')
                            ->body('Please specify the date range in the correct format (DD/MM/YYYY - DD/MM/YYYY).')
                            ->danger()
                            ->send();
                        $errorFlag = true; // Set the error flag to true to avoid duplicate messages
                    }

                    return null;
                }),
        ];
    }

    public function getTitle(): string|Htmlable
    {
        return __('Payment Record');
    }
}
