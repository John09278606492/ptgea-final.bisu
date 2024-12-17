<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StudentResource\Pages;
use App\Models\Collection as ModelsCollection;
use App\Models\Program;
use App\Models\Semester;
use App\Models\Student;
use App\Models\Yearlevel;
use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Collection;

class StudentResource extends Resource
{
    protected static ?string $model = Student::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Student Details')
                    ->description('Please fill in important fields')
                    ->schema([
                        Forms\Components\TextInput::make('studentidn')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('firstname')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('middlename')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('lastname')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('status')
                            ->required()
                            ->maxLength(255),
                    ])
                    ->columns(4),
                Section::make('Student College, ....')
                    ->description('Please fill in important fields')
                    ->schema([
                        Forms\Components\Select::make('college_id')
                            ->relationship('college', 'college')
                            ->searchable()
                            ->preload()
                            ->live()
                            ->required(),
                        Forms\Components\Select::make('program_id')
                            ->options(function (Get $get): Collection {
                                $collegeId = $get('college_id');
                                if (! $collegeId) {
                                    return collect(); // Return empty collection if no school year is selected
                                }

                                return Program::query()
                                    ->where('college_id', $collegeId)
                                    ->pluck('program', 'id');
                            })
                            ->searchable()
                            ->preload()
                            ->live()
                            ->required(),
                        Forms\Components\Select::make('yearlevel_id')
                            ->options(function (Get $get): Collection {
                                $programId = $get('program_id');
                                if (! $programId) {
                                    return collect(); // Return empty collection if no school year is selected
                                }

                                return Yearlevel::query()
                                    ->where('program_id', $programId)
                                    ->pluck('yearlevel', 'id');
                            })
                            ->searchable()
                            ->preload()
                            ->live()
                            ->required(),
                        Forms\Components\Select::make('yearlevelpayment_id')
                            ->relationship('yearlevelpayment', 'id')
                            ->required(),
                    ])
                    ->columns(4),
                Section::make('Student School Year')
                    ->description('Please fill in this section')
                    ->schema([
                        Forms\Components\Select::make('schoolyear_id')
                            ->label('School Year')
                            ->relationship('schoolyear', 'schoolyear')
                            ->searchable()
                            ->preload()
                            ->live()
                            ->reactive()
                            ->afterStateUpdated(function (callable $set, $state, callable $get) {
                                if ($state) {
                                    // Get all semesters for the new school year
                                    $semesterIds = Semester::query()
                                        ->where('schoolyear_id', $state)
                                        ->pluck('id')
                                        ->toArray();

                                    // Set semester_id to include only semesters from the new school year
                                    $set('semester_id', $semesterIds);

                                    // Get all collections associated with the new semesters
                                    $collectionIds = ModelsCollection::query()
                                        ->whereIn('semester_id', $semesterIds)
                                        ->pluck('id')
                                        ->toArray();

                                    // Set collection_id to include only collections from the new semesters
                                    $set('collection_id', $collectionIds);
                                } else {
                                    // Clear semesters and collections if no school year is selected
                                    $set('semester_id', []);
                                    $set('collection_id', []);
                                }
                            })
                            ->required(),

                        Forms\Components\CheckboxList::make('semester_id')
                            ->label('Semesters')
                            ->reactive()
                            ->options(function (Get $get): Collection {
                                $schoolyearId = $get('schoolyear_id');
                                if (! $schoolyearId) {
                                    return collect(); // Return empty collection if no school year is selected
                                }

                                return Semester::query()
                                    ->where('schoolyear_id', $schoolyearId)
                                    ->pluck('semester', 'id');
                            })
                            ->afterStateUpdated(function (callable $set, $state, callable $get) {
                                // Get current semester selection
                                $semesterIds = (array) $state;

                                // Keep only collections associated with the remaining semesters
                                $validCollections = ModelsCollection::query()
                                    ->whereIn('semester_id', $semesterIds)
                                    ->pluck('id')
                                    ->toArray();

                                $currentCollectionIds = (array) $get('collection_id');

                                // Filter current collection_id values to keep only valid ones
                                $filteredCollectionIds = array_intersect($currentCollectionIds, $validCollections);

                                // Update collection_id with valid values
                                $set('collection_id', $filteredCollectionIds);
                            })
                            ->required(),

                        Forms\Components\CheckboxList::make('collection_id')
                            ->label('Semester Fee Types')
                            ->reactive()
                            ->options(function (Get $get): Collection {
                                $semesterIds = $get('semester_id');
                                if (! $semesterIds || empty($semesterIds)) {
                                    return collect(); // Return empty collection if no semesters are selected
                                }

                                return ModelsCollection::query()
                                    ->whereIn('semester_id', $semesterIds)
                                    ->pluck('amount', 'id')
                                    ->mapWithKeys(function ($amount, $id) {
                                        $description = ModelsCollection::find($id)->description; // Assuming 'description' is a column in your ModelsCollection table

                                        // Format amount to two decimal places and add peso sign
                                        $formattedAmount = '₱'.number_format($amount, 2);

                                        return [$id => "$formattedAmount - $description"];
                                    });
                            })
                            ->required(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('schoolyear.id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('semester.id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('collection.id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('college.id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('program.id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('yearlevel.id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('yearlevelpayment.id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('studentidn')
                    ->searchable(),
                Tables\Columns\TextColumn::make('firstname')
                    ->searchable(),
                Tables\Columns\TextColumn::make('middlename')
                    ->searchable(),
                Tables\Columns\TextColumn::make('lastname')
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
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
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStudents::route('/'),
            'create' => Pages\CreateStudent::route('/create'),
            'edit' => Pages\EditStudent::route('/{record}/edit'),
        ];
    }
}
