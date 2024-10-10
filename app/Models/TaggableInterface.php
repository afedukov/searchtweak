<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property Collection<Tag> $tags
 */
interface TaggableInterface
{
    public function tags(): BelongsToMany;
}
