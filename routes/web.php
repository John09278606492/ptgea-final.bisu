<?php

use App\Filament\Pages\StudentInformation;
use App\Http\Controllers\InvoiceController;
use App\Models\Pay;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
})->name('welcome');

Route::get('/student-information', StudentInformation::class)->name('student.information');

Route::get('/student-information', StudentInformation::class)->name('student_information');

Route::get('/receipt/{pay}', function (Pay $pay) {
    $receiptData = [
        'id' => $pay->id,
        'amount_formatted' => 'PHP'.number_format($pay->amount, 2),
        'status' => $pay->status,
        'date' => $pay->created_at->format('M. d, Y g:i a'),
        'student' => $pay->enrollment->stud->only(['id', 'lastname', 'firstname', 'middlename']),
    ];

    return view('receipts.payment', $receiptData);
})->name('generate-receipt');

Route::get('/print-invoice/{id}', [InvoiceController::class, 'printInvoice'])
    ->name('PRINT.INVOICE');

Route::get('/export-records/{id?}', [InvoiceController::class, 'exportRecord'])
    ->name('EXPORT.RECORDS');

Route::get('/export-records-all', [InvoiceController::class, 'exportRecordAll'])
    ->name('EXPORT.RECORDS.ALL');


Route::get('/download-invoice/{id}', [InvoiceController::class, 'downloadInvoice'])
    ->name('PRINT.INVOICE.DOWNLOAD');
