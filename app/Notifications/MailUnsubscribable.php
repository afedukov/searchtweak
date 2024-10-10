<?php

namespace App\Notifications;

use App\Models\User;

trait MailUnsubscribable
{
    public function via(object $notifiable): array
    {
        $channels = ['database', 'broadcast'];

        if ($notifiable instanceof User && $notifiable->isSubscribedToNotification(static::class)) {
            $channels[] = 'mail';
        }

        return $channels;
    }
}
