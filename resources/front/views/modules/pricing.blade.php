@php
    $title = $module['title'] ?? '';
    $subtitle = $module['subtitle'] ?? '';
    $items = is_string($module['pricing'] ?? null) ? json_decode($module['pricing'], true) : ($module['pricing'] ?? []);
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
        <div class="mt-8 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 items-start">
            @foreach ($items as $item)
                @if (!is_array($item)) @continue @endif
                @php
                    $highlight = !empty($item['highlight']);
                    $features = is_string($item['features'] ?? null) ? preg_split('/\r?\n/', trim($item['features'])) : [];
                @endphp
                <div class="rounded-xl border p-6 {{ $highlight ? 'border-blue-600 ring-2 ring-blue-600/20 bg-white dark:bg-gray-900 shadow-lg' : 'border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 shadow-sm' }}">
                    @if (!empty($item['name']))
                        <h3 class="font-semibold">{{ $item['name'] }}</h3>
                    @endif
                    <div class="mt-3 flex items-baseline gap-1">
                        @if (!empty($item['price']))
                            <span class="text-3xl font-bold">{{ $item['price'] }}</span>
                        @endif
                        @if (!empty($item['period']))
                            <span class="text-sm text-gray-500 dark:text-gray-400">{{ $item['period'] }}</span>
                        @endif
                    </div>
                    @if (!empty($features))
                        <ul class="mt-4 space-y-2 text-sm text-gray-600 dark:text-gray-300">
                            @foreach ($features as $feature)
                                @if (trim($feature) === '') @continue @endif
                                <li class="flex items-start gap-2">
                                    <svg class="w-4 h-4 mt-0.5 shrink-0 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    <span>{{ $feature }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                    @if (!empty($item['cta_text']) && !empty($item['cta_url']))
                        <a href="{{ $item['cta_url'] }}" class="mt-6 block text-center px-4 py-2 rounded-lg font-medium {{ $highlight ? 'bg-blue-600 hover:bg-blue-700 text-white' : 'bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700' }}">{{ $item['cta_text'] }}</a>
                    @endif
                </div>
            @endforeach
        </div>
    </section>
@endif
