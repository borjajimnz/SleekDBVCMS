@php
    $store = $module['store'] ?? null;
    $limit = (int)($module['limit'] ?? 4);
    $title = $module['title'] ?? '';
    $stores = $ctx['stores'];
    $frontConfig = $ctx['config'];
    $cms = $ctx['cms'];

    $rows = [];
    $fields = [];
    if ($store && isset($stores[$store])) {
        // Respect the blog_enabled site setting for store modules.
        if (empty($frontConfig['blog_enabled']) && in_array($store, ['posts', 'categories'], true)) {
            $store = null;
        }
    }
    if ($store && isset($stores[$store])) {
        $rows = $cms->getDatabase()->findAll($store, ['_id' => 'desc']);
        $rows = array_slice(front_resolve_joins($cms, $stores, $store, $rows), 0, $limit);
        $fields = $stores[$store];
    }
@endphp
@if ($store && isset($stores[$store]))
    <section>
        @if ($title)
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-xl font-semibold">{{ $title }}</h2>
                <a href="{{ front_store_url($store) }}" class="text-sm text-blue-600 dark:text-blue-400 hover:underline">View all →</a>
            </div>
        @endif

        @if (empty($rows))
            <p class="text-gray-500 dark:text-gray-400 text-sm">No items yet.</p>
        @else
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                @foreach ($rows as $row)
                    @php $img = front_image_of($frontConfig, $row, $fields); @endphp
                    <a href="{{ front_item_url($store, $row['_id']) }}" class="group bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 overflow-hidden shadow-sm hover:shadow-md transition-shadow">
                        @if ($img)
                            <div class="aspect-video bg-gray-100 dark:bg-gray-800 overflow-hidden">
                                @if ($loop->first)
                                    <img src="{{ $img }}" fetchpriority="high" decoding="async" class="w-full h-full object-cover group-hover:scale-105 transition-transform" alt="">
                                @else
                                    <img src="{{ $img }}" loading="lazy" decoding="async" class="w-full h-full object-cover group-hover:scale-105 transition-transform" alt="">
                                @endif
                            </div>
                        @endif
                        <div class="p-4">
                            @php $titleShown = false; @endphp
                            @foreach ($fields as $fname => $ftype)
                                @if (is_array($ftype) || in_array($ftype, $frontConfig['image_fields'], true) || in_array($ftype, $frontConfig['html_fields'], true))
                                    @continue
                                @endif
                                @if (empty($row[$fname]))
                                    @continue
                                @endif
                                @if (!$titleShown && ($fname === 'title' || $fname === 'name'))
                                    <h3 class="font-semibold mb-1 truncate">{{ $row[$fname] }}</h3>
                                    @php $titleShown = true; @endphp
                                @elseif ($ftype === 'textarea' || $ftype === 'text')
                                    <p class="text-sm text-gray-500 dark:text-gray-400 line-clamp-2">{{ front_excerpt($row[$fname], 80) }}</p>
                                    @break
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
    </section>
@endif
