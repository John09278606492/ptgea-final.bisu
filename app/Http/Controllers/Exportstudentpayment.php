<?php

namespace App\Http\Controllers;

use App\Exports\AllStudentExport;
use App\Exports\PaymentRecordExport;
use App\Exports\StudentpaymentExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class Exportstudentpayment extends Controller
{
    /**
     * Export student payments based on filters
     *
     * @param  int|null  $schoolyear_id
     * @param  int|null  $college_id
     * @param  int|null  $program_id
     * @param  int|null  $yearlevel_id
     * @param  string|null  $status
     */
    public function export(Request $request)
    {
        $schoolyear_id = $request->input('schoolyear_id');
        $college_id = $request->input('college_id');
        $program_id = $request->input('program_id');
        $yearlevel_id = $request->input('yearlevel_id');
        $status = $request->input('status');

        return Excel::download(
            new StudentpaymentExport(
                $schoolyear_id ? (int)$schoolyear_id : null,
                $college_id ? (int)$college_id : null,
                $program_id ? (int)$program_id : null,
                $yearlevel_id ? (int)$yearlevel_id : null,
                $status
            ),
            'Student-Payment&Balance-Record-Export.xlsx',
            \Maatwebsite\Excel\Excel::XLSX
        );
    }

    public function exportPaymentRecord(Request $request)
    {
        $startDate = $request->input('date_from');
        $endDate = $request->input('date_to');

        $date_from = null;
        $date_to = null;

        // Ensure both startDate and endDate are provided
        if (!empty($startDate) && !empty($endDate)) {
            try {
                // Check if the format is already Y-m-d
                $date_from = \Carbon\Carbon::parse(trim($startDate))->startOfDay();
                $date_to = \Carbon\Carbon::parse(trim($endDate))->endOfDay();
            } catch (\Exception $e) {
                Log::error('Invalid date format: ' . $startDate . ' - ' . $endDate);
                return redirect()->back()->withErrors(['error' => 'Invalid date format.']);
            }
        }

        return Excel::download(
            new PaymentRecordExport($date_from, $date_to),
            'Student-Payment-Record-Export.xlsx',
            \Maatwebsite\Excel\Excel::XLSX
        );
    }
}
