<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProgramResource\Pages;
use App\Filament\Resources\ProgramResource\RelationManagers;
use App\Filament\Resources\ProgramResource\RelationManagers\YearlevelsRelationManager;
use App\Models\College;
use App\Models\Program;
use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Table;
use Guava\FilamentModalRelationManagers\Actions\Table\RelationManagerAction;

class ProgramResource extends Resource
{
    protected static ?string $model = Program::class;

    protected static ?string $navigationIcon = 'heroicon-o-book-open';

    protected static ?string $navigationLabel = 'Program';

    protected static ?string $navigationGroup = 'COLLEGE MANAGEMENT';

    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make()
                    ->schema([
                        Forms\Components\Select::make('college_id')
                            ->label('College')
                            ->options(College::all()->pluck('college', 'id'))
                            ->required(),
                        Forms\Components\TextInput::make('program')
                            ->label('Program')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->extraInputAttributes(['onInput' => 'this.value = this.value.toUpperCase()']),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('college.college')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('program')
                    ->sortable()
                    ->weight(FontWeight::Bold)
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                RelationManagerAction::make('yearlevels-relation-manager')
                    ->label('Add year level')
                    ->icon('heroicon-m-book-open')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close')
                    ->modalHeading('')
                    ->relationManager(YearlevelsRelationManager::make()),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\YearlevelsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPrograms::route('/'),
            'create' => Pages\CreateProgram::route('/create'),
            'edit' => Pages\EditProgram::route('/{record}/edit'),
        ];
    }
}
