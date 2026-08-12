@extends('layout')

@section('title', front_label($frontConfig, $storeName) . ' #' . (int)$row['_id'])

@section('content')
    @php $img = front_image_of($frontConfig, $row, $fields); @endphp
    <div class="space-y-6">
        <nav class="text-sm text-gray-500 dark:text-gray-400">
            <a href="/" class="hover:underline">Home</a>
            <span class="mx-1">/</span>
            <a href="{{ front_store_url($storeName) }}" class="hover:underline">{{ front_label($frontConfig, $storeName) }}</a>
            <span class="mx-1">/</span>
            <span>{{ $row['title'] ?? $row['name'] ?? '#' . (int)$row['_id'] }}</span>
        </nav>

        <article class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 shadow-sm overflow-hidden">
            @if ($img)
                <div class="aspect-video md:aspect-[21/9] bg-gray-100 dark:bg-gray-800 overflow-hidden">
                    <img src="{{ $img }}" fetchpriority="high" decoding="async" class="w-full h-full object-cover" alt="">
                </div>
            @endif

            <div class="p-5 sm:p-8">
                @php $titleShown = false; @endphp
                @foreach ($fields as $fname => $ftype)
                    @if (is_array($ftype))
                        @if (!empty($row['_join_' . $fname]))
                            <span class="inline-block mr-2 mb-2 px-3 py-1 rounded-full bg-blue-50 dark:bg-blue-950 text-blue-700 dark:text-blue-300 text-xs font-medium">{{ $row['_join_' . $fname] }}</span>
                        @endif
                        @continue
                    @endif
                    @if (in_array($ftype, $frontConfig['image_fields'], true))
                        @continue
                    @endif
                    @php $value = $row[$fname] ?? ''; @endphp
                    @if ($value === '' || $value === null)
                        @continue
                    @endif
                    @if ($fname === 'title' || $fname === 'name')
                        @if (!$titleShown)
                            <h1 class="text-2xl sm:text-3xl font-bold">{{ $value }}</h1>
                            @php $titleShown = true; @endphp
                        @endif
                    @elseif (in_array($ftype, $frontConfig['html_fields'], true))
                        <div class="prose-html leading-relaxed">{!! front_richtext($value) !!}</div>
                    @elseif (in_array($ftype, ['textarea', 'text'], true))
                        <p class="leading-relaxed whitespace-pre-line">{{ $value }}</p>
                    @elseif ($fname === 'url')
                        <p><a href="{{ $value }}" target="_blank" rel="noopener" class="text-blue-600 dark:text-blue-400 hover:underline">{{ $value }}</a></p>
                    @elseif (in_array($fname, ['created', 'date', 'published'], true))
                        <time class="block text-sm text-gray-400 dark:text-gray-500">{{ $value }}</time>
                    @else
                        <p><strong class="capitalize text-sm">{{ $fname }}:</strong> {{ $value }}</p>
                    @endif
                @endforeach
                @if (!$titleShown)
                    <h1 class="text-2xl font-bold">Record #{{ (int)$row['_id'] }}</h1>
                @endif
            </div>
        </article>

        <div>
            <a href="{{ front_store_url($storeName) }}" class="inline-flex items-center gap-1.5 text-sm text-blue-600 dark:text-blue-400 hover:underline">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Back to {{ front_label($frontConfig, $storeName) }}
            </a>
        </div>
    </div>
@endsection
