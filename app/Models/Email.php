<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Email extends Model
{
    use HasFactory;

    protected $fillable = [
        'message_id',
        'from_email',
        'from_name',
        'to_emails',
        'cc_emails',
        'bcc_emails',
        'subject',
        'body',
        'body_plain',
        'folder',
        'is_read',
        'has_attachments',
        'user_id'
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'has_attachments' => 'boolean',
    ];
}
