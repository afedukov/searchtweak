<?php

namespace App\Livewire\Traits\Users;

use App\Actions\Users\DeleteTag;
use Livewire\Attributes\Modelable;

trait ManageTagsTrait
{
    #[Modelable]
    public ?array $tags = [];

    public array $teamTags = [];
    public array $tag = [];
    public bool $showDeleteTag = false;
    public ?int $tagToDelete = null;
    public string $error = '';

    protected function initializeManageTags(): void
    {
        $this->team->load('tags');
        $this->teamTags = $this->team->tags->toArray();
    }

    public function deleteTag(DeleteTag $action): void
    {
        $this->error = '';

        if ($this->tagToDelete === null) {
            return;
        }

        try {
            $action->delete($this->team, $this->tagToDelete);
        } catch (\Exception $e) {
            $this->error = $e->getMessage();

            return;
        } finally {
            $this->showDeleteTag = false;
            $this->tagToDelete = null;
        }

        $this->initializeManageTags();
    }
}
