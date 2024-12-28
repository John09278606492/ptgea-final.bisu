<?php

namespace App\Filament\Resources\CollegeResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Guava\FilamentModalRelationManagers\Concerns\CanBeEmbeddedInModals;

class ProgramsRelationManager extends RelationManager
{
    use CanBeEmbeddedInModals;

    protected static string $relationship = 'programs';

    protected static ?string $title = 'Programs';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make()
                    ->schema([
                        Forms\Components\TextInput::make('college_id')
                            ->default(fn (RelationManager $livewire) => $livewire->ownerRecord->id)
                            ->hidden(),
                        Forms\Components\TextInput::make('program')
                            ->required()
                            ->maxLength(255)
                            ->extraInputAttributes(['onInput' => 'this.value = this.value.toUpperCase()'])
                            ->unique(ignoreRecord: true),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('program')
            ->columns([
                Tables\Columns\TextColumn::make('program'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
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
            ->emptyStateHeading('No programs yet')
            ->emptyStateDescription('Once you add program, it will appear here.');
    }
}
