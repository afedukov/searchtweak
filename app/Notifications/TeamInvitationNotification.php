<?php

namespace App\Notifications;

use App\Models\TeamInvitation;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class TeamInvitationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(protected readonly User $user, protected readonly TeamInvitation $invitation)
    {
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database', 'broadcast'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->markdown('emails.team-invitation', [
                'invitation' => $this->invitation,
                'acceptUrl' => URL::signedRoute('team-invitations.accept', ['invitation' => $this->invitation]),
            ])
            ->subject(__('Team Invitation'));
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'from' => $this->user->id,
            'team' => $this->invitation->team_id,
            'icon' => 'fa-solid fa-user-group',
            'url' => URL::signedRoute('team-invitations.accept', [
                'invitation' => $this->invitation,
            ]),
            'message' => $this->renderNotificationMessage($notifiable),
        ];
    }

    private function renderNotificationMessage(object $notifiable): string
    {
        return view('notifications.team-invitation', [
            'from' => $this->user,
            'invitation' => $this->invitation,
            'notifiable' => $notifiable,
        ])->render();
    }
}
