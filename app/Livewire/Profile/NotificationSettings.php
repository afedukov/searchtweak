<?php

namespace App\Livewire\Profile;

use App\Notifications\EvaluationFinishNotification;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Illuminate\View\View;
use Masmerise\Toaster\Toaster;

class NotificationSettings extends Component
{
    public bool $newsletter = true;
    public bool $evaluationFinished = true;

    public function mount(): void
    {
        $user = Auth::user();

        $this->newsletter = $user->newsletter;
        $this->evaluationFinished = $user->isSubscribedToNotification(EvaluationFinishNotification::class);
    }

    public function render(): View
    {
        return view('livewire.profile.notification-settings');
    }

    public function save(): void
    {
        $user = Auth::user();

        try {
            $user->newsletter = $this->newsletter;
            $user->save();

            $user->updateNotificationSubscription(EvaluationFinishNotification::class, $this->evaluationFinished);

            $this->dispatch('saved');
        } catch (\Exception) {
            Toaster::error('Failed to save notification settings');
        }
    }
}
