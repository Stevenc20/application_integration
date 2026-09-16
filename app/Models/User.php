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
}
    