<?php

namespace App\Actions\Users;

use App\Events\UserTagsChangedEvent;
use App\Models\Tag;
use App\Models\Team;
use App\Models\User;
use App\Models\UserTag;
use App\Services\Evaluations\UserFeedbackService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class SyncUserTags
{
    public function sync(User $user, Team $team, array $tags): void
    {
        Gate::authorize('manageUserTags', $team);

        $tagIds = array_column($tags, 'id');

        $this->validate($tagIds, $team->id);

        $teamTagIds = $team->tags()->pluck(Tag::FIELD_ID);

        $user->tags()
            ->wherePivotIn(UserTag::FIELD_TAG_ID, $teamTagIds)
            ->sync($tagIds);

        UserFeedbackService::flushUngradedSnapshotsCountCache($team->id);

        UserTagsChangedEvent::dispatch($user->id);
    }

    private function validate(array $tagIds, int $teamId): void
    {
        $validator = Validator::make(['tagIds' => $tagIds], [
            'tagIds' => ['array'],
            'tagIds.*' => ['integer', Rule::exists('tags', Tag::FIELD_ID)->where(Tag::FIELD_TEAM_ID, $teamId)],
        ]);

        if ($validator->fails()) {
            throw new \InvalidArgumentException('Invalid tags.');
        }
    }
}
