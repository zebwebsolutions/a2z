<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'role',
        'role_id',
        'store_id',
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function roleRelation() {
        return $this->belongsTo(Role::class, 'role_id');
    }
    public function store() {
        return $this->belongsTo(Store::class);
    }

    protected static function booted()
    {
        static::saving(function ($user) {

            // role_id → role (string)
            if ($user->role_id) {
                $role = Role::find($user->role_id);
                if ($role) {
                    $user->role = $role->name;
                }
            }

            // role (string) → role_id (fallback)
            if (!$user->role_id && !empty($user->role)) {
                $role = Role::where('name', $user->role)->first();
                if ($role) {
                    $user->role_id = $role->id;
                }
            }
        });
    }

    public function hasRole(string ...$roles): bool
    {
        return in_array($this->roleRelation?->name ?? $this->role, $roles);
    }

    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    public function isSalesman(): bool
    {
        return $this->hasRole('salesman');
    }

    public function isTechnician(): bool
    {
        return $this->hasRole('technician');
    }

    public function canView(string $module): bool
    {
        $role = $this->roleRelation?->name ?? $this->role;

        return match ($module) {
            'dashboard'      => in_array($role, ['admin', 'salesman', 'technician']),
            'products'       => in_array($role, ['admin', 'salesman']),
            'categories'     => in_array($role, ['admin', 'salesman']),
            'brands'         => in_array($role, ['admin', 'salesman']),
            'stores'         => $role === 'admin',
            'repairs'        => in_array($role, ['admin', 'technician']),
            'spare-parts'    => in_array($role, ['admin', 'salesman']),
            'orders'         => in_array($role, ['admin', 'salesman']),
            'sliders'        => $role === 'admin',
            'home-sections'  => $role === 'admin',
            'users'          => $role === 'admin',
            default          => false,
        };
    }

    public function canEdit(string $module): bool
    {
        $role = $this->roleRelation?->name ?? $this->role;

        return match ($module) {
            'products'    => $role === 'admin',
            'categories'  => $role === 'admin',
            'brands'      => $role === 'admin',
            'repairs'     => in_array($role, ['admin', 'technician']),
            'spare-parts' => in_array($role, ['admin', 'salesman']),
            'orders'      => in_array($role, ['admin', 'salesman']),
            default       => false,
        };
    }


}
