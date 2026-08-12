@php
    $title = $module['title'] ?? '';
    $subtitle = $module['subtitle'] ?? '';
    $items = is_string($module['testimonials'] ?? null) ? json_decode($module['testimonials'], true) : ($module['testimonials'] ?? []);
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
                <figure class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 p-5 flex flex-col">
                    <blockquote class="text-sm text-gray-600 dark:text-gray-300 flex-1">“{{ $item['quote'] ?? '' }}”</blockquote>
                    <figcaption class="mt-4 flex items-center gap-3">
                        @if (!empty($item['image']))
                            <img src="{{ $item['image'] }}" loading="lazy" decoding="async" class="w-10 h-10 rounded-full object-cover" alt="">
                        @endif
                        <div>
                            @if (!empty($item['author']))
                                <div class="font-semibold text-sm">{{ $item['author'] }}</div>
                            @endif
                            @if (!empty($item['role']))
                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ $item['role'] }}</div>
                            @endif
                        </div>
                    </figcaption>
                </figure>
            @endforeach
        </div>
    </section>
@endif
