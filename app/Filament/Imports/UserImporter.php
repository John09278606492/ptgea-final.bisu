<?php

namespace App\Filament\Imports;

use App\Models\Stud;
use App\Models\User;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Facades\Hash;
use Filament\Actions\Imports\Exceptions\RowImportFailedException;
use Filament\Notifications\Notification;
use Illuminate\Support\HtmlString;

class UserImporter extends Importer
{
    protected static ?string $model = User::class;

    private $students;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('canId')
                ->label('Student IDN')
                ->exampleHeader('Student IDN')
                ->numeric()
                ->requiredMapping()
                ->rules(['required', 'max:255']),
            ImportColumn::make('firstname')
                ->label('First Name')
                ->exampleHeader('First Name')
                ->requiredMapping()
                ->fillRecordUsing(function (User $record, string $state): void {
                    $record->firstname = collect(explode(' ', strtolower($state)))
                        ->map(fn($word) => ucfirst($word))
                        ->join(' ');
                })
                ->rules(['required', 'max:255', 'regex:/^[^0-9]*$/']),
            ImportColumn::make('middlename')
                ->ignoreBlankState()
                ->label('Middle Name')
                ->exampleHeader('Middle Name')
                ->fillRecordUsing(function (User $record, string $state): void {
                    $record->middlename = collect(explode(' ', strtolower($state)))
                        ->map(fn($word) => ucfirst($word))
                        ->join(' ');
                })
                ->rules(['nullable', 'max:255', 'regex:/^[^0-9]*$/']),
            ImportColumn::make('lastname')
                ->label('Last Name')
                ->exampleHeader('Last Name')
                ->requiredMapping()
                ->fillRecordUsing(function (User $record, string $state): void {
                    $record->lastname = collect(explode(' ', strtolower($state)))
                        ->map(fn($word) => ucfirst($word))
                        ->join(' ');
                })
                ->rules(['required', 'max:255', 'regex:/^[^0-9]*$/']),
        ];
    }

    public function getValidationMessages(): array
    {
        return [
            'canId.required' => 'The Student IDN field is required.',
            'firstname.required' => 'The First Name field is required.',
            'firstname.regex' => 'The First Name must contain only letters, dashes and spaces.',
            'middlename.regex' => 'The Middle Name must contain only letters, dashes and spaces.',
            'lastname.required' => 'The Last Name field is required.',
            'lastname.regex' => 'The Last Name must contain only letters, dashes and spaces.',
        ];
    }

    private function loadLookups(): void
    {
        if (! $this->students) {
            $this->students = Stud::select('id', 'studentidn')->get();
        }
    }

    public function resolveRecord(): ?User
    {
        $this->loadLookups();
        // Find Student
        $student = $this->students->firstWhere('studentidn', $this->data['canId']);
        if (!$student) {
            throw new RowImportFailedException('No student idn found');
        }

        $firstName = ucwords(strtolower($this->data['firstname'] ?? ''));
        $middleName = ucwords(strtolower($this->data['middle'] ?? ''));
        $lastName = ucwords(strtolower($this->data['lastname'] ?? ''));

        $fullName = trim("$firstName $middleName $lastName");

        return User::firstOrNew(
            [
                'canID' => $student->studentidn,
            ],
            [
                'name' => $fullName,
                'email' => 'ptgea' . '@' . $student->studentidn,
                'password' => Hash::make($student->studentidn),
                'role' => 'guest',
            ]
        );
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $recipient = auth()->user();
        $body = 'Your student account import has completed and ' . number_format($import->successful_rows) . ' ' . str('row')->plural($import->successful_rows) . ' imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $downloadUrl = url("/filament/imports/{$import->id}/failed-rows/download");

            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to import.';

            // Styled download link with independent hover underline effect
            $downloadLink = '<a href="' . $downloadUrl . '" target="_blank" class="text-sm font-semibold no-underline text-danger-600 dark:text-danger-400 hover:underline">
                    Download information about the failed row
                 </a>';

            Notification::make()
                ->title('Import completed')
                ->body(new HtmlString($body . '<br>' . $downloadLink))
                ->danger()
                ->sendToDatabase($recipient);
        }

        return $body;
    }
}
