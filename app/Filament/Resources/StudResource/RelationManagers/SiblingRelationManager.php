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
use Guava\FilamentModalRelationManagers\Concerns\CanBeEmbeddedInModals;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rules\Unique;
use stdClass;

class SiblingRelationManager extends RelationManager
{
    use CanBeEmbeddedInModals;

    protected static string $relationship = 'siblings';

    protected static ?string $badgeTooltip = 'Number of siblings';

    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        $count = $ownerRecord->siblings()->count();

        return $count > 0 ? $count : 0;
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('stud_id')
                    ->hidden()
                    ->default(fn (RelationManager $livewire) => $livewire->ownerRecord->id),
                Forms\Components\Select::make('sibling_id')
                    ->label('Sibling Name')
                    ->inlineLabel(false)
                    ->placeholder('Select a student')
                    ->searchable()
                    ->getSearchResultsUsing(fn (string $search): array => Stud::where('studentidn', 'like', "%{$search}%")
                        ->orWhere('firstname', 'like', "%{$search}%")
                        ->orWhere('middlename', 'like', "%{$search}%")
                        ->orWhere('lastname', 'like', "%{$search}%")
                        ->limit(50)
                        ->get()
                        ->mapWithKeys(fn ($student) => [
                            $student->id => "{$student->lastname}, {$student->firstname}, {$student->middlename}",
                        ])
                        ->toArray()
                    )
                    ->getOptionLabelUsing(fn ($value): ?string => optional(Stud::find($value), fn ($student) => "{$student->lastname}, {$student->firstname}, {$student->middlename}")
                    )
                    ->preload()
                    ->required()
                    ->unique(modifyRuleUsing: function (Unique $rule, callable $get) {
                        $studId = $get('stud_id');
                        $siblingId = $get('sibling_id');

                        return $rule
                            ->where('stud_id', $studId)
                            ->where('sibling_id', $siblingId);
                    },
                        ignoreRecord: true,
                    )
                    ->columnSpanFull()
                    ->validationMessages([
                        'unique' => 'Student already associated with this record.',
                    ]),
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
                Tables\Actions\CreateAction::make()
                    ->modalHeading('Add Sibling')
                    ->disableCreateAnother()
                    ->modalSubmitActionLabel('Save'),
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
