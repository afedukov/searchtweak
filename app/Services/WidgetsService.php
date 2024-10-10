<?php

namespace App\Services;

use App\Livewire\Widgets\GiveFeedbackWidget;
use App\Livewire\Widgets\LeaderboardWidget;
use App\Livewire\Widgets\TeamsWidget;
use App\Models\User;
use App\Models\UserWidget;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class WidgetsService
{
    public const array DEFAULT_WIDGETS = [
        [
            UserWidget::FIELD_WIDGET_CLASS => GiveFeedbackWidget::class,
            UserWidget::FIELD_VISIBLE => true,
            UserWidget::FIELD_POSITION => 0,
            UserWidget::FIELD_SETTINGS => null,
        ],
        [
            UserWidget::FIELD_WIDGET_CLASS => LeaderboardWidget::class,
            UserWidget::FIELD_VISIBLE => true,
            UserWidget::FIELD_POSITION => 1,
            UserWidget::FIELD_SETTINGS => null,
        ],
        [
            UserWidget::FIELD_WIDGET_CLASS => TeamsWidget::class,
            UserWidget::FIELD_VISIBLE => true,
            UserWidget::FIELD_POSITION => 2,
            UserWidget::FIELD_SETTINGS => null,
        ],
    ];

    /**
     * @param User $user
     *
     * @return Collection<UserWidget>
     */
    public function getUserWidgets(User $user): Collection
    {
        $widgets = $user->widgets()->get();

        if ($widgets->isEmpty()) {
            $widgets = $this->getDefaultWidgets($user);
        }

        return $widgets;
    }

    /**
     * @param User $user
     *
     * @return Collection<UserWidget>
     */
    private function getDefaultWidgets(User $user): Collection
    {
        $widgets = collect();

        foreach (self::DEFAULT_WIDGETS as $widget) {
            $widgets->push(
                new UserWidget([
                        UserWidget::FIELD_ID => Str::uuid()->toString(),
                        UserWidget::FIELD_USER_ID => $user->id,
                        UserWidget::FIELD_TEAM_ID => $user->current_team_id,
                    ] + $widget)
            );
        }

        return $widgets;
    }

    private function attachDefaultWidgets(User $user): void
    {
        $user->widgets()->createMany(
            $this->getDefaultWidgets($user)->toArray()
        );
    }

    public function attachWidget(User $user, string $widgetClass, array $settings = []): UserWidget
    {
        if ($user->widgets()->count() === 0) {
            $this->attachDefaultWidgets($user);
        }

        $user->widgets()->update([
            UserWidget::FIELD_POSITION => DB::raw(UserWidget::FIELD_POSITION . ' + 1'),
        ]);

        return UserWidget::create([
            UserWidget::FIELD_USER_ID => $user->id,
            UserWidget::FIELD_TEAM_ID => $user->current_team_id,
            UserWidget::FIELD_WIDGET_CLASS => $widgetClass,
            UserWidget::FIELD_VISIBLE => true,
            UserWidget::FIELD_POSITION => 0,
            UserWidget::FIELD_SETTINGS => $settings,
        ]);
    }
}
