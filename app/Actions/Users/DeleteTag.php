<?php

namespace App\Actions\Users;

use App\Models\EvaluationTag;
use App\Models\Team;
use App\Models\UserTag;
use Illuminate\Support\Facades\Gate;

class DeleteTag
{
    public function delete(Team $team, int $tagId): void
    {
        Gate::authorize('manageUserTags', $team);

        $tag = $team->tags()->find($tagId);
        if (!$tag) {
            throw new \InvalidArgumentException('Tag not found.');
        }

        $isAssignedToUser = UserTag::query()
            ->where(UserTag::FIELD_TAG_ID, $tagId)
            ->exists();

        if ($isAssignedToUser) {
            throw new \InvalidArgumentException('Tag is assigned to a user. Please unassign it first.');
        }

        $isAssignedToEvaluation = EvaluationTag::query()
            ->where(EvaluationTag::FIELD_TAG_ID, $tagId)
            ->exists();

        if ($isAssignedToEvaluation) {
            throw new \InvalidArgumentException('Tag is assigned to an evaluation. Please unassign it first.');
        }

        $tag->delete();
    }
}
