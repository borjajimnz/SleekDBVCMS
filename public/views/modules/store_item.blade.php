@php
    $store = $module['store'] ?? null;
    $id = (int)($module['item_id'] ?? $module['id'] ?? 0);
    $title = $module['title'] ?? '';
    $stores = $ctx['stores'];
    $cms = $ctx['cms'];
    $frontConfig = $ctx['config'];

    $row = null;
    $fields = [];
    if ($store && isset($stores[$store]) && $id !== 0) {
        // Respect the blog_enabled site setting for store modules.
        if (empty($frontConfig['blog_enabled']) && in_array($store, ['posts', 'categories'], true)) {
            $store = null;
        }
    }
    if ($store && isset($stores[$store]) && $id !== 0) {
        $row = $cms->getDatabase()->findById($store, $id);
        $fields = $stores[$store];
    }
    $img = $row ? front_image_of($frontConfig, $row, $fields) : '';
@endphp
@if ($row)
    <section>
        @if ($title)
            <h2 class="text-xl font-semibold mb-4">{{ $title }}</h2>
        @endif
        <a href="{{ front_item_url($store, $id) }}" class="block bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 overflow-hidden shadow-sm hover:shadow-md transition-shadow sm:flex">
            @if ($img)
                <div class="sm:w-1/3 bg-gray-100 dark:bg-gray-800"><img src="{{ $img }}" fetchpriority="high" decoding="async" class="w-full h-56 sm:h-full object-cover" alt=""></div>
            @endif
            <div class="p-5 flex-1">
                <h3 class="font-semibold mb-1">{{ $row['title'] ?? $row['name'] ?? '#' . $id }}</h3>
                @foreach ($fields as $fname => $ftype)
                    @if (in_array($ftype, $frontConfig['html_fields'], true) && !empty($row[$fname]))
                        <p class="mt-2 prose-html">{!! front_excerpt($row[$fname], 160) !!}</p>
                        @break
                    @endif
                @endforeach
            </div>
        </a>
    </section>
@endif
