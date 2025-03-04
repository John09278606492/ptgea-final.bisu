<?php

namespace App\Jobs;

use App\Exports\PaymentRecordExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Filament\Notifications\Notification;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Facades\Log;

class ExportPaymentRecordsJobs implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $user;
    protected $date_from;
    protected $date_to;

    public function __construct($user, $date_from, $date_to)
    {
        $this->user = $user;
        $this->date_from = $date_from;
        $this->date_to = $date_to;
    }

    public function handle()
    {
        $fileName = 'Student-Payment-Records-Export-' . now()->timestamp . '.xlsx';
        $filePath = 'exports/' . $fileName;

        // ✅ Store the export file
        Excel::store(new PaymentRecordExport($this->date_from, $this->date_to), $filePath, 'public');

        Log::info("Export file stored at: " . $filePath);

        // ✅ Generate the public URL


        // ✅ Notify the user
        if ($this->user) {
            Log::info("Sending notification to user: " . $this->user->id);

            $downloadUrl = Storage::url($filePath);

            Notification::make()
                ->title('Student Payment Records Export Ready')
                ->body(new HtmlString(
                    'Your student payment data export has been successfully completed. Click the link below to download your excel file:<br><br>' .
                        '<a href="' . $downloadUrl . '" download style="color: green; font-weight: bold; text-decoration: underline;">
                Download EXCEL File
            </a>'
                ))
                ->success()
                ->sendToDatabase($this->user, isEventDispatched: true);
        } else {
            Log::error("User not found while sending export notification.");
        }
    }
}
