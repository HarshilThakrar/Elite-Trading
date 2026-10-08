<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Chat extends Model
{
    protected $fillable = [
        'user_id',
        'message',
        'file_path',
        'file_type',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
