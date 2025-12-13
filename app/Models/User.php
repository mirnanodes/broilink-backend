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

    /**
     * Get all peternaks assigned to farms owned by this user (Owner role)
     * Returns users where their assignedFarm's owner_id matches this user
     */
    public function peternaks()
    {
        return $this->hasManyThrough(
            User::class,
            Farm::class,
            'owner_id',      // Foreign key on farms table (owner's farms)
            'user_id',       // Foreign key on users table (peternak's user_id)
            'user_id',       // Local key on this user (owner)
            'peternak_id'    // Local key on farms table (assigned peternak)
        )->whereNotNull('peternak_id');
    }

    /**
     * Get the owner of this peternak via their assigned farm
     * For Peternak role - returns the Owner who hired them
     */
    public function owner()
    {
        return $this->hasOneThrough(
            User::class,
            Farm::class,
            'peternak_id',   // Foreign key on farms table
            'user_id',       // Foreign key on users table
            'user_id',       // Local key on this user (peternak)
            'owner_id'       // Local key on farms table
        );
    }

}
