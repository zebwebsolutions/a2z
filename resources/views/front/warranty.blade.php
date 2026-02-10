@extends('layouts.app')

@section('content')

<div class="bg-gray-100 py-10">

    {{-- Breadcrumb --}}
    <div class="container mx-auto px-6 mb-6">
        <nav class="text-sm text-gray-600 flex items-center gap-2">
            <a href="{{ url('/') }}" class="hover:text-blue-600">Home</a>
            <span>/</span>
            <span class="text-gray-800 font-semibold">Warranty Policy</span>
        </nav>
    </div>

    <div class="container mx-auto px-6">

        {{-- Page Header --}}
        <div class="bg-white p-8 rounded-xl shadow-sm mb-8 border">
            <div class="flex items-center gap-3 mb-4">
                <div class="text-blue-600 text-4xl">🛡️</div>
                <h1 class="text-4xl font-extrabold tracking-tight">Warranty Policy</h1>
            </div>

            <p class="text-gray-600 text-lg leading-relaxed">
                At A2Z, your satisfaction and product reliability are our top priorities.
                Please review our full warranty policy below.
            </p>
        </div>

        {{-- Policy Content --}}
        <div class="bg-white p-10 rounded-xl shadow-sm border p-3">

            <article class="prose max-w-none 
                prose-headings:font-extrabold 
                prose-headings:text-gray-900 
                prose-h1:text-3xl prose-h1:mb-4
                prose-h2:text-2xl prose-h2:mt-8 prose-h2:mb-3
                prose-h3:text-xl prose-h3:mt-6 prose-h3:mb-2
                prose-p:text-gray-700 prose-p:leading-relaxed">

                {!! $policy !!}
            </article>

        </div>

    </div>
</div>

@endsection
