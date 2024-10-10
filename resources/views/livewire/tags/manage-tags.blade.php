<div
		x-data="{
			tags: @entangle('tags'),
			teamTags: @entangle('teamTags'),
			tag: @entangle('tag'),
			showDeleteTag: @entangle('showDeleteTag'),
			tagToDelete: @entangle('tagToDelete'),
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
