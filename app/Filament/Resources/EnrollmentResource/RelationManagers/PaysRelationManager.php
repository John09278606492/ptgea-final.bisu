<?php

namespace App\Filament\Resources\EnrollmentResource\RelationManagers;

use App\Models\Pay;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions\Modal\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Components\Actions\Action as ActionsAction;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Forms\Get;
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
use Filament\Notifications\Notification;
use Illuminate\Support\HtmlString;
use Filament\Notifications\Events\DatabaseNotificationsSent;
use FontLib\Table\Type\name;
use Illuminate\Support\Facades\Log;

class PaysRelationManager extends RelationManager
{
    use CanBeEmbeddedInModals;

    protected static string $relationship = 'pays';

    protected static ?string $title = 'Payments';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Grid::make()
                    ->schema([
                        // Remaining Balance Display
                        Forms\Components\TextInput::make('balance')
                            ->label('Remaining Balance')
                            ->prefixIcon('heroicon-m-peso-symbol')
                            ->default(fn($livewire) => $livewire->ownerRecord?->getBalanceAttribute() ?? '₱0.00')
                            ->dehydrated(false)
                            ->disabled(),
                    ])
                    ->columnStart(1),

                Section::make('Payment Details')
                    ->schema([
                        // Amount Input
                        Forms\Components\TextInput::make('amount')
                            ->mask(RawJs::make('$money($input)'))
                            ->required()
                            ->label('Enter Amount')
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
                                                $fail('Fully Paid! Remaining balance is: ₱' . number_format($numericBalance, 2));
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

                        // Payment Summary Display
                        Forms\Components\Textarea::make('payment_summary')
                            ->default('No payment entered yet.')
                            ->reactive()
                            ->disabled()
                            ->autosize()
                            ->dehydrated(false),
                    ])
                    ->footerActions([
                        // Pay Button - Updates Payment Summary
                        ActionsAction::make('pay')
                            ->label('Pay')
                            ->color('primary')
                            ->action(
                                function (
                                    callable $set,
                                    callable $get,
                                    $livewire
                                ) {
                                    $amount = $get('amount');

                                    // Ensure amount is entered
                                    if (empty($amount) || (float) str_replace(',', '', $amount) <= 0) {
                                        Notification::make()
                                            ->title('Please enter a valid amount')
                                            ->danger()
                                            ->send();
                                        return;
                                    }

                                    // Get balance and convert it to a numeric value
                                    $balance = $livewire->ownerRecord?->getBalanceAttribute() ?? '₱0.00';
                                    $numericBalance = (float) str_replace([',', '₱'], '', $balance);

                                    // Prevent payment if balance is already zero or negative
                                    if ($numericBalance <= 0) {
                                        Notification::make()
                                            ->title('Payment Not Allowed')
                                            ->body('The student is fully paid! No additional payment required.')
                                            ->danger()
                                            ->send();
                                        return;
                                    }

                                    // Proceed to update the payment summary
                                    $livewire->updatePaymentSummary($amount, $set, $livewire);
                                }
                            ),

                        ActionsAction::make('confirm_payment')
                            ->label('Confirm Payment')
                            ->color('success')
                            ->requiresConfirmation()
                            ->hidden(fn(callable $get) => empty($get('payment_summary')) || $get('payment_summary') === 'No payment entered yet.')
                            ->action(function (callable $set, callable $get, $livewire) {
                                $amount = $get('amount');
                                $balance = $get('balance');

                                // Convert balance and amount to numeric values
                                $numericBalance = (float) str_replace([',', '₱'], '', $balance);
                                $numericAmount = (float) str_replace(',', '', $amount);

                                // ✅ Check if the entered amount is valid
                                if (empty($amount) || $numericAmount <= 0) {
                                    Notification::make()
                                        ->title('Invalid Amount')
                                        ->body('Please enter a valid amount.')
                                        ->danger()
                                        ->send();
                                    return;
                                }

                                // ✅ Determine the amount to be deducted (prevents overpayment)
                                $amountDeducted = min($numericBalance, $numericAmount);
                                $remainingBalance = max(0, $numericBalance - $amountDeducted);
                                $change = max(0, $numericAmount - $numericBalance);

                                // ✅ Get the parent enrollment
                                $enrollment = $livewire->getRelationship()->getParent();
                                if (!$enrollment) {
                                    logger()->error('No related enrollment found for payment record.');
                                    Notification::make()
                                        ->title('Error')
                                        ->body('No related enrollment found.')
                                        ->danger()
                                        ->send();
                                    return;
                                }

                                // ✅ Save the payment with the **correct deducted amount**
                                $payment = Pay::create([
                                    'enrollment_id' => $enrollment->id,
                                    'amount' => $amountDeducted, // 💡 Only deduct the allowed amount
                                    'status1' => 'paid',
                                ]);

                                if (!$payment) {
                                    logger()->error('Failed to save payment record.');
                                    Notification::make()
                                        ->title('Payment Error')
                                        ->body('Payment could not be saved. Please try again.')
                                        ->danger()
                                        ->send();
                                    return;
                                }

                                // ✅ Find the recipient (user) based on canId
                                $recipient = User::where('canId', $enrollment->stud->studentidn)->first();
                                if ($recipient) {
                                    Notification::make()
                                        ->title('Payment Received')
                                        ->body("Thank you for paying ₱" . number_format($amountDeducted, 2, '.', ',') .
                                            ". Your remaining balance is ₱" . number_format($remainingBalance, 2))
                                        ->success()
                                        ->sendToDatabase($recipient, isEventDispatched: true);
                                } else {
                                    logger()->error("No user found with canId: {$enrollment->stud->studentidn}");
                                }

                                // ✅ Update enrollment status if fully paid
                                if ($remainingBalance <= 0) {
                                    DB::table('enrollments')
                                        ->where('id', $enrollment->id)
                                        ->update(['status' => 'paid']);

                                    logger()->info('Payment is correct and balance is zero or below.');
                                } else {
                                    logger()->info("Remaining balance: {$remainingBalance}");
                                }

                                // ✅ Clear input fields
                                $set('amount', '');
                                $set('payment_summary', 'No payment entered yet.');
                                $set('balance', $livewire->ownerRecord?->getBalanceAttribute() ?? '₱0.00');

                                // ✅ Show success notification
                                Notification::make()
                                    ->title('Payment Successfully Processed')
                                    ->success()
                                    ->send();
                            })
                    ]),
                Forms\Components\TextInput::make('status')
                    ->readOnly()
                    ->hidden()
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
                Tables\Columns\TextColumn::make('status1')
                    ->label('Status')
                    ->sortable()
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
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
                Tables\Actions\CreateAction::make('create_payment')
                    ->label('New payment')
                    ->modalHeading('Payment Form')
                    ->closeModalByClickingAway(false)
                    ->modalSubmitAction(false)
                    ->modalCancelAction(false)
                    ->modalSubmitActionLabel('Pay')
                    ->disableCreateAnother()
                    ->after(function (Pay $record) {
                        $parentModel = $record->enrollment;

                        if ($parentModel) {
                            $balance = $parentModel->getBalanceAttribute();
                            $numericBalance = str_replace([',', '₱'], '', $balance);

                            // Find the recipient (user) based on canId
                            $recipient = User::where('canId', $record->enrollment->stud->studentidn)->first();

                            if ($recipient) {
                                // Send notification to the correct user
                                Notification::make()
                                    ->title('Payment Received')
                                    ->body("Thank you for paying the amount of ₱" . number_format($record->amount, 2, '.', ',') .
                                        ". Your remaining balance is ₱{$balance}.")
                                    ->success()
                                    ->sendToDatabase($recipient, isEventDispatched: true);
                            } else {
                                logger()->error("No user found with canId: {$record->enrollment->stud->studentidn}");
                            }

                            // Update enrollment status if fully paid
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
                    })
            ])
            ->actions([
                // Tables\Actions\Action::make('Generate Receipt')
                //     ->icon('heroicon-o-document')
                //     ->action(function (Pay $record) {
                //         $folderPath = storage_path('app/public/temp_receipts');
                //         if (! File::exists($folderPath)) {
                //             File::makeDirectory($folderPath, 0777, true, true);
                //         }
                //         $pdf = Pdf::loadView('receipts.payment', [
                //             'id' => $record->id,
                //             'amount_formatted' => 'PHP ' . number_format($record->amount, 2),
                //             'status' => $record->status,
                //             'date' => $record->created_at->format('M. d, Y g:i a'),
                //             'enrollment' => $record->enrollment->stud->only(['id', 'lastname', 'firstname', 'middlename']),
                //         ])
                //             ->setOption('encoding', 'UTF-8');
                //         $pdfOutput = $pdf->output();
                //         $filePath = $folderPath . '/receipt-' . $record->id . '.pdf';
                //         file_put_contents($filePath, $pdfOutput);
                //         $publicFilePath = asset('storage/temp_receipts/receipt-' . $record->id . '.pdf');
                //         $jsCode = "
                //         window.open('{$publicFilePath}', '_blank');
                //     ";

                //         return $this->js($jsCode);
                //     }),
                Tables\Actions\EditAction::make()
                    ->hidden(),
                Tables\Actions\DeleteAction::make()
                    ->label('Return')
                    ->color('warning')
                    ->icon('heroicon-m-arrow-path')
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
                        ->after(function ($records, Get $amount) { // `$records` is a collection of selected records
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

    public function updatePaymentSummary($amountTendered, callable $set, $livewire)
    {
        $balance = (float) str_replace([',', '₱'], '', $livewire->ownerRecord?->getBalanceAttribute() ?? '0');
        $amountTendered = (float) str_replace(',', '', $amountTendered);

        $amountDeducted = min($balance, $amountTendered);
        $remainingBalance = max(0, $balance - $amountTendered);
        $change = max(0, $amountTendered - $balance);

        $summary = "Amount Tendered: ₱" . number_format($amountTendered, 2) . "\n" .
            "Amount Deducted: ₱" . number_format($amountDeducted, 2) . "\n" .
            "Remaining Balance: ₱" . number_format($remainingBalance, 2) . "\n" .
            "Change: ₱" . number_format($change, 2);

        $set('payment_summary', $summary);
    }

    public function handlePayment($data, callable $set)
    {
        // Log the payment for debugging
        Log::info('Payment processed: ', $data);

        // Reset the form fields after payment
        $set('amount', '');
        $set('payment_summary', 'No payment entered yet.');

        Notification::make()
            ->title('Payment successfully processed')
            ->body('Thank you for your payment.')
            ->success()
            ->send();
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
