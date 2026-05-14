<?php

namespace App\Mail;

use App\Models\Notification;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SystemNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Notification $notification)
    {
    }

    public function build(): self
    {
        return $this->subject($this->notification->titre)
            ->view('emails.notification')
            ->with([
                'notification' => $this->notification,
            ]);
    }
}
