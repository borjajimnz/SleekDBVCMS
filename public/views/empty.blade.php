@extends('layout')

@section('title', $frontConfig['site_name'])

@section('content')
    <div class="text-center py-20">
        <h1 class="text-3xl font-bold">{{ $frontConfig['site_name'] }}</h1>
        <p class="mt-3 text-gray-500 dark:text-gray-400">No pages created yet. Create pages in the CMS at <code class="px-1.5 py-0.5 rounded bg-gray-100 dark:bg-gray-800">/cms/?p=pages</code>.</p>
    </div>
@endsection
