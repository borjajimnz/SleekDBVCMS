@php
    $title = $module['title'] ?? '';
    $subtitle = $module['subtitle'] ?? '';
    $items = is_string($module['faq'] ?? null) ? json_decode($module['faq'], true) : ($module['faq'] ?? []);
    if (!is_array($items)) {
        $items = [];
    }
@endphp
@if ($title || $subtitle || $items)
    <section class="max-w-3xl mx-auto">
        @if ($title)
            <h2 class="text-2xl sm:text-3xl font-bold text-center">{{ $title }}</h2>
        @endif
        @if ($subtitle)
            <p class="mt-2 text-gray-500 dark:text-gray-400 text-center">{{ $subtitle }}</p>
        @endif
        <div class="mt-8 space-y-3">
            @foreach ($items as $item)
                @if (!is_array($item)) @continue @endif
                <details class="group bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 overflow-hidden">
                    <summary class="flex items-center justify-between gap-4 px-5 py-4 font-medium cursor-pointer list-none">
                        <span>{{ $item['question'] ?? '' }}</span>
                        <svg class="w-5 h-5 shrink-0 text-gray-400 transition-transform group-open:rotate-45" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    </summary>
                    <div class="px-5 pb-5 text-sm text-gray-500 dark:text-gray-400 prose-html">{!! front_richtext($item['answer'] ?? '') !!}</div>
                </details>
            @endforeach
        </div>
    </section>
@endif
