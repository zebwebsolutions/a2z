@extends('admin.layouts.app')

@section('content')
<div class="container mx-auto py-8">
    <h1 class="text-2xl font-bold mb-4">Edit User</h1>

    <form method="POST" action="{{ route('admin.users.update', $user) }}">
        @csrf @method('PUT')
        @include('admin.users._form')
        <button class="mt-4 bg-blue-600 text-white px-4 py-2 rounded">
            Update
        </button>
    </form>
</div>
@endsection