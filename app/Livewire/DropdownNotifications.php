<?php

namespace App\Livewire;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Notifications\Events\BroadcastNotificationCreated;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Component;
use Toaster;

class DropdownNotifications extends Component
{
    /** @var Collection<DatabaseNotification> $notifications */
    public $notifications;

    public bool $hasUnreadNotifications = false;

    protected function getListeners(): array
    {
        return [
            sprintf('echo-private:App.Models.User.%d,.%s', Auth::id(), BroadcastNotificationCreated::class) => 'notify',
        ];
    }

    public function notify(array $data): void
    {
        if (isset($data['message'])) {
            Toaster::info(strip_tags($data['message']));
        }

        $this->mount();
    }

    public function mount(): void
    {
        $this->notifications = Auth::user()->notifications->take(5);
        $this->hasUnreadNotifications = $this->notifications->contains(fn (DatabaseNotification $notification) => $notification->unread());
    }

    public function render(): View
    {
        return view('livewire.dropdown-notifications');
    }

    public function read(DatabaseNotification $notification): void
    {
        if ($notification->read()) {
            return;
        }

        $notification->markAsRead();
        $this->mount();

        if (isset($notification->data['url'])) {
            redirect($notification->data['url']);
        }
    }
}
