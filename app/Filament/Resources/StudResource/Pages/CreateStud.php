<?php

namespace App\Filament\Resources\StudResource\Pages;

use App\Filament\Resources\StudResource;
use App\Models\User;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Hash;

class CreateStud extends CreateRecord
{
    protected static string $resource = StudResource::class;

    protected static bool $canCreateAnother = false;

    protected function getRedirectUrl(): string
    {
        return $this->previousUrl ?? $this->getResource()::getUrl('index');
    }

    public function getTitle(): string|Htmlable
    {
        return __('Create Student');
    }

    protected function afterCreate(): void
    {
        $studentId = $this->record->studentidn;
        $firstName = $this->record->firstname;
        $middleName = $this->record->middlename;
        $lastName = $this->record->lastname;
        $fullName = "{$firstName} {$middleName} {$lastName}";
        $email = "ptgea@{$studentId}";
        $role = "guest";
        $hashedPassword = Hash::make($studentId); // Hash the student ID for password

        // Check if the user already exists
        $existingUser = User::where('canId', $studentId)->first();

        if ($existingUser) {
            // Notify that the record already exists
            Notification::make()
                ->title('Duplicate Entry')
                ->body("A user with Student ID {$studentId} already exists.")
                ->danger()
                ->send();
        } else {
            // Insert the new user
            User::create([
                'firstname' => $firstName,
                'middlename' => $middleName,
                'lastname' => $lastName,
                'name' => $fullName,
                'email' => $email,
                'role' => $role,
                'canId' => $studentId,
                'password' => $hashedPassword, // Store hashed password
            ]);

            // Notify that the user was created
            Notification::make()
                ->title('User Created')
                ->body("User {$fullName} has been successfully created.")
                ->success()
                ->send();
        }
    }
}
