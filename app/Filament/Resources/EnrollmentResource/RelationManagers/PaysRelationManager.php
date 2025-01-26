<?php

namespace App\Filament\Resources\EnrollmentResource\RelationManagers;

use App\Models\Pay;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\RawJs;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Guava\FilamentModalRelationManagers\Concerns\CanBeEmbeddedInModals;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use stdClass;

class PaysRelationManager extends RelationManager
{
    use CanBeEmbeddedInModals;

    protected static string $relationship = 'pays';

    protected static ?string $title = 'Payments';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('amount')
                    ->mask(RawJs::make('$money($input)'))
                    ->stripCharacters(',')
                    ->extraInputAttributes([
                        'onInput' => 'this.value = this.value.replace(/[^\d.]/g, "").replace(/(\..*?)\.+/g, "$1").replace(/\B(?=(\d{3})+(?!\d))/g, ",")',
                    ])
                    ->numeric()
                    ->prefixIcon('heroicon-m-peso-symbol')
                    ->required()
                    ->rules([
                        function () {
                            return function ($attribute, $value, $fail) {
                                $parentModel = $this->getRelationship()->getParent();

                                if (method_exists($parentModel, 'getBalanceAttribute')) {
                                    $balance = $parentModel->getBalanceAttribute();

                                    $numericBalance = (float) str_replace([',', '₱'], '', $balance);

                                    if ($numericBalance <= 0) {
                                        $fail('Full Paid! Remaining balance is: ₱' . number_format($numericBalance, 2));
                                    }
                                } else {
                                    $fail('Balance validation failed due to missing method.');
                                }
                            };
                        },
                    ])
                    ->dehydrateStateUsing(function ($state) {
                        $parentModel = $this->getRelationship()->getParent();

                        if (method_exists($parentModel, 'getBalanceAttribute')) {
                            $balance = $parentModel->getBalanceAttribute();
                            $numericBalance = (float) str_replace([',', '₱'], '', $balance);

                            if ($numericBalance > 0) {
                                return min((float) str_replace(',', '', $state), $numericBalance);
                            }
                        }
                        return (float) str_replace(',', '', $state);
                    }),
                Forms\Components\TextInput::make('status')
                    ->readOnly()
                    ->default('paid'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('amount')
            ->columns([
                TextColumn::make('#')->state(
                    static function (HasTable $livewire, stdClass $rowLoop): string {
                        return (string) (
                            $rowLoop->iteration +
                            ($livewire->getTableRecordsPerPage() * (
                                $livewire->getTablePage() - 1
                            ))
                        );
                    }
                ),
                Tables\Columns\TextColumn::make('amount')
                    ->money('PHP'),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->sortable()
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'paid' => 'success',
                        'returned' => 'warning',
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date/Time Paid')
                    ->dateTime('M d, Y h:i a')
                    ->sortable(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Date/Time Paid Updated')
                    ->dateTime('M d, Y h:i a')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('New payment')
                    ->modalHeading('Payment Form')
                    ->modalSubmitActionLabel('Pay')
                    ->disableCreateAnother()
                    ->after(function (Pay $record) {
                        $parentModel = $record->enrollment;

                        if ($parentModel) {
                            $balance = $parentModel->getBalanceAttribute();

                            $numericBalance = str_replace([',', '₱'], '', $balance);

                            if ((float) $numericBalance <= 0) {
                                DB::table('enrollments')
                                    ->where('id', $this->getOwnerRecord()->id)
                                    ->update(['status' => 'paid']);
                                logger()->info('Payment is correct and balance is zero or below.');
                            } else {
                                logger()->info("Remaining balance: {$numericBalance}");
                            }
                        } else {
                            logger()->error('No related enrollment found for payment record.');
                        }
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('Generate Receipt')
                    ->icon('heroicon-o-document')
                    ->action(function (Pay $record) {
                        $folderPath = storage_path('app/public/temp_receipts');
                        if (! File::exists($folderPath)) {
                            File::makeDirectory($folderPath, 0777, true, true);
                        }
                        $pdf = Pdf::loadView('receipts.payment', [
                            'id' => $record->id,
                            'amount_formatted' => 'PHP '.number_format($record->amount, 2),
                            'status' => $record->status,
                            'date' => $record->created_at->format('M. d, Y g:i a'),
                            'enrollment' => $record->enrollment->stud->only(['id', 'lastname', 'firstname', 'middlename']),
                        ])
                            ->setOption('encoding', 'UTF-8');
                        $pdfOutput = $pdf->output();
                        $filePath = $folderPath.'/receipt-'.$record->id.'.pdf';
                        file_put_contents($filePath, $pdfOutput);
                        $publicFilePath = asset('storage/temp_receipts/receipt-'.$record->id.'.pdf');
                        $jsCode = "
                        window.open('{$publicFilePath}', '_blank');
                    ";

                        return $this->js($jsCode);
                    }),
                Tables\Actions\EditAction::make()
                    ->hidden(),
                Tables\Actions\DeleteAction::make()
                ->after(function (Pay $record) {
                    $parentModel = $record->enrollment;

                    if ($parentModel) {
                        $balance = $parentModel->getBalanceAttribute();

                        $numericBalance = str_replace([',', '₱'], '', $balance);

                        if ((float) $numericBalance <= 0) {
                            DB::table('enrollments')
                                ->where('id', $this->getOwnerRecord()->id)
                                ->update(['status' => 'paid']);
                            logger()->info('Payment is correct and balance is zero or below.');
                        } else {
                            DB::table('enrollments')
                                ->where('id', $this->getOwnerRecord()->id)
                                ->update(['status' => NULL]);
                            logger()->info("Remaining balance: {$numericBalance}");
                        }
                    } else {
                        logger()->error('No related enrollment found for payment record.');
                    }
                }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                    ->after(function ($records) { // `$records` is a collection of selected records
                        foreach ($records as $record) {
                            $parentModel = $record->enrollment;

                            if ($parentModel) {
                                $balance = $parentModel->getBalanceAttribute();

                                $numericBalance = str_replace([',', '₱'], '', $balance);

                                if ((float) $numericBalance <= 0) {
                                    DB::table('enrollments')
                                        ->where('id', $parentModel->id) // Use the `enrollment` model's ID
                                        ->update(['status' => 'paid']);
                                    logger()->info("Payment is correct for enrollment ID {$parentModel->id}, balance is zero or below.");
                                } else {
                                    DB::table('enrollments')
                                        ->where('id', $parentModel->id) // Use the `enrollment` model's ID
                                        ->update(['status' => NULL]);
                                    logger()->info("Remaining balance for enrollment ID {$parentModel->id}: {$numericBalance}");
                                }
                            } else {
                                logger()->error("No related enrollment found for payment record ID {$record->id}.");
                            }
                        }
                    }),
                ]),
            ])

            ->heading('Payment history')
            ->emptyStateHeading('No payments yet')
            ->emptyStateDescription('Once student pays, it will appear here.');
        // ->save(function (Forms\ComponentContainer $form, Pay $record) {
        //     $enrollment = $record->enrollment;

        //     if ($enrollment && $enrollment->getBalanceAttribute() <= 0) {
        //         $enrollment->update(['status' => 'paid']);
        //     }
        // });
    }

    protected function afterSave(): void
    {
        $enrollment = $this->getOwnerRecord(); // Retrieve the parent Enrollment

        if ($enrollment) {
            $balance = $enrollment->getBalanceAttribute();

            if ($balance <= 0) {
                logger()->info('Should Update');
                $enrollment->update(['status' => 'paid']);
            } else {
                logger()->info("Balance is not zero or below: {$balance}");
            }
        } else {
            logger()->error('No related enrollment found for payment record.');
        }
    }
}
