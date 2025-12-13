<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Farm extends Model
{
    protected $primaryKey = 'farm_id';

    protected $fillable = [
        'owner_id',
        'peternak_id',
        'farm_name',
        'location',
        'initial_population',
        'initial_weight',
        'farm_area'
    ];

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id', 'user_id');
    }
    

    public function peternak()
    {
        return $this->belongsTo(User::class, 'peternak_id', 'user_id');
    }

    public function configs()
    {
        return $this->hasMany(FarmConfig::class, 'farm_id', 'farm_id');
    }

    public function iotData()
    {
        return $this->hasMany(IotData::class, 'farm_id', 'farm_id');
    }

    public function manualData()
    {
        return $this->hasMany(ManualData::class, 'farm_id', 'farm_id');
    }
}
