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
        'system_role',
        'position_id',
        'section_id',
        'avatar',
        'is_active',
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

    public function position()
    {
        return $this->belongsTo(Position::class);
    }

    public function section()
    {
        return $this->belongsTo(Section::class);
    }

    public function isSuperadmin(): bool
    {
        return $this->system_role === 'superadmin' || strtolower($this->role) === 'superadmin';
    }

    public function isAdmin(): bool
    {
        return $this->system_role === 'admin' || $this->isSuperadmin();
    }

    public function isUser(): bool
    {
        return $this->system_role === 'user';
    }

    public function hasPosition(string $positionName): bool
    {
        return $this->position && strtolower($this->position->position_name) === strtolower($positionName);
    }

    public function hasSection(string $sectionName): bool
    {
        return $this->section && strtolower($this->section->section_name) === strtolower($sectionName);
    }

    public function getOrganizationalRoleAttribute(): string
    {
        if ($this->isSuperadmin()) return 'Superadmin';
        if ($this->isAdmin() && !$this->position_id) return 'Admin';

        $pos = $this->position ? $this->position->position_name : '';
        $sec = $this->section ? $this->section->section_name : '';

        if ($pos && $sec) return trim("$pos $sec");
        if ($pos) return $pos;
        if ($sec) return $sec;

        return ucfirst($this->role ?? 'Unassigned');
    }

    public function hasFeature(string $featureCode): bool
    {
        // 1. Superadmin override
        if ($this->isSuperadmin()) {
            return true;
        }

        // 2. Legacy fallback
        $role = strtolower($this->role);
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

    // Role helpers for legacy modules
    public function hasRole(string|array $roles): bool
    {
        if (is_array($roles)) {
            return in_array($this->role, $roles);
        }
        return $this->role === $roles;
    }

    public function isForeman(): bool    { return $this->hasPosition('Foreman') || strtolower($this->role) === 'foreman'; }
    public function isProduction(): bool { return $this->hasSection('Produksi') || strtolower($this->role) === 'production' || strtolower($this->role) === 'produksi'; }
    public function isCustomer(): bool   { return strtolower($this->role) === 'customer'; }
    public function isSupervisor(): bool { return $this->hasPosition('SPV') || strtolower($this->role) === 'supervisor'; }
    public function isGroupLeader(): bool { return $this->hasPosition('Leader') || strtolower($this->role) === 'group leader' || str_starts_with(strtolower($this->role), 'leader'); }
}