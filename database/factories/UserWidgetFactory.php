<?php

namespace Database\Factories;

use App\Livewire\Widgets\GiveFeedbackWidget;
use App\Models\Team;
use App\Models\User;
use App\Models\UserWidget;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserWidget>
 */
class UserWidgetFactory extends Factory
{
    protected $model = UserWidget::class;

    public function definition(): array
    {
        return [
            UserWidget::FIELD_USER_ID => User::factory(),
            UserWidget::FIELD_TEAM_ID => Team::factory(),
            UserWidget::FIELD_WIDGET_CLASS => GiveFeedbackWidget::class,
            UserWidget::FIELD_POSITION => 0,
            UserWidget::FIELD_VISIBLE => true,
            UserWidget::FIELD_SETTINGS => null,
        ];
    }
}
