<?php

namespace App\Http\Controllers;

use App\Models\Enrollment;
use App\Models\InvoiceRecord;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Notifications\Notification;

class InvoiceController extends Controller
{
    /**
     * printing invoice
     *
     * @param  mixed  $id
     */
    public function printInvoice($id)
    {
        $payments = Enrollment::with('pays')->find($id);
        if ($payments) {
            InvoiceRecord::create([
                'user_id' => auth()->user()->id,
                'pay_id' => $id,
            ]);

            $pdf = Pdf::loadView('pdf.print_invoice', compact('payments'));

            return $pdf->stream();
        } else {
            Notification::make()
                ->title('No invoice record found!')
                ->danger()
                ->send();

            return redirect()->back();
        }
    }
}
