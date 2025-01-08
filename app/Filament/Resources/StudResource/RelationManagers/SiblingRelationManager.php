<?php

namespace App\Filament\Resources\StudResource\RelationManagers;

use App\Models\Stud;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use stdClass;

class SiblingRelationManager extends RelationManager
{
    protected static string $relationship = 'siblings';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('sibling_id')
                    ->label('Student')
                    ->options(Stud::all()->pluck('firstname', 'id'))
                    ->preload()
                    ->searchable()
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('sibling_id')
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
                Tables\Columns\TextColumn::make('stud.studentidn')
                    ->label('Student IDN'),
                Tables\Columns\TextColumn::make('stud.lastname')
                    ->weight(FontWeight::Bold)
                    ->label('Last Name'),
                Tables\Columns\TextColumn::make('stud.firstname')
                    ->label('First Name'),  
                Tables\Columns\TextColumn::make('stud.middlename')
                    ->label('Middle Name'),

            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
