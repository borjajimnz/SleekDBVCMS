@php
    $title = $module['title'] ?? '';
    $subtitle = $module['subtitle'] ?? '';
    $image = $module['image'] ?? '';
    $ctaText = $module['cta_text'] ?? '';
    $ctaUrl = $module['cta_url'] ?? '';
@endphp
@if ($title || $subtitle || $ctaText)
    <section class="relative rounded-2xl overflow-hidden bg-gray-900 text-white">
        @if ($image)
            <img src="{{ $image }}" loading="lazy" decoding="async" alt="" class="absolute inset-0 w-full h-full object-cover opacity-30">
        @endif
        <div class="relative px-6 py-16 text-center">
            @if ($title)
                <h2 class="text-2xl sm:text-4xl font-bold">{{ $title }}</h2>
            @endif
            @if ($subtitle)
                <p class="mt-3 text-gray-300 max-w-2xl mx-auto">{{ $subtitle }}</p>
            @endif
            @if ($ctaText && $ctaUrl)
                <a href="{{ $ctaUrl }}" class="mt-6 inline-block px-6 py-2.5 rounded-lg bg-blue-600 hover:bg-blue-700 text-white font-medium">{{ $ctaText }}</a>
            @endif
        </div>
    </section>
@endif
