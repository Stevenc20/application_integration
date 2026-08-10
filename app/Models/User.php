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
        'nrp',
        'password',
        'role',
        'avatar',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
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

    public function hasFeature(string $featureCode): bool
    {
        $role = strtolower($this->role);
    
        if ($role === 'superadmin') {
            return true;
        }
        if (str_starts_with($role, 'leader') || $role === 'shearing' || $role === 'handwork') {
            $role = 'leader';
        }
        $hambatanRoles = ['dies_shop', 'plant_service', 'irm', 'logistik', 'produksi'];
        if (in_array($role, $hambatanRoles)) {
            $role = 'hambatan';
        }

        return RoleFeature::where('role', $role)
            ->whereHas('feature', function ($q) use ($featureCode) {
                $q->where('feature_code', $featureCode);
            })  
            ->where('enabled', true)
            ->exists();
    }

    // ── Role helpers for QA module ───────────────────────────

    public function hasRole(string|array $roles): bool
    {
        if (is_array($roles)) {
            return in_array($this->role, $roles);
        }
        return $this->role === $roles;
    }

    public function isAdmin(): bool      { return $this->role === 'Admin' || strtolower($this->role) === 'superadmin' || strtolower($this->role) === 'admin'; }
    public function isForeman(): bool    { return $this->role === 'Foreman' || strtolower($this->role) === 'foreman'; }
    public function isProduction(): bool { return $this->role === 'Production' || strtolower($this->role) === 'production'; }
    public function isCustomer(): bool   { return $this->role === 'Customer' || strtolower($this->role) === 'customer'; }
    public function isSupervisor(): bool   { return $this->role === 'Supervisor' || strtolower($this->role) === 'supervisor'; }
    public function isGroupLeader(): bool   { return $this->role === 'Group Leader' || strtolower($this->role) === 'group leader' || strtolower($this->role) === 'leader'; }
}