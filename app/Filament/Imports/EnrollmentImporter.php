<?php

namespace App\Filament\Imports;

use App\Models\College;
use App\Models\Enrollment;
use App\Models\Program;
use App\Models\Schoolyear;
use App\Models\Stud;
use App\Models\Yearlevel;
use Filament\Actions\Imports\Exceptions\RowImportFailedException;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;

class EnrollmentImporter extends Importer
{
    protected static ?string $model = Enrollment::class;

    private $students;

    private $colleges;

    private $programs;

    private $yearlevels;

    private $schoolyears;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('stud')
                ->label('Student IDN')
                ->exampleHeader('Student IDN')
                ->requiredMapping()
                ->relationship(resolveUsing: function (string $state): ?Stud {
                    return Stud::query()
                        ->where('studentidn', $state)
                        ->first();
                })
                ->rules(['required', 'filled', 'present']),
            ImportColumn::make('college')
                ->label('College')
                ->exampleHeader('College')
                ->requiredMapping()
                ->relationship(resolveUsing: function (string $state): ?College {
                    return College::query()
                        ->where('college', $state)
                        ->first();
                })
                ->rules(['required']),
            ImportColumn::make('program')
                ->label('Program')
                ->exampleHeader('Program')
                ->requiredMapping()
                ->relationship(resolveUsing: function (string $state): ?Program {
                    return Program::query()
                        ->where('program', $state)
                        ->first();
                })
                ->rules(['required']),
            ImportColumn::make('yearlevel')
                ->label('Year Level')
                ->exampleHeader('Year Level')
                ->requiredMapping()
                ->relationship(resolveUsing: function (string $state, array $data): ?Yearlevel {
                    // Get the program from the data
                    $program = Program::query()
                        ->where('program', $data['program']) // Use the program from the import data
                        ->first();

                    // If the program exists, find the yearlevel associated with it
                    if ($program) {
                        return Yearlevel::query()
                            ->where('yearlevel', $state)
                            ->where('program_id', $program->id) // Ensure the yearlevel is associated with the program
                            ->first();
                    }

                    return null; // Return null if no program is found
                })
                ->rules(['required']),
            ImportColumn::make('schoolyear')
                ->label('School Year')
                ->exampleHeader('School Year')
                ->requiredMapping()
                ->relationship(resolveUsing: function (string $state): ?Schoolyear {
                    return Schoolyear::query()
                        ->where('schoolyear', $state)
                        ->first();
                })
                ->rules(['required', 'filled', 'present']),
            ImportColumn::make('status')
                ->label('Status')
                ->exampleHeader('Status')
                ->ignoreBlankState()
                ->rules(['max:255']),
        ];
    }

    private function loadLookups(): void
    {
        if (! $this->students) {
            $this->students = Stud::select('id', 'studentidn')->get();
            $this->colleges = College::select('id', 'college')->get();
            $this->programs = Program::select('id', 'college_id', 'program')->get();
            $this->yearlevels = Yearlevel::select('id', 'program_id', 'yearlevel')->get();
            $this->schoolyears = Schoolyear::select('id', 'schoolyear')->get();
        }
    }

    public function resolveRecord(): ?Enrollment
    {
        $this->loadLookups();

        $college = $this->colleges->firstWhere('college', $this->data['college']);
        if (! $college) {
            $college = College::create(['college' => $this->data['college']]);
            $this->colleges->push($college); // Update lookup cache
        }

        $program = $this->programs
            ->where('college_id', $college->id)
            ->firstWhere('program', $this->data['program']);
        if (! $program) {
            $program = Program::create(['college_id' => $college->id, 'program' => $this->data['program']]);
            $this->programs->push($program); // Update lookup cache
        }

        $yearlevel = $this->yearlevels
            ->where('program_id', $program->id)
            ->firstWhere('yearlevel', $this->data['yearlevel']);
        if (! $yearlevel) {
            $yearlevel = Yearlevel::create(['program_id' => $program->id, 'yearlevel' => $this->data['yearlevel']]);
            $this->yearlevels->push($yearlevel); // Update lookup cache
        }

        $schoolyear = $this->schoolyears->firstWhere('schoolyear', $this->data['schoolyear']);
        if (! $schoolyear) {
            // throw new \Exception('School year not found: '.$this->data['schoolyear']);
            throw new RowImportFailedException('No school year found');
        }

        $student = $this->students->firstWhere('studentidn', $this->data['stud']);
        if (! $student) {
            // throw new \Exception('Student not found: '.$this->data['stud']);
            throw new RowImportFailedException('No student idn found');
        }

        return Enrollment::firstOrNew(
            [
                'stud_id' => $student->id,
                'schoolyear_id' => $schoolyear->id,
            ]
        );

        return new Enrollment;
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your enrollment import has completed and '.number_format($import->successful_rows).' '.str('row')->plural($import->successful_rows).' imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' '.number_format($failedRowsCount).' '.str('row')->plural($failedRowsCount).' failed to import.';
        }

        return $body;
    }
}
