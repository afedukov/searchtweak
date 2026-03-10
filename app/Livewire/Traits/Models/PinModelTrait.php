<?php

namespace App\Livewire\Traits\Models;

use App\Actions\Models\PinSearchModel;
use App\Models\SearchModel;
use Illuminate\Support\Facades\Gate;
use Masmerise\Toaster\Toaster;

trait PinModelTrait
{
    public function pinModel(SearchModel $model, bool $pinned, PinSearchModel $action): void
    {
        try {
            Gate::authorize('pin', $model);

            $action->pin($model, $pinned);

            Toaster::success(sprintf('Model %s.', $pinned ? 'pinned to top' : 'un-pinned'));
        } catch (\Exception $e) {
            Toaster::error($e->getMessage());
        }
    }
}
