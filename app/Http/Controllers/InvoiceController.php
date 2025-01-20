<?php

namespace App\Http\Controllers;

use App\Models\Enrollment;
use App\Models\InvoiceRecord;
use App\Models\Stud;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Notifications\Notification;

class InvoiceController extends Controller
{
    /**
     * Print the invoice
     *
     * @param  int  $id
     */
    public function printInvoice($id)
    {
        // Retrieve the enrollment record with relationships
        $payments = Enrollment::with([
            'pays',
            'stud',
            'program',
            'college',
            'schoolyear',
            'collections',
            'yearlevelpayments',
        ])->find($id);

        // Check if the record exists
        if ($payments) {
            // Log the invoice generation
            InvoiceRecord::create([
                'user_id' => auth()->user()->id,
                'pay_id' => $id,
            ]);

            // Fetch sibling information
            $siblingsInformation = Stud::with(['siblings' => function ($query) use ($payments) {
                $query->whereHas('stud.enrollments', function ($enrollmentQuery) use ($payments) {
                    $enrollmentQuery->where('schoolyear_id', $payments->schoolyear_id);
                });
            }])->find($payments->stud_id);

            // Define custom paper size (e.g., 5x5 inches)
            $customPaper = [0, 0, 300, 600];

            // Generate the PDF with a custom paper size
            $pdf = Pdf::loadView('pdf.print_invoice', compact('payments', 'siblingsInformation'))
                ->setPaper($customPaper);

            return $pdf->stream();
        } else {
            // Notify if no record exists
            Notification::make()
                ->title('No invoice record found!')
                ->danger()
                ->send();

            return redirect()->back();
        }
    }

    public function downloadInvoice($id)
    {
        // Retrieve the enrollment record with relationships
        $payments = Enrollment::with([
            'pays',
            'stud',
            'program',
            'college',
            'schoolyear',
            'collections',
            'yearlevelpayments',
        ])->find($id);

        // Check if the record exists
        if ($payments) {
            // Log the invoice generation
            InvoiceRecord::create([
                'user_id' => auth()->user()->id,
                'pay_id' => $id,
            ]);

            // Fetch sibling information
            $siblingsInformation = Stud::with(['siblings' => function ($query) use ($payments) {
                $query->whereHas('stud.enrollments', function ($enrollmentQuery) use ($payments) {
                    $enrollmentQuery->where('schoolyear_id', $payments->schoolyear_id);
                });
            }])->find($payments->stud_id);

            // Define custom paper size (e.g., 5x5 inches)
            $customPaper = [0, 0, 300, 600];

            // Generate the PDF with a custom paper size
            $pdf = Pdf::loadView('pdf.print_invoice', compact('payments', 'siblingsInformation'))
                ->setPaper($customPaper);

            // Download the PDF instead of streaming
            return $pdf->download('invoice_'.$id.'.pdf');
        } else {
            // Notify if no record exists
            Notification::make()
                ->title('No invoice record found!')
                ->danger()
                ->send();

            return redirect()->back();
        }
    }
}
