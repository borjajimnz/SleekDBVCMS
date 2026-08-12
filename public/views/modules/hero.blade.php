@php
    $image = $module['image'] ?? '';
    $title = $module['title'] ?? '';
    $subtitle = $module['subtitle'] ?? '';
    $ctaText = $module['cta_text'] ?? '';
    $ctaUrl = $module['cta_url'] ?? '';
@endphp
<section class="relative rounded-2xl overflow-hidden bg-gray-900 text-white">
    @if ($image)
        <img src="{{ $image }}" class="absolute inset-0 w-full h-full object-cover opacity-40" alt="">
    @endif
    <div class="relative px-6 py-20 text-center">
        @if ($title)
            <h1 class="text-3xl sm:text-5xl font-bold">{{ $title }}</h1>
        @endif
        @if ($subtitle)
            <p class="mt-4 text-gray-200 max-w-xl mx-auto">{{ $subtitle }}</p>
        @endif
        @if ($ctaText && $ctaUrl)
            <a href="{{ $ctaUrl }}" class="mt-6 inline-block px-6 py-2.5 rounded-lg bg-blue-600 hover:bg-blue-700 text-white font-medium">{{ $ctaText }}</a>
        @endif
    </div>
</section>
