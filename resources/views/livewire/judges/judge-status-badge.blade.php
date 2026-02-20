<span>
    @if ($status !== null)
        <span wire:poll.15s class="inline-flex items-center gap-1.5">
            <span
                @class([
                    'inline-flex items-center justify-center gap-1 font-medium px-2 py-0.5 rounded-full text-xs min-w-[68px]',
                    'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300' => $status === 'working',
                    'bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-300' => $status === 'waiting',
                ])
            >
                @if ($status === 'working')
                    <span class="w-1.5 h-1.5 rounded-full bg-green-500 animate-pulse"></span>
                @endif
                {{ $label }}
            </span>

            <span class="inline-flex min-w-[46px]">
                @if ($hasError)
                    <a
                        href="{{ $judgeLogsUrl }}"
                        class="inline-flex items-center justify-center font-medium px-2 py-0.5 rounded-full text-xs bg-red-100 text-red-800 hover:bg-red-200 dark:bg-red-900 dark:text-red-300 dark:hover:bg-red-800"
                    >
                        Error
                    </a>
                @endif
            </span>
        </span>
    @endif
</span>
