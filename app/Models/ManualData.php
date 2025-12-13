<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ManualData extends Model
{
    protected $table = 'manual_data';

    protected $fillable = [
        'farm_id',
        'user_id_input',
        'report_date',
        'konsumsi_pakan',
        'konsumsi_air',
        'rata_rata_bobot',
        'jumlah_kematian'
    ];

    protected function casts(): array
    {
        return [
            'report_date' => 'date',
        ];
    }

    public function farm()
    {
        return $this->belongsTo(Farm::class, 'farm_id', 'farm_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id_input', 'user_id');
    }
}
