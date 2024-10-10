<?php

namespace App\Livewire\Tags;

use App\Models\Tag;
use App\Models\Team;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Attributes\Modelable;
use Livewire\Component;

class CreateTag extends Component
{
    #[Modelable]
    public array $tag;

    public string $id;
    public Team $team;
    public array $colors;
    public bool $showCreateTag = false;
    public string $color = 'blue';
    public string $tagName = '';
    public string $error = '';

    public function render(): View
    {
        return view('livewire.tags.create-tag');
    }

    public function createTag(): void
    {
        $this->error = '';

        try {
            $this->validate([
                'color' => ['required', 'string', Rule::in(array_keys(Tag::getColors())), Rule::unique('tags', Tag::FIELD_COLOR)->where(fn (Builder $query) =>
                    $query->where(Tag::FIELD_TEAM_ID, $this->team->id)
                        ->where(Tag::FIELD_NAME, $this->tagName)
                    )
                ],
                'tagName' => ['nullable', 'string', 'max:16'],
            ], [
                'color.unique' => 'The combination of name and color already exists.',
            ]);
        } catch (\Exception $e) {
            $this->error = $e->getMessage();

            return;
        }

        $this->tag = $this->team
            ->tags()
            ->create([
                Tag::FIELD_NAME => $this->tagName,
                Tag::FIELD_COLOR => $this->color,
            ])
            ->toArray();

        $this->tagName = '';
        $this->showCreateTag = false;
    }
}
