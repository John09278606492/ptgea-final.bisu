<?php

namespace App\Filament\Resources\ProgramResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Validation\Rules\Unique;

class YearlevelsRelationManager extends RelationManager
{
    protected static string $relationship = 'yearlevels';

    protected static ?string $title = 'Year Levels';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make()
                    ->schema([
                        Forms\Components\TextInput::make('program_id')
                            ->hidden()
                            ->default(fn (RelationManager $livewire) => $livewire->ownerRecord->id),
                        Forms\Components\TextInput::make('yearlevel')
                            ->label('Year Level')
                            ->required()
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(4)
                            ->unique(modifyRuleUsing: function (Unique $rule, callable $get) {
                                $yearlevel = $get('yearlevel');
                                $program_id = $get('program_id');

                                return $rule->where('program_id', $program_id)->where('yearlevel', $yearlevel);
                            },
                                ignoreRecord: true, ),

                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('yearlevel')
            ->columns([
                Tables\Columns\TextColumn::make('yearlevel')
                    ->label('Year Level'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('New year level')
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
            ->emptyStateHeading('No year levels yet')
            ->emptyStateDescription('Once you add year level, it will appear here.');
    }
}
