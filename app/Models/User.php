<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens;

    protected $primaryKey = 'user_id';

    protected $fillable = [
        'role_id',
        'username',
        'email',
        'password',
        'name',
        'phone_number',
        'profile_pic',
        'status',
        'date_joined',
        'last_login'
    ];

    protected $hidden = [
        'password',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'date_joined' => 'datetime',
            'last_login' => 'datetime',
        ];
    }

    public function role()
    {
        // Foreign key 'role_id' in users table references 'role_id' in roles table
        return $this->belongsTo(Role::class, 'role_id', 'role_id');
    }

    public function farms()
    {
        return $this->hasMany(Farm::class, 'owner_id', 'user_id');
    }

    public function assignedFarm()
    {
        return $this->hasOne(Farm::class, 'peternak_id', 'user_id');
    }

    public function ownerFromFarm()
    {
    return $this->assignedFarm?->owner;
    }

}
