<?php

namespace App\Filament\Resources;

use App\Filament\Resources\YearlevelResource\Pages;
use App\Filament\Resources\YearlevelResource\RelationManagers;
use App\Filament\Resources\YearlevelResource\RelationManagers\YearlevelpaymentsRelationManager;
use App\Models\Program;
use App\Models\Yearlevel;
use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Table;
use Guava\FilamentModalRelationManagers\Actions\Table\RelationManagerAction;
use Illuminate\Validation\Rules\Unique;

class YearlevelResource extends Resource
{
    protected static ?string $model = Yearlevel::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = 'Year Level';

    protected static ?string $breadcrumb = 'Year Level';

    protected static ?string $navigationGroup = 'COLLEGE MANAGEMENT';

    protected static ?int $navigationSort = 5;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make()
                    ->schema([
                        Forms\Components\Select::make('program_id')
                            ->label('Program')
                            ->options(Program::all()->pluck('program', 'id'))
                            ->required(),
                        Forms\Components\TextInput::make('yearlevel')
                            ->required()
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(4)
                            ->unique(modifyRuleUsing: function (Unique $rule, callable $get) {
                                $yearlevel = $get('yearlevel');
                                $program_id = $get('program_id');

                                return $rule->where('program_id', $program_id)->where('yearlevel', $yearlevel);
                            },
                                ignoreRecord: true,
                            ),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('program.program')
                    ->sortable(),
                Tables\Columns\TextColumn::make('yearlevel')
                    ->weight(FontWeight::Bold)
                    ->searchable(),
                // Tables\Columns\TextColumn::make('formatted_amount')
                //     ->label('Total Collections')
                //     ->money('PHP')
                //     ->searchable(),
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
                RelationManagerAction::make('yearlevelpayments-relation-manager')
                    ->label('Add fee type')
                    ->icon('heroicon-m-banknotes')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close')
                    ->modalHeading('')
                    ->relationManager(YearlevelpaymentsRelationManager::make()),
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
            RelationManagers\YearlevelpaymentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListYearlevels::route('/'),
            'create' => Pages\CreateYearlevel::route('/create'),
            'edit' => Pages\EditYearlevel::route('/{record}/edit'),
        ];
    }
}
