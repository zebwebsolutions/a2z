@extends('admin.layouts.app')

@section('content')
<div class="container mx-auto py-8">

    <div class="flex justify-between items-center mb-4">
        <h1 class="text-2xl font-bold">Users</h1>
        <a href="{{ route('admin.users.create') }}"
           class="bg-blue-600 text-white px-4 py-2 rounded">
            Add User
        </a>
    </div>

    <table class="w-full bg-white shadow rounded text-sm">
        <thead class="bg-gray-100">
            <tr>
                <th class="p-3 text-left">Name</th>
                <th class="p-3 text-left">Email</th>
                <th class="p-3 text-left">Role</th>
                <th class="p-3 text-left">Store</th>
                <th class="p-3 text-left">Status</th>
                <th class="p-3 text-right">Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($users as $user)
                <tr class="border-b">
                    <td class="p-3">{{ $user->name }}</td>
                    <td class="p-3">{{ $user->email }}</td>
                    <td class="p-3 capitalize">{{ $user->roleRelation?->name ?? '-' }}</td>
                    <td class="p-3">
                        {{ $user->store->name ?? '—' }}
                    </td>
                    <td class="p-3">
                        <form method="POST"
                            action="{{ route('admin.users.toggle', $user) }}">
                            @csrf
                            @method('PATCH')

                            <button
                                onclick="return confirm('Change user status?')"
                                class="px-3 py-1 rounded text-xs
                                    {{ $user->is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                                {{ $user->is_active ? 'Active' : 'Inactive' }}
                            </button>
                        </form>
                    </td>
                    <td class="p-3 text-right">
                        <a href="{{ route('admin.users.edit', $user) }}"
                           class="text-blue-600">
                            Edit
                        </a>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="mt-4">
        {{ $users->links() }}
    </div>
</div>
@endsection