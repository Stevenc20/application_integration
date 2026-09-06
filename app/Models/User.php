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

    public function hasPermission(string $featureCode, string $action = 'can_view'): bool
    {
        // 1. Superadmin override
        if ($this->isSuperadmin()) {
            return true;
        }

        // 2. Check new PermissionMatrix if position or section exists
        if ($this->position_id || $this->section_id) {
            $hasMatrixAccess = PermissionMatrix::whereHas('feature', function ($q) use ($featureCode) {
                    $q->where('feature_code', $featureCode);
                })
                ->where(function($q) {
                    // Match either the specific pos+sec, or pos+null, or null+sec
                    $q->where(function($q1) {
                        $q1->where('position_id', $this->position_id)
                           ->where('section_id', $this->section_id);
                    })->orWhere(function($q2) {
                        $q2->where('position_id', $this->position_id)
                           ->whereNull('section_id');
                    })->orWhere(function($q3) {
                        $q3->whereNull('position_id')
                           ->where('section_id', $this->section_id);
                    });
                })
                ->where($action, true)
                ->exists();

            if ($hasMatrixAccess) {
                return true;
            }
        }

        // 3. Legacy Fallback (defaults to view equivalent in legacy)
        if ($action === 'can_view' || $action === 'can_create') {
            return $this->hasLegacyFeature($featureCode);
        }

        return false;
    }

    public function hasFeature(string $featureCode): bool
    {
        return $this->hasPermission($featureCode, 'can_view');
    }

    public function hasLegacyFeature(string $featureCode): bool
    {
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

    public function isRole($roles): bool
    {
        $roles = is_array($roles) ? $roles : func_get_args();
        $roles = array_map('strtolower', $roles);
        
        // 1. Check System Role
        if (in_array(strtolower($this->system_role ?? ''), $roles)) return true;

        // 2. Check Canonical Position
        if ($this->position) {
            $posName = strtolower($this->position->position_name);
            if (in_array($posName, $roles)) return true;
            // Handle specific aliases
            if ($posName === 'tim member' && in_array('operator', $roles)) return true;
            if ($posName === 'spv' && in_array('supervisor', $roles)) return true;
            if (in_array('group leader', $roles) && $posName === 'leader') return true;
            if (in_array('groupleader', $roles) && $posName === 'leader') return true;
        }

        // 3. Legacy Fallback
        $legacyRole = strtolower($this->role ?? '');
        if (in_array($legacyRole, $roles)) return true;
        if (str_starts_with($legacyRole, 'leader') && in_array('leader', $roles)) return true;
        
        // Special legacy handling for group leader checks
        if (in_array('group leader', $roles) || in_array('groupleader', $roles)) {
            if (str_starts_with($legacyRole, 'leader')) return true;
        }

        return false;
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