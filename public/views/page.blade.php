@extends('layout')

@section('title', $page['seo_title'] ?? $page['title'] ?? 'Page')

@if (!empty($page['seo_description']))
    @section('meta_description', $page['seo_description'])
@endif

@section('content')
    @php
        $pageSlug = $page['slug'] ?? '';
        $previewBanner = $preview && empty($page['published']);

        $moduleRefs = is_string($page['modules'] ?? null) ? json_decode($page['modules'], true) : ($page['modules'] ?? []);
        if (!is_array($moduleRefs)) {
            $moduleRefs = [];
        }
        $modules = [];
        foreach ($moduleRefs as $moduleEntry) {
            if (is_array($moduleEntry)) {
                if (empty($moduleEntry['type']) && !empty($moduleEntry['_module_id'])) {
                    $moduleId = (int)$moduleEntry['_module_id'];
                    try {
                        $template = $cms->getDatabase()->findById('modules', $moduleId);
                    } catch (\Throwable $e) {
                        $template = null;
                    }
                    if (is_array($template)) {
                        $moduleEntry['type'] = (string)($template['type'] ?? 'text');
                        foreach (\SleekDBVCMS\Forms\Types\ModulesType::decodeSchema($template['fields'] ?? null, $moduleEntry['type']) as $field) {
                            if (!array_key_exists($field, $moduleEntry)) {
                                $moduleEntry[$field] = '';
                            }
                        }
                    }
                }
                $modules[] = $moduleEntry;
                continue;
            }
            $moduleId = (int)$moduleEntry;
            if ($moduleId <= 0) {
                continue;
            }
            try {
                $module = $cms->getDatabase()->findById('modules', $moduleId);
            } catch (\Throwable $e) {
                $module = null;
            }
            if ($module) {
                $modules[] = $module;
            }
        }

        $ctx = ['cms' => $cms, 'config' => $frontConfig, 'stores' => $stores, 'blade' => $blade, 'page' => $pageSlug];

        // Preload the LCP image (first hero module) so the browser starts
        // fetching it before the render-blocking CSS is done.
        $lcpImage = '';
        foreach ($modules as $m) {
            if (is_array($m) && ($m['type'] ?? '') === 'hero' && !empty($m['image'])) {
                $lcpImage = $m['image'];
                break;
            }
        }
    @endphp

    @if ($lcpImage)
        @push('head_extra')
            <link rel="preload" as="image" href="{{ $lcpImage }}" fetchpriority="high">
        @endpush
    @endif

    <div class="space-y-12">
        @if ($previewBanner)
            <div class="px-4 py-2.5 rounded-lg bg-amber-50 dark:bg-amber-950 border border-amber-200 dark:border-amber-800 text-amber-700 dark:text-amber-300 text-sm font-medium">
                Draft preview — this page is not published yet.
            </div>
        @endif

        @if (empty($modules))
            <div class="text-center py-20">
                <h1 class="text-3xl sm:text-4xl font-bold">{{ $page['title'] ?? '' }}</h1>
                <p class="mt-3 text-gray-500 dark:text-gray-400">This page has no modules yet.</p>
            </div>
        @else
            @foreach ($modules as $module)
                @php $ctx['module_index'] = $loop->index; @endphp
                {!! front_render_module(is_array($module) ? $module : [], $ctx) !!}
            @endforeach
        @endif
    </div>
@endsection
