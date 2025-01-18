<?php

namespace App\Filament\Pages;

use App\Models\Enrollment;
use App\Models\Schoolyear;
use App\Models\Sibling;
use App\Models\Stud;
use Carbon\Carbon;
use Filament\Pages\Page;

class StudentInformation extends Page
{
    public $activeTab = 'student info';

    public $studentIDN;

    public $studentInfo = null;  // Initialize as null to prevent unexpected errors

    public $sibling_id;

    public $finalSib;

    public $defaultSchoolYearId;

    public $today;

    public $enrollmentId;

    public $searchStudent = '';  // Property for searching students

    public $students = [];

    public $payments = null;  // Initialize as null

    public $siblingsInformation = null;  // Initialize as null

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()->role == 'guest';
    }

    public static function canAccess(): bool
    {
        return auth()->user()->role == 'guest';
    }

    public function mount()
    {
        $this->studentIDN = auth()->user()->canId ?? null;

        if (! $this->studentIDN) {
            return; // Exit early if no student IDN is found
        }

        $this->today = Carbon::today();
        $this->defaultSchoolYearId = Schoolyear::where('startDate', '<=', $this->today)
            ->where('endDate', '>=', $this->today)
            ->value('id');

        if (! $this->defaultSchoolYearId) {
            return; // Exit early if no matching school year is found
        }

        $this->studentInfo = Stud::with(['siblings'])
            ->where('studentidn', $this->studentIDN)
            ->first();

        if (! $this->studentInfo) {
            return; // Exit early if no student info is found
        }

        $this->enrollmentId = Enrollment::where('stud_id', $this->studentInfo->id)
            ->where('schoolyear_id', $this->defaultSchoolYearId)
            ->value('id');

        if (! $this->enrollmentId) {
            return; // Exit early if no enrollment record is found
        }

        $this->payments = Enrollment::with([
            'pays',
            'stud',
            'program',
            'college',
            'schoolyear',
            'collections',
            'yearlevelpayments',
        ])->find($this->enrollmentId);

        if (! $this->payments) {
            return; // Exit early if no payment data is found
        }

        $this->siblingsInformation = Sibling::where('stud_id', $this->payments->stud_id)->value('sibling_id');

        $this->finalSib = Enrollment::with(['stud']) // Include the stud relationship
            ->where('stud_id', $this->siblingsInformation)
            ->where('schoolyear_id', $this->defaultSchoolYearId)
            ->get();

        // $this->siblingsInformation = Stud::with(['siblings' => function ($query) {
        //     $query->whereHas('stud.enrollments', function ($enrollmentQuery) {
        //         $enrollmentQuery->where('schoolyear_id', $this->payments->schoolyear_id);
        //     });
        // }])->find($this->payments->stud_id);
    }

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.pages.student-information';
}
