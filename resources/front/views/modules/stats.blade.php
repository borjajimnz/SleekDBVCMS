@php
    $title = $module['title'] ?? '';
    $items = is_string($module['stats'] ?? null) ? json_decode($module['stats'], true) : ($module['stats'] ?? []);
    if (!is_array($items)) {
        $items = [];
    }
@endphp
@if ($items)
    <section class="rounded-2xl bg-gray-900 text-white px-6 py-10">
        @if ($title)
            <h2 class="text-2xl sm:text-3xl font-bold text-center mb-8">{{ $title }}</h2>
        @endif
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-6 text-center">
            @foreach ($items as $item)
                @if (!is_array($item)) @continue @endif
                <div>
                    @if (!empty($item['value']))
                        <div class="text-3xl sm:text-4xl font-bold">{{ $item['value'] }}</div>
                    @endif
                    @if (!empty($item['label']))
                        <div class="mt-1 text-sm text-gray-400">{{ $item['label'] }}</div>
                    @endif
                </div>
            @endforeach
        </div>
    </section>
@endif
