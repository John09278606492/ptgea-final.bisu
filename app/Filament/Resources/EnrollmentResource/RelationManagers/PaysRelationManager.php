<?php

namespace App\Filament\Resources\EnrollmentResource\RelationManagers;

use App\Models\Pay;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\RawJs;
use Filament\Tables;
use Filament\Tables\Table;
use Guava\FilamentModalRelationManagers\Concerns\CanBeEmbeddedInModals;
use Illuminate\Support\Facades\File;
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
                                // Retrieve the parent model through the relationship
                                $parentModel = $this->getRelationship()->getParent();

                                if (method_exists($parentModel, 'getBalanceAttribute')) {
                                    $balance = $parentModel->getBalanceAttribute();

                                    // Convert balance to numeric for comparison
                                    $numericBalance = str_replace([',', '₱'], '', $balance);

                                    if ((float) $value > (float) $numericBalance) {
                                        $fail('The payment amount cannot exceed the remaining balance of ₱'.number_format((float) $numericBalance, 2));
                                    }
                                } else {
                                    $fail('Balance validation failed due to missing method.');
                                }
                            };
                        },
                    ]),
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
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Date/Time Paid Updated')
                    ->dateTime()
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
                    ->disableCreateAnother(),
            ])
            ->actions([
                Tables\Actions\Action::make('Generate Receipt')
                    ->icon('heroicon-o-document')
                    ->action(function (Pay $record) {
                        // Folder path for saving the PDF
                        $folderPath = storage_path('app/public/temp_receipts');

                        // Check if the folder exists, if not, create it
                        if (! File::exists($folderPath)) {
                            File::makeDirectory($folderPath, 0777, true, true); // Create directory with permissions
                        }

                        // Generate the receipt PDF
                        $pdf = Pdf::loadView('receipts.payment', [
                            'id' => $record->id,
                            'amount_formatted' => 'PHP '.number_format($record->amount, 2),
                            'status' => $record->status,
                            'date' => $record->created_at->format('M. d, Y g:i a'),
                            'enrollment' => $record->enrollment->stud->only(['id', 'lastname', 'firstname', 'middlename']),
                        ])
                            ->setOption('encoding', 'UTF-8');

                        // Save the PDF to a file
                        $pdfOutput = $pdf->output();
                        $filePath = $folderPath.'/receipt-'.$record->id.'.pdf';
                        file_put_contents($filePath, $pdfOutput);

                        // Get the URL of the file for the print action
                        $publicFilePath = asset('storage/temp_receipts/receipt-'.$record->id.'.pdf');

                        // Prepare JavaScript code as a single string
                        $jsCode = "
                        window.open('{$publicFilePath}', '_blank');
                    ";

                        // Log the JavaScript code for debugging
                        Log::info('JavaScript Code', ['code' => $jsCode]);

                        // Return the JavaScript to be executed
                        return $this->js($jsCode);
                    }),
                Tables\Actions\EditAction::make()
                    ->hidden(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->heading('Payment history')
            ->emptyStateHeading('No payments yet')
            ->emptyStateDescription('Once student pays, it will appear here.');
    }
}
