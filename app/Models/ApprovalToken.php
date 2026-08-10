<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApprovalToken extends Model
{
    protected $table = 'approval_tokens';

    protected $fillable = [
        'qpr_id', 'user_id', 'token', 'role', 
        'nama', 'position', 'signature', 'is_used', 'signed_at',
    ];

    protected $casts = [
        'is_used'   => 'boolean',
        'signed_at' => 'datetime',
    ];

    public function qpr()
    {
        return $this->belongsTo(Qpr::class, 'qpr_id');
    }
    public function user()     
    { 
        return $this->belongsTo(User::class);
    }
}