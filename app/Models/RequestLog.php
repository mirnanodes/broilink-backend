<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RequestLog extends Model
{
    protected $table = 'request_log';
    protected $primaryKey = 'request_id';

    // Table has created_at/updated_at columns
    public $timestamps = true;

    protected $fillable = [
        'user_id',
        'sender_name',
        'phone_number',
        'request_type',
        'request_content',
        'status',
        'sent_time'
    ];

    protected function casts(): array
    {
        return [
            'sent_time' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }
}
