<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name', 'email', 'password', 'role',
        'employee_id', 'department', 'assigned_line',
        'company_name', 'phone', 'is_active', 'last_login_at',
        'signature',
    ];

    protected $hidden = [
        'password', 'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_login_at'     => 'datetime',
        'is_active'         => 'boolean',
        'password'          => 'hashed',
    ];

    // ── Role helpers ─────────────────────────────────────────

    public function hasRole(string|array $roles): bool
    {
        if (is_array($roles)) {
            return in_array($this->role, $roles);
        }
        return $this->role === $roles;
    }

    public function isAdmin(): bool      { return $this->role === 'Admin'; }
    // public function isQA(): bool         { return in_array($this->role, ['admin', 'qa']); }
    // public function isInspector(): bool  { return $this->role === 'inspector'; }
    public function isForeman(): bool    { return $this->role === 'Foreman'; }
    public function isProduction(): bool { return $this->role === 'Production'; }
    public function isCustomer(): bool   { return $this->role === 'Customer'; }
    public function isSupervisor(): bool   { return $this->role === 'Supervisor'; }
    public function isGroupLeader(): bool   { return $this->role === 'Group Leader'; }
}