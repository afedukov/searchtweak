<?php

namespace Database\Factories;

use App\Models\SearchSnapshot;
use App\Models\User;
use App\Models\UserFeedback;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserFeedback>
 */
class UserFeedbackFactory extends Factory
{
    protected $model = UserFeedback::class;

    public function definition(): array
    {
        return [
            UserFeedback::FIELD_SEARCH_SNAPSHOT_ID => SearchSnapshot::factory(),
            UserFeedback::FIELD_USER_ID => null,
            UserFeedback::FIELD_GRADE => null,
        ];
    }

    public function graded(int $grade = 1): static
    {
        return $this->state([
            UserFeedback::FIELD_USER_ID => User::factory(),
            UserFeedback::FIELD_GRADE => $grade,
        ]);
    }
}
