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
    @endphp

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
