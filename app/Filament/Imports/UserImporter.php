<?php

namespace App\Filament\Imports;

use App\Models\Stud;
use App\Models\User;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Facades\Hash;

class UserImporter extends Importer
{
    protected static ?string $model = User::class;

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
                        ->map(fn ($word) => ucfirst($word))
                        ->join(' ');
                })
                ->rules(['required', 'max:255']),
            ImportColumn::make('middlename')
                ->ignoreBlankState()
                ->label('Middle Name')
                ->exampleHeader('Middle Name')
                ->fillRecordUsing(function (User $record, string $state): void {
                    $record->middlename = collect(explode(' ', strtolower($state)))
                        ->map(fn ($word) => ucfirst($word))
                        ->join(' ');
                })
                ->rules(['max:255']),
            ImportColumn::make('lastname')
                ->label('Last Name')
                ->exampleHeader('Last Name')
                ->requiredMapping()
                ->fillRecordUsing(function (User $record, string $state): void {
                    $record->lastname = collect(explode(' ', strtolower($state)))
                        ->map(fn ($word) => ucfirst($word))
                        ->join(' ');
                })
                ->rules(['required', 'max:255']),
        ];
    }

    public function resolveRecord(): ?User
    {
        $firstName = ucwords(strtolower($this->data['firstname'] ?? ''));
        $middleName = ucwords(strtolower($this->data['middle'] ?? ''));
        $lastName = ucwords(strtolower($this->data['lastname'] ?? ''));

        $fullName = trim("$firstName $middleName $lastName");

        return User::firstOrNew(
            [
                'canID' => $this->data['canId'],
            ],
            [
                'name' => $fullName,
                'email' => 'ptgea'.'@'.$this->data['canId'],
                'password' => Hash::make($this->data['canId']),
                'role' => 'guest',
            ]
        );
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your student account import has completed and ' . number_format($import->successful_rows) . ' ' . str('row')->plural($import->successful_rows) . ' imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to import.';
        }

        return $body;
    }
}
