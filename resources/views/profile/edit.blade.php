@extends('layouts.app')

@section('title', 'My Profile')

@section('content')
<div class="max-w-3xl mx-auto py-10 px-4">

    <h1 class="text-2xl font-bold mb-6">My Profile</h1>

    {{-- Status --}}
    @if (session('status') === 'profile-updated')
        <div class="mb-4 p-3 bg-green-100 text-green-700 rounded">
            Profile updated successfully.
        </div>
    @endif

    {{-- Profile Info --}}
    <form method="POST" action="{{ route('profile.update') }}"
          class="bg-white p-6 rounded shadow mb-6">
        @csrf
        @method('PATCH')

        <h2 class="text-lg font-semibold mb-4">Personal Information</h2>

        <div class="mb-4">
            <label class="block text-sm font-medium">Name</label>
            <input type="text"
                   name="name"
                   value="{{ old('name', $user->name) }}"
                   class="w-full border rounded px-3 py-2">
            @error('name') <small class="text-red-600">{{ $message }}</small> @enderror
        </div>

        <div class="mb-4">
            <label class="block text-sm font-medium">Email</label>
            <input type="email"
                   name="email"
                   value="{{ old('email', $user->email) }}"
                   class="w-full border rounded px-3 py-2">
            @error('email') <small class="text-red-600">{{ $message }}</small> @enderror
        </div>

        <div class="mb-4">
            <label class="block text-sm font-medium">Phone Number</label>
            <input type="text"
                name="phone"
                value="{{ old('phone', $user->phone) }}"
                placeholder="+965 5555 5555"
                class="w-full border rounded px-3 py-2">
            @error('phone')
                <small class="text-red-600">{{ $message }}</small>
            @enderror
        </div>

        <button class="bg-blue-600 text-white px-4 py-2 rounded">
            Save Changes
        </button>
    </form>

    {{-- Password --}}
    @if (session('status') === 'password-updated')
        <div class="mb-4 p-3 bg-green-100 text-green-700 rounded">
            Password updated successfully.
        </div>
    @endif
    @if ($errors->updatePassword->any())
        <div class="mb-4 p-3 bg-red-100 text-red-700 rounded">
            Password update failed. Please make sure the current password is correct and new password and confirm passwords fields match.
        </div>
    @endif
    <form method="POST" action="{{ route('password.update') }}"
          class="bg-white p-6 rounded shadow mb-6">
        @csrf
        @method('PUT')

        <h2 class="text-lg font-semibold mb-4">Change Password</h2>

        <div class="mb-4">
            <label class="block text-sm font-medium">Current Password</label>
            <input type="password"
                   name="current_password"
                   class="w-full border rounded px-3 py-2">
            @error('current_password') <small class="text-red-600">{{ $message }}</small> @enderror
        </div>

        <div class="mb-4">
            <label class="block text-sm font-medium">New Password</label>
            <input type="password"
                   name="password"
                   class="w-full border rounded px-3 py-2">
            @error('password') <small class="text-red-600">{{ $message }}</small> @enderror
        </div>

        <div class="mb-4">
            <label class="block text-sm font-medium">Confirm Password</label>
            <input type="password"
                   name="password_confirmation"
                   class="w-full border rounded px-3 py-2">
        </div>

        <button class="bg-gray-800 text-white px-4 py-2 rounded">
            Update Password
        </button>
    </form>

    {{-- Delete --}}
    <form method="POST"
          action="{{ route('profile.destroy') }}"
          onsubmit="return confirm('Are you sure? This action cannot be undone.')"
          class="bg-white p-6 rounded shadow border border-red-200">
        @csrf
        @method('DELETE')

        <h2 class="text-lg font-semibold mb-4 text-red-600">
            Delete Account
        </h2>

        <div class="mb-4">
            <label class="block text-sm font-medium">Confirm Password</label>
            <input type="password"
                   name="password"
                   class="w-full border rounded px-3 py-2">
            @error('userDeletion.password')
                <small class="text-red-600">{{ $message }}</small>
            @enderror
        </div>

        <button class="bg-red-600 text-white px-4 py-2 rounded">
            Delete My Account
        </button>
    </form>

</div>
@endsection