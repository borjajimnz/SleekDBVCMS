@extends('layout')

@section('title', front_label($frontConfig, $storeName))

@section('content')
    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl sm:text-3xl font-bold">{{ front_label($frontConfig, $storeName) }}</h1>
            <span class="text-sm text-gray-500 dark:text-gray-400">{{ count($rows) }} items</span>
        </div>

        @if (empty($rows))
            <p class="text-gray-500 dark:text-gray-400">No items in {{ $storeName }} yet.</p>
        @else
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
                @foreach ($rows as $row)
                    @php $img = front_image_of($frontConfig, $row, $fields); @endphp
                    <a href="{{ front_item_url($storeName, $row['_id']) }}"
                       class="group bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 overflow-hidden shadow-sm hover:shadow-md transition-shadow flex flex-col">
                        @if ($img)
                            <div class="aspect-video bg-gray-100 dark:bg-gray-800 overflow-hidden">
                                <img src="{{ $img }}" loading="lazy" class="w-full h-full object-cover group-hover:scale-105 transition-transform" alt="">
                            </div>
                        @endif
                        <div class="p-4 space-y-2 flex-1">
                            @php $titleShown = false; @endphp
                            @foreach ($fields as $fname => $ftype)
                                @if (is_array($ftype))
                                    @if (!empty($row['_join_' . $fname]))
                                        <div class="text-xs font-medium text-blue-600 dark:text-blue-400 uppercase tracking-wide">{{ $row['_join_' . $fname] }}</div>
                                    @endif
                                    @continue
                                @endif
                                @if (in_array($ftype, $frontConfig['image_fields'], true) || in_array($ftype, $frontConfig['html_fields'], true))
                                    @continue
                                @endif
                                @if (empty($row[$fname]))
                                    @continue
                                @endif
                                @if (!$titleShown && ($fname === 'title' || $fname === 'name'))
                                    <h3 class="font-semibold truncate">{{ $row[$fname] }}</h3>
                                    @php $titleShown = true; @endphp
                                @elseif ($ftype === 'textarea' || $ftype === 'text')
                                    <p class="text-sm text-gray-500 dark:text-gray-400 line-clamp-3">{{ front_excerpt($row[$fname], 140) }}</p>
                                    @break
                                @elseif ($fname === 'created' || $fname === 'date' || $fname === 'published')
                                    <time class="block text-xs text-gray-400 dark:text-gray-500">{{ $row[$fname] }}</time>
                                @endif
                            @endforeach
                            @if (!$titleShown)
                                <h3 class="font-semibold truncate">#{{ (int)$row['_id'] }}</h3>
                            @endif
                        </div>
                    </a>
                @endforeach
            </div>
        @endif
    </div>
@endsection
