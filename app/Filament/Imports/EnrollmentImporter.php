<?php

namespace App\Filament\Imports;

use App\Models\Collection;
use App\Models\College;
use App\Models\Enrollment;
use App\Models\Program;
use App\Models\Schoolyear;
use App\Models\Semester;
use App\Models\Stud;
use App\Models\Yearlevel;
use Filament\Actions\Imports\Exceptions\RowImportFailedException;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Facades\DB;

class EnrollmentImporter extends Importer
{
    protected static ?string $model = Enrollment::class;

    private $students;

    private $colleges;

    private $programs;

    private $yearlevels;

    private $schoolyears;

    private $semesters;

    private $collections;

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
                    $program = Program::query()
                        ->where('program', $data['program'])
                        ->first();
                    if ($program) {
                        return Yearlevel::query()
                            ->where('yearlevel', $state)
                            ->where('program_id', $program->id)
                            ->first();
                    }

                    return null;
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
            $this->semesters = Semester::select('id', 'schoolyear_id', 'semester')->get();
            $this->collections = Collection::select('id', 'semester_id', 'amount', 'description')->get();
        }
    }

    public function resolveRecord(): ?Enrollment
    {
        $this->loadLookups();

        // Find or create College
        $college = $this->colleges->firstWhere(function ($college) {
            return strtolower($college->college) === strtolower($this->data['college']);
        });
        if (!$college) {
            $college = College::create(['college' => $this->data['college']]);
            $this->colleges->push($college);
        }

        // Find or create Program
        $program = $this->programs
            ->where('college_id', $college->id)
            ->firstWhere(function ($program) {
                return strtolower($program->program) === strtolower($this->data['program']);
            });
        if (!$program) {
            $program = Program::create(['college_id' => $college->id, 'program' => $this->data['program']]);
            $this->programs->push($program);
        }

        // Find or create Yearlevel
        $yearlevel = $this->yearlevels
            ->where('program_id', $program->id)
            ->firstWhere('yearlevel', $this->data['yearlevel']);
        if (!$yearlevel) {
            $yearlevel = Yearlevel::create(['program_id' => $program->id, 'yearlevel' => $this->data['yearlevel']]);
            $this->yearlevels->push($yearlevel);
        }

        // Find Schoolyear
        $schoolyear = $this->schoolyears->firstWhere('schoolyear', $this->data['schoolyear']);
        if (!$schoolyear) {
            throw new RowImportFailedException('No school year found');
        }

        // Find Student
        $student = $this->students->firstWhere('studentidn', $this->data['stud']);
        if (!$student) {
            throw new RowImportFailedException('No student idn found');
        }

        // Prepare Enrollment data
        $enrollmentData = [
            'stud_id' => $student->id,
            'schoolyear_id' => $schoolyear->id,
            'college_id' => $college->id,
            'program_id' => $program->id,
            'yearlevel_id' => $yearlevel->id,
        ];

        // Find or create Enrollment
        $enrollment = Enrollment::firstOrNew(
            ['stud_id' => $student->id, 'schoolyear_id' => $schoolyear->id],
            $enrollmentData
        );

        // Handle Semester and Collection relationships
        if ($schoolyear) {
            $semesters = $this->semesters->where('schoolyear_id', $schoolyear->id);

            // Prepare semester and collection relationships
            $semesterRelations = $semesters->map(function ($semester) {
                return ['semester_id' => $semester->id];
            });

            $collectionRelations = $semesters->flatMap(function ($semester) {
                return $this->collections
                    ->where('semester_id', $semester->id)
                    ->map(function ($collection) {
                        return ['collection_id' => $collection->id];
                    });
            });

            // Attach relationships to the Enrollment model
            $enrollment->semesters()->sync($semesterRelations->pluck('semester_id')->toArray());
            $enrollment->collections()->sync($collectionRelations->pluck('collection_id')->toArray());
        }

        // Save the Enrollment model with all relationships
        $enrollment->save();

        return $enrollment;
    }

    // public function resolveRecord(): ?Enrollment
    // {
    //     $this->loadLookups();

    //     $college = $this->colleges->firstWhere(function ($college) {
    //         return strtolower($college->college) === strtolower($this->data['college']);
    //     });

    //     if (! $college) {
    //         $college = College::create(['college' => $this->data['college']]);
    //         $this->colleges->push($college);
    //     }

    //     $program = $this->programs
    //         ->where('college_id', $college->id)
    //         ->firstWhere(function ($program) {
    //             return strtolower($program->program) === strtolower($this->data['program']);
    //         });
    //     if (! $program) {
    //         $program = Program::create(['college_id' => $college->id, 'program' => $this->data['program']]);
    //         $this->programs->push($program);
    //     }

    //     $yearlevel = $this->yearlevels
    //         ->where('program_id', $program->id)
    //         ->firstWhere('yearlevel', $this->data['yearlevel']);
    //     if (! $yearlevel) {
    //         $yearlevel = Yearlevel::create(['program_id' => $program->id, 'yearlevel' => $this->data['yearlevel']]);
    //         $this->yearlevels->push($yearlevel);
    //     }

    //     $schoolyear = $this->schoolyears->firstWhere('schoolyear', $this->data['schoolyear']);
    //     if (! $schoolyear) {
    //         throw new RowImportFailedException('No school year found');
    //     }

    //     $student = $this->students->firstWhere('studentidn', $this->data['stud']);
    //     if (! $student) {
    //         throw new RowImportFailedException('No student idn found');
    //     }

    //     $enrollment = Enrollment::firstOrNew(
    //         [
    //             'stud_id' => $student->id,
    //             'schoolyear_id' => $schoolyear->id,
    //         ],
    //         [
    //             'college_id' => $college->id,
    //             'program_id' => $program->id,
    //             'yearlevel_id' => $yearlevel->id,
    //         ]
    //     );

    //     $enrollment->save();

    //     $enrollmentId = $enrollment->id;

    //     if ($schoolyear) {
    //         $semester = $this->semesters->where('schoolyear_id', $schoolyear->id);
    //         $existingEntry = DB::table('enrollment_semester')->where('enrollment_id', $enrollmentId)->exists();

    //         if ($existingEntry) {
    //             DB::table('enrollment_semester')->where('enrollment_id', $enrollmentId)->delete();
    //         }

    //         foreach ($semester as $semester1) {
    //             DB::table('enrollment_semester')->insert([
    //                 'enrollment_id' => $enrollmentId,
    //                 'semester_id' => $semester1->id,
    //             ]);

    //         }

    //         if ($semester) {
    //             $existingEntry1 = DB::table('collection_enrollment')->where('enrollment_id', $enrollmentId)->exists();
    //             if ($existingEntry1) {
    //                 DB::table('collection_enrollment')->where('enrollment_id', $enrollmentId)->delete();
    //             }
    //             foreach ($semester as $semester1) {
    //                 $collection = $this->collections->where('semester_id', $semester1->id);
    //                 foreach ($collection as $collection1) {
    //                     DB::table('collection_enrollment')->insert([
    //                         'enrollment_id' => $enrollmentId,
    //                         'collection_id' => $collection1->id,
    //                     ]);
    //                 }
    //             }
    //         }
    //     }

    //     return $enrollment;
    // }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your enrollment import has completed and '.number_format($import->successful_rows).' '.str('row')->plural($import->successful_rows).' imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' '.number_format($failedRowsCount).' '.str('row')->plural($failedRowsCount).' failed to import.';
        }

        return $body;
    }
}
