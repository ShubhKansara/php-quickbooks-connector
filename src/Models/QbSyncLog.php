<?php

namespace ShubhKansara\PhpQuickbooksConnector\Models;

use Illuminate\Database\Eloquent\Model;

class QbSyncLog extends Model
{
    protected $fillable = ['level', 'message', 'context'];

    protected $casts = [
        'context' => 'array',
    ];
}
