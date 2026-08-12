@php
    $currentPage = $pageSlug ?? ($storeName !== null ? 'store-' . $storeName : null);
@endphp
<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', $frontConfig['site_name']) — {{ $frontConfig['site_name'] }}</title>
    @hasSection('meta_description')
        <meta name="description" content="@yield('meta_description')">
    @endif
    <link href="{{ cms_css_url() }}" rel="stylesheet">
    @stack('head_extra')
    <script>
        (function () {
            var stored = localStorage.getItem('front-theme');
            var pref = @json($frontConfig['theme']);
            if (stored === 'dark' || (pref === 'dark' && !stored) || (!stored && pref === 'auto' && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.classList.add('dark');
            }
        })();
    </script>
    <style>
        .prose-html { line-height: 1.75; color: inherit; }
        .prose-html > * + * { margin-top: 1em; }
        .prose-html h1, .prose-html h2, .prose-html h3, .prose-html h4, .prose-html h5, .prose-html h6 { font-weight: 700; line-height: 1.3; margin-top: 1.5em; margin-bottom: 0.5em; }
        .prose-html h1 { font-size: 1.75rem; }
        .prose-html h2 { font-size: 1.5rem; }
        .prose-html h3 { font-size: 1.25rem; }
        .prose-html p { margin: 0 0 1em; }
        .prose-html ul { list-style: disc; padding-left: 1.5em; }
        .prose-html ol { list-style: decimal; padding-left: 1.5em; }
        .prose-html li { margin-bottom: 0.25em; }
        .prose-html blockquote { border-left: 4px solid #d1d5db; padding-left: 1em; margin: 1em 0; font-style: italic; color: #6b7280; }
        .dark .prose-html blockquote { border-color: #374151; color: #9ca3af; }
        .prose-html pre { background: #1f2937; color: #e5e7eb; padding: 1em; border-radius: 0.5rem; overflow-x: auto; }
        .prose-html code { background: #f3f4f6; padding: 0.15em 0.4em; border-radius: 0.25em; font-size: 0.875em; }
        .dark .prose-html code { background: #1f2937; }
        .prose-html pre code { background: none; padding: 0; }
        .prose-html img { max-width: 100%; height: auto; border-radius: 0.5rem; }
        .prose-html a { color: #2563eb; text-decoration: underline; }
        .dark .prose-html a { color: #60a5fa; }
        .prose-html table { border-collapse: collapse; width: 100%; }
        .prose-html th, .prose-html td { border: 1px solid #d1d5db; padding: 0.5em 0.75em; }
        .dark .prose-html th, .dark .prose-html td { border-color: #374151; }
    </style>
</head>
<body class="h-full bg-gray-50 dark:bg-gray-950 text-gray-900 dark:text-gray-100 antialiased flex flex-col">
    <!-- Header -->
    <header class="bg-white dark:bg-gray-900 border-b border-gray-200 dark:border-gray-800 sticky top-0 z-30">
        <div class="max-w-5xl mx-auto px-4 sm:px-6">
            <div class="h-16 flex items-center gap-4">
                <a href="/" class="shrink-0">
                    <span class="text-lg font-bold">{{ $frontConfig['site_name'] }}</span>
                </a>
                @if (!empty($frontConfig['tagline']))
                    <span class="hidden md:inline text-sm text-gray-500 dark:text-gray-400 truncate">{{ $frontConfig['tagline'] }}</span>
                @endif
                <nav class="ml-auto flex items-center gap-1 overflow-x-auto">
                    @foreach ($navPages as $nav)
                        @php $isActive = $currentPage === ($nav['slug'] ?? ''); @endphp
                        <a href="/{{ urlencode($nav['slug'] ?? '') }}"
                           class="px-3 py-1.5 rounded-lg text-sm whitespace-nowrap {{ $isActive ? 'bg-blue-600 text-white' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800' }}">{{ $nav['title'] ?? 'Page' }}</a>
                    @endforeach
                    <button onclick="toggleTheme()" title="Toggle theme"
                            class="p-1.5 rounded-lg text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800">
                        <svg class="w-5 h-5 dark:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
                        <svg class="w-5 h-5 hidden dark:block" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                    </button>
                </nav>
            </div>
        </div>
    </header>

    <!-- Custom header block -->
    @if (!empty($frontConfig['header_html']))
        <div class="max-w-5xl mx-auto px-4 sm:px-6 pt-6">{!! $frontConfig['header_html'] !!}</div>
    @endif

    <!-- Main -->
    <main class="flex-1 w-full max-w-5xl mx-auto px-4 sm:px-6 py-8">
        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="bg-white dark:bg-gray-900 border-t border-gray-200 dark:border-gray-800 mt-8">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 py-6 flex flex-col sm:flex-row items-center gap-2 justify-between">
            <span class="text-sm text-gray-500 dark:text-gray-400">{{ $frontConfig['site_name'] }}</span>
            <span class="text-sm text-gray-500 dark:text-gray-400">{!! $frontConfig['footer_html'] !!}</span>
        </div>
    </footer>

    <script>
        function toggleTheme() {
            var dark = document.documentElement.classList.toggle('dark');
            localStorage.setItem('front-theme', dark ? 'dark' : 'light');
        }
    </script>
</body>
</html>
