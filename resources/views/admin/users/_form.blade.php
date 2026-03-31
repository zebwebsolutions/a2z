<div class="grid grid-cols-1 md:grid-cols-2 gap-4">

    <div>
        <label class="block text-sm font-medium">Name</label>
        <input type="text" name="name"
               value="{{ old('name', $user->name ?? '') }}"
               class="w-full border rounded px-3 py-2">
    </div>

    <div>
        <label class="block text-sm font-medium">Email</label>
        <input type="email" name="email"
               value="{{ old('email', $user->email ?? '') }}"
               class="w-full border rounded px-3 py-2">
    </div>

    <div>
        <label class="block text-sm font-medium">Phone</label>
        <input type="text" name="phone"
               value="{{ old('phone', $user->phone ?? '') }}"
               class="w-full border rounded px-3 py-2">
    </div>

    <div>
        <label class="block text-sm font-medium">Role</label>
        <select name="role_id"
                class="w-full border rounded px-3 py-2">
            @foreach($roles as $role)
                <option value="{{ $role->id }}"
                    @selected(old('role_id', $user->role_id ?? '') == $role->id)>
                    {{ ucfirst($role->name) }}
                </option>
            @endforeach
        </select>
    </div>

    <div>
        <label class="block text-sm font-medium">Store</label>
        <select name="store_id"
                class="w-full border rounded px-3 py-2">
            <option value="">— None —</option>
            @foreach($stores as $store)
                <option value="{{ $store->id }}"
                    @selected(old('store_id', $user->store_id ?? '') == $store->id)>
                    {{ $store->name }}
                </option>
            @endforeach
        </select>

        <small class="text-gray-500">
            Required for salesmen & technicians
        </small>
    </div>

    <div>
        <label class="block text-sm font-medium">Password</label>
        <input type="password" name="password"
               class="w-full border rounded px-3 py-2">
        @isset($user)
            <small class="text-gray-500">Leave empty to keep current password</small>
        @endisset
    </div>

</div>