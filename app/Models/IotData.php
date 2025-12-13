<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IotData extends Model
{
    protected $table = 'iot_data';

    // Table does not have created_at/updated_at columns
    public $timestamps = false;

    protected $fillable = [
        'farm_id',
        'timestamp',
        'temperature',
        'humidity',
        'ammonia',
        'data_source'
    ];

    protected function casts(): array
    {
        return [
            'timestamp' => 'datetime',
        ];
    }

    public function farm()
    {
        return $this->belongsTo(Farm::class, 'farm_id', 'farm_id');
    }
}
