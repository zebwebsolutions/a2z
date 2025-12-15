@props(['items' => []])

<nav class="text-sm text-gray-600 mb-4" aria-label="Breadcrumb">
    <ol class="flex items-center flex-wrap gap-1">
        @foreach($items as $item)
            @if(!$loop->last)
                <li>
                    <a href="{{ $item['url'] }}" class="text-blue-600 hover:underline">
                        {{ $item['label'] }}
                    </a>
                </li>
                <li>/</li>
            @else
                <li class="text-gray-800 font-semibold">
                    <a href="{{ $item['url'] }}" class="text-blue-600 hover:underline">
                        {{ $item['label'] }}
                    </a>
                </li>
            @endif
        @endforeach
    </ol>
</nav>
