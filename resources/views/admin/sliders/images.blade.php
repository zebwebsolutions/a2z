@extends('admin.layouts.app')

@section('title', 'Slider Images')

@section('content')

<h1 class="text-2xl font-bold mb-4">Slider: {{ $slider->title ?? $slider->name }}</h1>

{{-- UPLOAD FORM --}}
<form action="{{ route('admin.sliders.images.store', $slider) }}" method="POST" enctype="multipart/form-data"
      class="bg-white p-4 mb-6 shadow rounded">
    @csrf

    <div class="grid grid-cols-2 gap-4">

        <div>
            <label>Image</label>
            <input type="file" name="image" class="w-full border p-2">
        </div>

        <div>
            <label>Slider Link</label>
            <input type="text" name="url" class="w-full border p-2" placeholder="https://example.com">
        </div>
    </div>

    <button class="mt-3 px-4 py-2 bg-blue-600 text-white rounded">Add Slide</button>
</form>

{{-- DRAG SORTABLE LIST --}}
<h2 class="text-xl font-bold mb-4">Slides</h2>

<ul id="sortable" class="space-y-3">
    @foreach($images as $image)
        <li data-id="{{ $image->id }}"
            class="bg-white p-3 shadow flex items-center gap-4 rounded cursor-move">

            <img src="{{ asset('storage/' . $image->image) }}" class="w-32 h-20 object-cover rounded">

            <a href="{{ route('admin.sliders.images.edit', $image) }}" class="text-blue-600">Edit</a>
            <form action="{{ route('admin.sliders.images.destroy', $image) }}" method="POST">
                @csrf @method('DELETE')
                <button class="text-red-600">Delete</button>
            </form>
        </li>
    @endforeach
</ul>

@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
    new Sortable(document.getElementById('sortable'), {
        animation: 150,
        onEnd: function () {
            let order = [];
            document.querySelectorAll('#sortable li').forEach((el) => {
                order.push(el.getAttribute('data-id'));
            });

            fetch('{{ route('admin.sliders.images.sort') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({order})
            });
        }
    });
</script>
@endsection
