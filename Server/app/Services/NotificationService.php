<?php

namespace App\Services;

use App\Events\NotificationCreated;
use App\Mail\SystemNotificationMail;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class NotificationService
{
    public function sendToUser(
        User $user,
        string $title,
        string $message,
        string $type = 'info',
        array $data = [],
        bool $sendMail = true
    ): Notification {
        $notification = Notification::create([
            'user_id' => $user->id,
            'titre' => $title,
            'message' => $message,
            'type' => $type,
            'data' => $data,
        ]);

        event(new NotificationCreated($notification));

        if ($sendMail && !empty($user->email)) {
            try {
                Mail::to($user->email)->send(new SystemNotificationMail($notification));
            } catch (\Throwable $exception) {
                Log::warning('Email notification failed: ' . $exception->getMessage(), [
                    'notification_id' => $notification->id,
                    'user_id' => $user->id,
                ]);
            }
        }

        return $notification;
    }
}
