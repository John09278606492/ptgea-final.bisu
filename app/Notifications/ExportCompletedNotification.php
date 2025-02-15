<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\DatabaseMessage;

class ExportCompletedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $filePath;

    public function __construct($filePath)
    {
        $this->filePath = $filePath;
    }

    public function via($notifiable)
    {
        return ['database']; // Save the notification in Filament's database notifications
    }

    public function toDatabase($notifiable)
    {
        return [
            'title' => 'Export Completed',
            'body' => 'Your student payment export is ready for download.',
            'file_path' => $this->filePath,
        ];
    }
}
