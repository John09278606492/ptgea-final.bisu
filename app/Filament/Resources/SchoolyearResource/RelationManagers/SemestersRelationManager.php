<?php

namespace App\Filament\Resources\SchoolyearResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\RawJs;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Validation\Rules\Unique;

class SemestersRelationManager extends RelationManager
{
    protected static string $relationship = 'semesters';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make()
                    ->schema([
                        Forms\Components\TextInput::make('schoolyear_id')
                            ->hidden()
                            ->default(fn (RelationManager $livewire) => $livewire->ownerRecord->id),
                        Forms\Components\TextInput::make('semester')
                            ->required()
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(2)
                            ->unique(modifyRuleUsing: function (Unique $rule, callable $get) {
                                $semester = $get('semester');
                                $schoolyear_id = $get('schoolyear_id');

                                return $rule->where('schoolyear_id', $schoolyear_id)->where('semester', $semester);
                            },
                                ignoreRecord: true, ),
                        Repeater::make('collections')
                            ->relationship()
                            ->schema([
                                TextInput::make('amount')
                                    ->mask(RawJs::make('$money($input)'))
                                    ->stripCharacters(',')
                                    ->extraInputAttributes(['onInput' => 'this.value = this.value.replace(/[^\d.]/g, "").replace(/(\..*?)\.+/g, "$1").replace(/\B(?=(\d{3})+(?!\d))/g, ",")'])
                                    ->numeric()
                                    ->prefixIcon('heroicon-m-peso-symbol')
                                    ->required(),
                                TextInput::make('description')
                                    ->required()
                                    ->minLength(2)
                                    ->maxLength(60)
                                    ->extraInputAttributes(['onInput' => 'this.value = this.value.replace(/\\b\\w/g, char => char.toUpperCase())']),
                            ])
                            ->columns(2),
                    ])
                    ->columns(1),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('semester')
            ->columns([
                Tables\Columns\TextColumn::make('semester')
                    ->sortable(),
                Tables\Columns\TextColumn::make('semester_total_collection')
                    ->label('Total for Fee Types')
                    ->money('PHP')
                    ->getStateUsing(function ($record) {
                        return $record->semester_total_collection;
                    }),
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
            ->emptyStateHeading('No semesters yet')
            ->emptyStateDescription('Once you add semester, it will appear here.');
    }
}
