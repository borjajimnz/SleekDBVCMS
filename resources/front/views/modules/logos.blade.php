@php
    $title = $module['title'] ?? '';
    $items = is_string($module['logos'] ?? null) ? json_decode($module['logos'], true) : ($module['logos'] ?? []);
    if (!is_array($items)) {
        $items = [];
    }
@endphp
@if ($title || $items)
    <section class="text-center">
        @if ($title)
            <h2 class="text-sm uppercase tracking-widest text-gray-400 font-semibold">{{ $title }}</h2>
        @endif
        <div class="mt-6 flex flex-wrap items-center justify-center gap-6">
            @foreach ($items as $item)
                @if (!is_array($item) || empty($item['image'])) @continue @endif
                @php $alt = $item['name'] ?? ''; @endphp
                @if (!empty($item['url']))
                    <a href="{{ $item['url'] }}" target="_blank" rel="noopener" title="{{ $alt }}" class="opacity-60 hover:opacity-100 transition-opacity">
                        <img src="{{ $item['image'] }}" loading="lazy" decoding="async" alt="{{ $alt }}" class="h-8 w-auto">
                    </a>
                @else
                    <img src="{{ $item['image'] }}" loading="lazy" decoding="async" alt="{{ $alt }}" class="h-8 w-auto opacity-60 hover:opacity-100 transition-opacity">
                @endif
            @endforeach
        </div>
    </section>
@endif
