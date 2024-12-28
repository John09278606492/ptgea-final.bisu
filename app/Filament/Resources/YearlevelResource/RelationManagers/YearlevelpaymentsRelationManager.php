<?php

namespace App\Filament\Resources\YearlevelResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\RawJs;
use Filament\Tables;
use Filament\Tables\Table;
use Guava\FilamentModalRelationManagers\Concerns\CanBeEmbeddedInModals;

class YearlevelpaymentsRelationManager extends RelationManager
{
    use CanBeEmbeddedInModals;
    protected static string $relationship = 'yearlevelpayments';

    protected static ?string $title = 'Fee Types ';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make()
                    ->schema([
                        Forms\Components\TextInput::make('yearlevel_id')
                            ->default(fn (RelationManager $livewire) => $livewire->ownerRecord->id)
                            ->hidden(),
                        Forms\Components\TextInput::make('amount')
                            ->mask(RawJs::make('$money($input)'))
                            ->stripCharacters(',')
                            ->extraInputAttributes(['onInput' => 'this.value = this.value.replace(/[^\d.]/g, "").replace(/(\..*?)\.+/g, "$1").replace(/\B(?=(\d{3})+(?!\d))/g, ",")'])
                            ->numeric()
                            ->prefixIcon('heroicon-m-peso-symbol')
                            ->required(),
                        Forms\Components\TextInput::make('description')
                            ->required()
                            ->minLength(2)
                            ->maxLength(60)
                            ->extraInputAttributes(['onInput' => 'this.value = this.value.replace(/\\b\\w/g, char => char.toUpperCase())']),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('amount')
            ->columns([
                Tables\Columns\TextColumn::make('amount')
                    ->money('PHP'),
                Tables\Columns\TextColumn::make('description'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('New fee type')
                    ->createAnother(false),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('No fee types yet')
            ->emptyStateDescription('Once you add fee type, it will appear here.');
    }
}
