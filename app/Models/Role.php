<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    // Database uses 'role_id' as primary key (see migration)
    protected $primaryKey = 'role_id';

    protected $fillable = [
        'name',
        'description'
    ];

    public function users()
    {
        // Foreign key 'role_id' in users table references 'role_id' in roles table
        return $this->hasMany(User::class, 'role_id', 'role_id');
    }
}
