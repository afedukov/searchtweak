<?php

namespace App\Actions\Models;

use App\Models\SearchModel;
use Illuminate\Support\Facades\Gate;

class DeleteSearchModel
{
    public function delete(SearchModel $model): void
    {
        Gate::authorize('delete', $model);

        $model->delete();
    }
}
