<?php

namespace App\Filament\Imports;

use App\Models\Stud;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class StudImporter extends Importer
{
    protected static ?string $model = Stud::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('studentidn')
                ->label('Student IDN')
                ->exampleHeader('Student IDN')
                ->numeric()
                ->requiredMapping()
                ->rules(['required', 'max:255']),
            ImportColumn::make('firstname')
                ->label('First Name')
                ->exampleHeader('First Name')
                ->requiredMapping()
                ->rules(['required', 'max:255'])
                ->fillRecordUsing(function (Stud $record, string $state): void {
                    $record->firstname = collect(explode(' ', strtolower($state)))
                        ->map(fn ($word) => ucfirst($word))
                        ->join(' ');
                }),
            ImportColumn::make('middlename')
                ->ignoreBlankState()
                ->label('Middle Name')
                ->exampleHeader('Middle Name')
                ->rules(['max:255'])
                ->fillRecordUsing(function (Stud $record, string $state): void {
                    $record->middlename = collect(explode(' ', strtolower($state)))
                        ->map(fn ($word) => ucfirst($word))
                        ->join(' ');
                }),
            ImportColumn::make('lastname')
                ->label('Last Name')
                ->exampleHeader('Last Name')
                ->requiredMapping()
                ->rules(['required', 'max:255'])
                ->fillRecordUsing(function (Stud $record, string $state): void {
                    $record->lastname = collect(explode(' ', strtolower($state)))
                        ->map(fn ($word) => ucfirst($word))
                        ->join(' ');
                }),
            ImportColumn::make('status')
                ->ignoreBlankState()
                ->label('Status')
                ->exampleHeader('Status')
                ->rules(['max:255']),
        ];
    }

    public function resolveRecord(): ?Stud
    {

        return Stud::firstOrNew([
            'studentidn' => $this->data['studentidn'],
        ]);
        // Find or create the student record
        // $students = Stud::firstOrNew([
        //     'studentidn' => $this->data['studentidn'],
        // ]);

        // // Update student details
        // $students->firstname = $this->data['firstname'] ?? $students->firstname;
        // $students->middlename = $this->data['middlename'] ?? $students->middlename;
        // $students->lastname = $this->data['lastname'] ?? $students->lastname;

        // // Save the student record
        // $students->save();

        // return $students;
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your student import has completed and '.number_format($import->successful_rows).' '.str('row')->plural($import->successful_rows).' imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' '.number_format($failedRowsCount).' '.str('row')->plural($failedRowsCount).' failed to import.';
        }

        return $body;
    }
}
