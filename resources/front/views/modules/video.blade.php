@php
    $title = $module['title'] ?? '';
    $subtitle = $module['subtitle'] ?? '';
    $url = trim((string)($module['video_url'] ?? ''));
    $poster = $module['poster'] ?? '';
    $embed = '';
    $native = '';

    if ($url !== '') {
        if (preg_match('/(?:youtube\.com\/watch\?v=|youtube\.com\/embed\/|youtu\.be\/)([\w-]+)/', $url, $m)) {
            $embed = 'https://www.youtube.com/embed/' . $m[1];
        } elseif (preg_match('/(?:vimeo\.com\/|player\.vimeo\.com\/video\/)(\d+)/', $url, $m)) {
            $embed = 'https://player.vimeo.com/video/' . $m[1];
        } elseif (preg_match('#^https?://#', $url)) {
            $embed = $url;
        } else {
            $native = $url;
        }
    }
@endphp
@if ($title || $subtitle || $embed || $native || $poster)
    <section>
        @if ($title)
            <h2 class="text-2xl sm:text-3xl font-bold text-center">{{ $title }}</h2>
        @endif
        @if ($subtitle)
            <p class="mt-2 text-gray-500 dark:text-gray-400 text-center">{{ $subtitle }}</p>
        @endif
        @if ($embed)
            <div class="mt-8 max-w-4xl mx-auto">
                <div class="aspect-video rounded-xl overflow-hidden bg-gray-900">
                    <iframe src="{{ $embed }}" class="w-full h-full" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen loading="lazy" title="{{ $title }}"></iframe>
                </div>
            </div>
        @elseif ($native)
            <div class="mt-8 max-w-4xl mx-auto">
                <video class="w-full h-auto rounded-xl bg-gray-900" controls preload="none" @if ($poster) poster="{{ $poster }}" @endif>
                    <source src="{{ $native }}" type="video/mp4">
                    Tu navegador no soporta vídeo HTML5.
                </video>
            </div>
        @elseif ($poster)
            <div class="mt-8 max-w-4xl mx-auto">
                <div class="rounded-xl overflow-hidden bg-gray-100 dark:bg-gray-800">
                    <img src="{{ $poster }}" loading="lazy" decoding="async" alt="{{ $title }}" class="w-full h-auto">
                </div>
            </div>
        @endif
    </section>
@endif
