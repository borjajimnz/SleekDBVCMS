{{--
    Recursive navigation tree from the "menus" store.
    $items: array of {label, url, children[]}
    $variant: 'desktop' (hover dropdown) | 'mobile' (indented list)
    $currentUrl: current path used for the active state.
--}}
@php
    $variant = $variant ?? 'desktop';
    $currentUrl = (string)($currentUrl ?? '/');
@endphp
@foreach ($items as $item)
    @php
        $url = trim((string)($item['url'] ?? ''));
        $href = $url !== '' ? $url : '#';
        $label = trim((string)($item['label'] ?? ''));
        $children = is_array($item['children'] ?? null) ? $item['children'] : [];
        $hasChildren = count($children) > 0;
        $isActive = $currentUrl === $href
            || ($href !== '/' && $href !== '#' && str_starts_with($currentUrl, $href . '/'));
        $linkCls = $isActive
            ? 'bg-blue-600 text-white'
            : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800';
    @endphp
    @if ($variant === 'desktop')
        <li class="relative group">
            @if ($hasChildren)
                <span class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-sm whitespace-nowrap cursor-pointer {{ $linkCls }}">
                    {{ $label }}
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </span>
                <ul class="absolute left-0 top-full pt-2 invisible opacity-0 group-hover:visible group-hover:opacity-100 transition-opacity">
                    <li class="bg-white dark:bg-gray-900 rounded-xl shadow-lg border border-gray-200 dark:border-gray-800 p-2 min-w-44">
                        <ul class="space-y-0.5">
                            @include('partials.menu-tree', ['items' => $children, 'variant' => 'desktop', 'currentUrl' => $currentUrl])
                        </ul>
                    </li>
                </ul>
            @else
                <a href="{{ $href }}" class="block px-3 py-1.5 rounded-lg text-sm whitespace-nowrap {{ $linkCls }}">{{ $label }}</a>
            @endif
        </li>
    @else
        <li>
            @if ($hasChildren)
                <span class="block px-3 py-2.5 rounded-lg text-sm font-medium text-gray-900 dark:text-gray-100">{{ $label }}</span>
                <ul class="ml-4 space-y-1 border-l border-gray-200 dark:border-gray-800">
                    @include('partials.menu-tree', ['items' => $children, 'variant' => 'mobile', 'currentUrl' => $currentUrl])
                </ul>
            @else
                <a href="{{ $href }}" class="block px-3 py-2.5 rounded-lg text-sm {{ $isActive ? 'bg-blue-600 text-white' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800' }}">{{ $label }}</a>
            @endif
        </li>
    @endif
@endforeach
