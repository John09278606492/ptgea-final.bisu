<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EnrollmentResource\Pages;
use App\Filament\Resources\EnrollmentResource\RelationManagers;
use App\Filament\Resources\EnrollmentResource\RelationManagers\PaysRelationManager;
use App\Models\Collection as ModelsCollection;
use App\Models\College;
use App\Models\Enrollment;
use App\Models\Program;
use App\Models\Schoolyear;
use App\Models\Semester;
use App\Models\Yearlevel;
use App\Models\Yearlevelpayments;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Guava\FilamentModalRelationManagers\Actions\Table\RelationManagerAction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\HtmlString;
use stdClass;

class EnrollmentResource extends Resource
{
    protected static ?string $model = Enrollment::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $breadcrumb = 'Student Payment';

    protected static ?string $navigationLabel = 'Student Payment';

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::get()->filter(function ($enrollment) {
            $balance = $enrollment->balance; // Calls the `getBalanceAttribute` accessor

            return $balance === 'No Payments' || floatval(str_replace(['₱', ','], '', $balance)) > 0;
        })->count();
    }

    protected static ?string $navigationBadgeTooltip = 'Not fully paid';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Student Details')
                    ->relationship('stud', 'id')
                    ->schema([
                        Forms\Components\Grid::make()
                            ->schema([
                                Forms\Components\TextInput::make('studentidn')
                                    ->label('Student IDN')
                                    ->numeric()
                                    ->minValue(0)
                                    ->minLength(6)
                                    ->maxLength(15)
                                    ->required()
                                    ->maxLength(255),
                            ])
                            ->columnStart(1),
                        Forms\Components\TextInput::make('firstname')
                            ->label('First Name')
                            ->required()
                            ->maxLength(255)
                            ->extraInputAttributes(['onInput' => 'this.value = this.value.replace(/\\b\\w/g, char => char.toUpperCase())']),
                        Forms\Components\TextInput::make('middlename')
                            ->label('Middle Name')
                            ->maxLength(255)
                            ->extraInputAttributes(['onInput' => 'this.value = this.value.replace(/\\b\\w/g, char => char.toUpperCase())']),
                        Forms\Components\TextInput::make('lastname')
                            ->label('Last Name')
                            ->required()
                            ->maxLength(255)
                            ->extraInputAttributes(['onInput' => 'this.value = this.value.replace(/\\b\\w/g, char => char.toUpperCase())']),
                        Forms\Components\Select::make('status')
                            ->options([
                                'active' => 'Active',
                                'inactive' => 'Inactive',
                                'graduated' => 'Graduated',
                            ])
                            ->required(),
                    ]),
                Forms\Components\Section::make('Student Course')
                    ->schema([
                        Forms\Components\Select::make('college_id')
                            ->label('College')
                            ->options(College::all()->pluck('college', 'id'))
                            ->preload()
                            ->live()
                            ->afterStateUpdated(function (Set $set) {
                                $set('program_id', null);
                                $set('yearlevel_id', null);
                                $set('yearlevelpayment_id', []);
                            })
                            ->searchable()
                            ->required(),
                        Forms\Components\Select::make('program_id')
                            ->label('Program')
                            ->options(fn (Get $get): Collection => Program::query()
                                ->where('college_id', $get('college_id'))
                                ->pluck('program', 'id'))
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(function (Set $set) {
                                $set('yearlevel_id', null);
                                $set('yearlevelpayment_id', []);
                            })
                            ->required(),
                        Forms\Components\Select::make('yearlevel_id')
                            ->label('Year Level')
                            ->options(fn (Get $get): Collection => Yearlevel::query()
                                ->where('program_id', $get('program_id'))
                                ->pluck('yearlevel', 'id'))
                            ->searchable()
                            ->preload()
                            ->live()
                            ->reactive()
                            ->afterStateUpdated(fn (Set $set) => $set('yearlevelpayment_id', []))
                            ->required(),
                        CheckboxList::make('yearlevelpayment')
                            ->label('Year Level Fee')
                            ->inlineLabel()
                            ->relationship('yearlevelpayments', 'amount') // Define the relationship and the display column
                            ->options(fn (Get $get): array => Yearlevelpayments::query()
                                ->where('yearlevel_id', $get('yearlevel_id'))
                                ->get()
                                ->mapWithKeys(fn ($payment) => [
                                    $payment->id => '₱'.number_format($payment->amount, 2), // Only amount here
                                ])
                                ->toArray())
                            ->descriptions(fn (Get $get): array => Yearlevelpayments::query()
                                ->where('yearlevel_id', $get('yearlevel_id'))
                                ->get()
                                ->mapWithKeys(fn ($payment) => [
                                    $payment->id => new HtmlString(
                                        $payment->description
                                            ? e($payment->description)
                                            : '<em>No description available.</em>' // Use italics for no description
                                    ),
                                ])
                                ->toArray())
                            ->live()
                            ->afterStateUpdated(function ($state, Set $set) {
                                // Keep the current state as is without clearing other selections
                                if (! is_array($state)) {
                                    $set('yearlevelpayments', []);
                                }
                            })
                            ->columns(4),
                    ])
                    ->columns(2),
                Forms\Components\Section::make('Student School Year')
                    ->schema([
                        Forms\Components\Grid::make()
                            ->schema([
                                Forms\Components\Select::make('schoolyear_id')
                                    ->label('School Year')
                                    ->options(Schoolyear::all()->pluck('schoolyear', 'id'))
                                    ->preload()
                                    ->searchable()
                                    ->live()
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, Set $set) {
                                        $set('semester_id', []);
                                        $set('collection_id', []);
                                    })
                                    ->required(),
                            ])
                            ->columnStart(1),
                        Forms\Components\CheckboxList::make('semester_id')
                            ->label('Semester')
                            ->inlineLabel()
                            ->relationship('semesters', 'semester')
                            ->options(fn (Get $get): array => Semester::query()
                                ->where('schoolyear_id', $get('schoolyear_id'))
                                ->pluck('semester', 'id')
                                ->toArray())
                            ->live()
                            ->reactive()
                            ->afterStateUpdated(function ($state, Set $set) {
                                $set('collection_id', []);
                            })
                            ->columns(2)
                            ->gridDirection('row'),
                        CheckboxList::make('collection_id')
                            ->label('Semester Fee Type')
                            ->inlineLabel()
                            ->relationship('collections', 'amount') // Adjusted to match the relationship name and attribute in your model
                            ->options(fn (Get $get): array => ModelsCollection::query()
                                ->whereIn('semester_id', $get('semester_id'))
                                ->get()
                                ->mapWithKeys(fn ($collection) => [
                                    $collection->id => '₱'.number_format($collection->amount, 2), // Only amount here
                                ])
                                ->toArray())
                            ->descriptions(fn (Get $get): array => ModelsCollection::query()
                                ->whereIn('semester_id', $get('semester_id'))
                                ->with('semester') // Eager load the semester relationship
                                ->get()
                                ->mapWithKeys(fn ($collection) => [
                                    $collection->id => new HtmlString(
                                        ($collection->description
                                            ? e($collection->description)
                                            : '<em>No description available.</em>') // Payment description
                                        .'<br>'
                                        .'<small>Semester: '.e(optional($collection->semester)->semester ?? 'Unknown Semester').'</small>' // Add semester type
                                    ),
                                ])
                                ->toArray())
                            ->live()
                            ->reactive()
                            ->afterStateUpdated(function ($state, Set $set) {
                                // Keep the current state as is without clearing other selections
                                if (! is_array($state)) {
                                    $set('collection_id', []);
                                }
                            })
                            ->columns(2)
                            ->gridDirection('row'),

                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        $today = Carbon::today();
        $defaultSchoolYearId = Schoolyear::where('startDate', '<=', $today)
            ->where('endDate', '>=', $today)
            ->value('id');

        return $table
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
                    ->label('Studen IDN')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('stud.full_name')
                    ->label('Complete Name')
                    ->weight(FontWeight::Bold)
                    ->sortable(['lastname', 'firstname', 'middlename'])
                    ->searchable([
                        'lastname', 'firstname', 'middlename',
                    ]),
                Tables\Columns\TextColumn::make('college.college')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('program.program')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('yearlevel.yearlevel')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('schoolyear.schoolyear')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('balance')
                    ->label('Balance')
                    ->badge()
                    ->weight(FontWeight::Bold)
                    ->color(function ($record) {
                        if ($record->balance === 'No Payments') {
                            return 'gray';
                        }
                        $balance = (float) str_replace(['₱', ','], '', $record->balance);
                        if ($balance > 0) {
                            return 'danger';
                        } elseif ($balance == 0) {
                            return 'success';
                        } elseif ($balance < 0) {
                            return 'warning';
                        }
                    }),
                Tables\Columns\TextColumn::make('stud.status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'inactive' => 'danger',
                        'graduated' => 'gray',
                    })
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
                SelectFilter::make('schoolyear')
                    ->label('School Year')
                    ->relationship('schoolyear', 'schoolyear')
                    ->default($defaultSchoolYearId ?? 'All')
                    ->searchable()
                    ->preload(),
                Filter::make('created_at')
                    ->form([
                        Select::make('college_id')
                            ->label('College')
                            ->placeholder('All')
                            ->options(College::all()->pluck('college', 'id'))
                            ->preload()
                            ->reactive()
                            ->afterStateUpdated(function (Set $set) {
                                $set('program_id', null);
                                $set('yearlevel_id', null);
                            })
                            ->searchable(),
                        Select::make('program_id')
                            ->label('Program')
                            ->placeholder('All')
                            ->options(fn (Get $get) => Program::query()
                                ->where('college_id', $get('college_id'))
                                ->pluck('program', 'id'))
                            ->reactive()
                            ->afterStateUpdated(function (Set $set) {
                                $set('yearlevel_id', null);
                            })
                            ->preload()
                            ->searchable(),
                        Select::make('yearlevel_id')
                            ->label('Year Level')
                            ->placeholder('All')
                            ->options(fn (Get $get) => Yearlevel::query()
                                ->where('program_id', $get('program_id'))
                                ->pluck('yearlevel', 'id'))
                            ->reactive()
                            ->preload()
                            ->searchable(),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['college_id'] ?? null,
                                fn (Builder $query, $collegeId) => $query->where('college_id', $collegeId)
                            )
                            ->when(
                                $data['program_id'] ?? null,
                                fn (Builder $query, $programId) => $query->where('program_id', $programId)
                            )
                            ->when(
                                $data['yearlevel_id'] ?? null,
                                fn (Builder $query, $yearlevelId) => $query->where('yearlevel_id', $yearlevelId)
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];

                        if (! empty($data['college_id'])) {
                            $indicators['college_id'] = 'College: '.College::find($data['college_id'])->college ?? 'N/A';
                        }

                        if (! empty($data['program_id'])) {
                            $indicators['program_id'] = 'Program: '.Program::find($data['program_id'])->program ?? 'N/A';
                        }

                        if (! empty($data['yearlevel_id'])) {
                            $indicators['yearlevel_id'] = 'Year Level: '.Yearlevel::find($data['yearlevel_id'])->yearlevel ?? 'N/A';
                        }

                        return $indicators;
                    })
                    ->columns(3)
                    ->columnSpan(3)

            ], layout: FiltersLayout::AboveContent)->filtersFormColumns(4)
            ->deferLoading()
            ->actions([
                // Tables\Actions\ViewAction::make()
                //     ->color('primary')
                //     ->modalHeading('Student Academic Information'),
                Tables\Actions\Action::make('viewReceipt')
                    ->label('Invoice')
                    ->color('cyan')
                    ->icon('heroicon-m-document-text')
                    ->url(fn ($record) => self::getUrl('invoice', ['record' => $record->id])),
                RelationManagerAction::make('pays-relation-manager')
                    ->label('Pay')
                    ->icon('heroicon-m-banknotes')
                    ->color('success')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close')
                    ->modalHeading('')
                    ->relationManager(PaysRelationManager::make()),
                Tables\Actions\EditAction::make()
                    ->label('Edit')
                    ->color('warning'),
            ])
            // ->bulkActions([
            //     Tables\Actions\BulkActionGroup::make([
            //         Tables\Actions\DeleteBulkAction::make(),
            //     ]),
            // ])
            ->emptyStateHeading('No records');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Personal Information')
                    ->schema([
                        TextEntry::make('stud.studentidn')
                            ->label('I.D No.'),
                        TextEntry::make('stud.full_name')
                            ->label('Full Name'),
                    ]),
                Section::make('Course')
                    ->schema([
                        TextEntry::make('college.college')
                            ->label('College'),
                        TextEntry::make('program.program')
                            ->label('Program'),
                        TextEntry::make('yearlevel.yearlevel')
                            ->label('Year Level'),
                        TextEntry::make('formatted_yearlevel_payments')
                            ->label('Fee Type'),
                    ])
                    ->columns(4),
                Section::make('School Year')
                    ->schema([
                        TextEntry::make('schoolyear.schoolyear')
                            ->label('School Year'),
                        TextEntry::make('semesters.semester')
                            ->label('Semesters'),
                        TextEntry::make('formatted_collections')
                            ->label('Fee Type'),
                    ])
                    ->columns(4),
                Section::make('Totals')
                    ->schema([
                        TextEntry::make('total_payments')
                            ->label('Amount Payable')
                            ->default(fn ($record) => $record ? $record->total_payments : '₱0.00'),
                        TextEntry::make('total_pays_amount')
                            ->label('Amount Paid')
                            ->default(fn ($record) => $record ? '₱'.number_format($record->pays->sum('amount'), 2) : '₱0.00'),
                        TextEntry::make('balance')
                            ->label('Remaining Balance')
                            ->default(fn ($record) => $record ? '₱'.number_format(
                                ($record->collections()->sum('amount') + $record->yearlevelpayments()->sum('amount')) - $record->pays->sum('amount'),
                                2
                            ) : '₱0.00'),
                    ])
                    ->columns(4),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\PaysRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEnrollments::route('/'),
            'create' => Pages\CreateEnrollment::route('/create'),
            'edit' => Pages\EditEnrollment::route('/{record}/edit'),
            'invoice' => Pages\Invoice::route('/{record}/invoice'),
        ];
    }
}
