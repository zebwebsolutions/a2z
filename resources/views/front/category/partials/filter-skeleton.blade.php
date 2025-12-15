<div class="space-y-6 p-4 bg-white border rounded-lg shadow-sm animate-pulse">

    {{-- Brand Skeleton --}}
    <div class="space-y-3">
        <div class="h-5 w-24 bg-gray-200 rounded"></div>

        @for ($i = 0; $i < 5; $i++)
            <div class="h-4 w-32 bg-gray-200 rounded"></div>
        @endfor
    </div>

    {{-- RAM Skeleton --}}
    <div class="space-y-3">
        <div class="h-5 w-20 bg-gray-200 rounded"></div>

        @for ($i = 0; $i < 4; $i++)
            <div class="h-4 w-28 bg-gray-200 rounded"></div>
        @endfor
    </div>

    {{-- Storage Skeleton --}}
    <div class="space-y-3">
        <div class="h-5 w-24 bg-gray-200 rounded"></div>

        @for ($i = 0; $i < 4; $i++)
            <div class="h-4 w-28 bg-gray-200 rounded"></div>
        @endfor
    </div>

    {{-- Price Skeleton --}}
    <div class="space-y-3">
        <div class="h-5 w-16 bg-gray-200 rounded"></div>
        <div class="h-3 w-full bg-gray-200 rounded"></div>
        <div class="flex justify-between">
            <div class="h-4 w-10 bg-gray-200 rounded"></div>
            <div class="h-4 w-10 bg-gray-200 rounded"></div>
        </div>
    </div>

</div>