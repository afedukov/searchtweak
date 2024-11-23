<div
		x-data="{
			tags: $wire.entangle('tags'),
			teamTags: $wire.entangle('teamTags'),
			tag: $wire.entangle('tag'),
			showDeleteTag: $wire.entangle('showDeleteTag'),
			tagToDelete: $wire.entangle('tagToDelete'),
			availableTags: [],
		}"
		x-init="
			availableTags = teamTags.filter(t => !tags.find(tag => tag.id === t.id));
			$watch('tag', value => { teamTags.push(value); });
			$watch('teamTags', value => { availableTags = teamTags.filter(t => !tags.find(tag => tag.id === t.id)); });
		"
>
	<x-tags.tags-edit :id="$id" :team="$team" :tooltip="$tooltip" />
</div>
