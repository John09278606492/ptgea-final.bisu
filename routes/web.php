<?php

use App\Models\Pay;
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
});

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
