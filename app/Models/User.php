<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
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

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'user_role');
    }

    public function hasRole(string|Role|array $roles): bool
    {
        $slugs = collect(is_array($roles) ? $roles : [$roles])->map(fn ($role) => $role instanceof Role ? $role->slug : $role);

        return $this->roles()->whereIn('slug', $slugs)->exists();
    }

    public function hasPermissionTo(string $permission): bool
    {
        if ($this->hasRole('super_admin')) {
            return true;
        }

        return $this->roles()->whereHas('permissions', fn ($query) => $query->where('slug', $permission))->exists();
    }

    public function assignRole(string|Role $role): static
    {
        $role = $role instanceof Role ? $role : Role::where('slug', $role)->firstOrFail();
        $this->roles()->syncWithoutDetaching($role->id);

        return $this;
    }

    public function syncRoles(array $roles): static
    {
        $roleIds = Role::query()->whereIn('slug', $roles)->pluck('id');
        $this->roles()->sync($roleIds);

        return $this;
    }
}
