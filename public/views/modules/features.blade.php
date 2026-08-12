@php
    $title = $module['title'] ?? '';
    $subtitle = $module['subtitle'] ?? '';
    $items = is_string($module['features'] ?? null) ? json_decode($module['features'], true) : ($module['features'] ?? []);
    if (!is_array($items)) {
        $items = [];
    }
@endphp
@if ($title || $subtitle || $items)
    <section>
        @if ($title)
            <h2 class="text-2xl sm:text-3xl font-bold text-center">{{ $title }}</h2>
        @endif
        @if ($subtitle)
            <p class="mt-2 text-gray-500 dark:text-gray-400 text-center max-w-2xl mx-auto">{{ $subtitle }}</p>
        @endif
        <div class="mt-8 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach ($items as $item)
                @if (!is_array($item)) @continue @endif
                <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 p-5">
                    @if (!empty($item['icon']))
                        <div class="text-2xl mb-2">{{ $item['icon'] }}</div>
                    @endif
                    @if (!empty($item['title']))
                        <h3 class="font-semibold">{{ $item['title'] }}</h3>
                    @endif
                    @if (!empty($item['text']))
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $item['text'] }}</p>
                    @endif
                </div>
            @endforeach
        </div>
    </section>
@endif
