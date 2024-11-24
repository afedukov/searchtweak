@props(['roles', 'var'])

<div class="flex flex-wrap gap-3 mt-1" x-data="{ currentRole: $wire.entangle('{{ $var }}') }">
	@foreach ($roles as $index => $role)
		<button
				type="button"
				class="relative px-5 py-4 inline-flex w-full text-gray-500 dark:text-gray-400 border rounded-lg cursor-pointer bg-white dark:bg-gray-800"
				:class="currentRole === '{{ $role->key }}' ? 'border-blue-600 dark:border-blue-500' : 'border-gray-200 dark:border-gray-700'"
				@click="currentRole = '{{ $role->key }}'"
		>
			<div>
				<!-- Role Name -->
				<div class="flex items-center">
					<div class="text-sm font-semibold" :class="{ 'text-blue-600 dark:text-blue-500': currentRole === '{{ $role->key }}' }">
						{{ $role->name }}
					</div>

					<template x-if="currentRole === '{{ $role->key }}'">
						<svg class="ms-2 h-5 w-5 text-green-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
							<path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
						</svg>
					</template>
				</div>

				<!-- Role Description -->
				<div class="mt-1 text-xs">
					{{ $role->description }}
				</div>
			</div>
		</button>
	@endforeach
</div>
