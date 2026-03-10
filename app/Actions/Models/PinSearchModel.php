<?php

namespace App\Actions\Models;

use App\Models\SearchModel;

class PinSearchModel
{
    /**
     * @param SearchModel $model
     * @param bool $pinned
     *
     * @return void
     */
    public function pin(SearchModel $model, bool $pinned): void
    {
        if ($pinned === true && !$model->isPinnable()) {
            throw new \RuntimeException('Failed to pin model: model is not pinnable');
        }

        if ($pinned === false && !$model->isUnpinnable()) {
            throw new \RuntimeException('Failed to un-pin model: model is not unpinnable');
        }

        $model->pinned = $pinned;
        $model->saveQuietly();
    }
}
