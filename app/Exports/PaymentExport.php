<?php

namespace App\Exports;

use App\Models\Pay;
use Maatwebsite\Excel\Concerns\FromCollection;

class PaymentExport implements FromCollection
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        return Pay::all();
    }
}
