<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FarmConfig extends Model
{
    protected $table = 'farm_config';
    protected $primaryKey = 'config_id';

    // Table does not have created_at/updated_at columns
    public $timestamps = false;

    protected $fillable = [
        'farm_id',
        'parameter_name',
        'value'
    ];

    public function farm()
    {
        return $this->belongsTo(Farm::class, 'farm_id', 'farm_id');
    }
}
