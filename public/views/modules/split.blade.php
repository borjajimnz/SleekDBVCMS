@php
    $title = $module['title'] ?? '';
    $text = $module['text'] ?? '';
    $image = $module['image'] ?? '';
    $position = ($module['image_position'] ?? 'left') === 'right' ? 'right' : 'left';
    $ctaText = $module['cta_text'] ?? '';
    $ctaUrl = $module['cta_url'] ?? '';
@endphp
@if ($title || $text || $image)
    <section class="grid grid-cols-1 lg:grid-cols-2 gap-8 items-center">
        @if ($image && $position === 'left')
            <div class="rounded-xl overflow-hidden bg-gray-100 dark:bg-gray-800">
                <img src="{{ $image }}" loading="lazy" decoding="async" class="w-full h-full object-cover" alt="">
            </div>
        @endif
        <div>
            @if ($title)
                <h2 class="text-2xl sm:text-3xl font-bold">{{ $title }}</h2>
            @endif
            @if ($text)
                <div class="mt-4 prose-html">{!! front_richtext($text) !!}</div>
            @endif
            @if ($ctaText && $ctaUrl)
                <a href="{{ $ctaUrl }}" class="mt-6 inline-block px-5 py-2.5 rounded-lg bg-blue-600 hover:bg-blue-700 text-white font-medium">{{ $ctaText }}</a>
            @endif
        </div>
        @if ($image && $position === 'right')
            <div class="rounded-xl overflow-hidden bg-gray-100 dark:bg-gray-800">
                <img src="{{ $image }}" loading="lazy" decoding="async" class="w-full h-full object-cover" alt="">
            </div>
        @endif
    </section>
@endif
