@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    
    {{-- BREADCRUMB --}}
    <x-breadcrumb :items="$breadcrumbItems" />

    {{-- PAGE TITLE --}}
    <h1 class="text-2xl font-bold mb-4">{{ $category->name }}</h1>

    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        @foreach($subcategories as $sub)
            <a href="{{ route('category.show', $sub->slug) }}" 
            class="border rounded p-4 hover:shadow">
                {{ $sub->name }}
            </a>
        @endforeach
    </div>
</div>
@endsection