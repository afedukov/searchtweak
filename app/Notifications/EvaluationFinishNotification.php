<?php

namespace App\Notifications;

use App\Models\SearchEvaluation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EvaluationFinishNotification extends Notification implements ShouldQueue
{
    use Queueable, MailUnsubscribable;

    /**
     * Create a new notification instance.
     */
    public function __construct(protected readonly SearchEvaluation $evaluation)
    {
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'icon' => 'fa-solid fa-check',
            'url' => route('evaluation', $this->evaluation->id),
            'message' => sprintf('Evaluation <b>%s</b> finished', $this->evaluation->name),
        ];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->markdown('emails.evaluation-finish', [
                'evaluation' => $this->evaluation
            ])
            ->subject(__('Evaluation :name finished', ['name' => $this->evaluation->name]));
    }
}
