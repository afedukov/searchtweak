<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Notifications\SystemNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

class SystemNotificationCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:system-notification {message} {--icon=} {--url=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send system notification to all users';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $message = $this->argument('message');
        $icon = $this->option('icon');
        $url = $this->option('url');

        $this->info('Sending system notification to all users ...');

        $users = User::all();

        Notification::send($users, new SystemNotification($message, $icon, $url));

        $this->info(sprintf('System notification sent to %d users.', $users->count()));

        return self::SUCCESS;
    }
}
