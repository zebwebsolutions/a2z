<?php 

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $request->validate(['role' => ['nullable', 'string', 'max:100'], 'search' => ['nullable', 'string', 'max:255']]);
        $roles = Role::pluck('name')->merge(User::whereNotNull('role')->distinct()->pluck('role'))
            ->merge(['admin', 'salesman', 'customer', 'technician'])->unique()->sort()->values();
        $users = User::with(['roleRelation', 'store'])
            ->when($request->filled('role'), function ($query) use ($request) {
                $query->where(function ($query) use ($request) {
                    $query->whereHas('roleRelation', fn ($role) => $role->where('name', $request->input('role')))
                        ->orWhere(fn ($fallback) => $fallback->whereDoesntHave('roleRelation')->where('role', $request->input('role')));
                });
            })
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = '%'.$request->input('search').'%';
                $query->where(fn ($query) => $query->where('name', 'like', $search)->orWhere('email', 'like', $search)->orWhere('phone', 'like', $search));
            })
            ->latest()->paginate(20)->withQueryString();
        return view('admin.users.index', compact('users', 'roles'));
    }

    public function create()
    {
        $roles = Role::orderBy('name')->get();
        $stores = Store::orderBy('name')->get();
        return view('admin.users.create', compact('roles', 'stores'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'nullable|string|max:30',
            'role_id' => 'required|exists:roles,id',
            'store_id' => 'nullable|exists:stores,id',
            'password' => 'required|min:6',
        ]);

        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'role_id' => $request->role_id,
            'store_id' => $request->store_id,
            'password' => Hash::make($request->password),
            'is_active' => true,
        ]);

        return redirect()->route('admin.users.index')
            ->with('success', 'User created successfully');
    }

    public function edit(User $user)
    {
        $roles = Role::orderBy('name')->get();
        $stores = Store::orderBy('name')->get();
        return view('admin.users.edit', compact('user', 'roles', 'stores'));
    }

    public function update(Request $request, User $user)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|max:30',
            'role_id' => 'required|exists:roles,id',
            'store_id' => 'nullable|exists:stores,id',
            'password' => 'nullable|min:6',
        ]);

        $user->update([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'role_id' => $request->role_id,
            'store_id' => $request->store_id,
        ]);

        if ($request->filled('password')) {
            $user->update([
                'password' => Hash::make($request->password),
            ]);
        }

        return redirect()->route('admin.users.index')
            ->with('success', 'User updated successfully');
    }

    public function toggle(User $user)
    {
        // Prevent admin from disabling themselves
        if ($user->id === auth()->id()) {
            return back()->with('error', 'You cannot deactivate your own account.');
        }

        $user->update([
            'is_active' => !$user->is_active,
        ]);

        return back()->with('success', 'User status updated.');
    }

    public function destroy(User $user)
    {
        // Prevent admin from deleting themselves
        if ($user->id === auth()->id()) {
            return back()->with('error', 'You cannot delete your own account.');
        }
        
        // Only admin can delete users
        if (!auth()->user()->hasRole('admin')) {
            return back()->with('error', 'You do not have permission to delete users.');
        }

        $user->delete();

        return back()->with('success', 'User deleted successfully.');
    }

}