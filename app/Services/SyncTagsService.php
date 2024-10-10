<?php

namespace App\Services;

use App\Models\Tag;
use App\Models\TaggableInterface;

class SyncTagsService
{
    public function syncTags(TaggableInterface $model, array $tags): void
    {
        $model->tags()->sync(
            array_column($tags, Tag::FIELD_ID)
        );
    }
}
