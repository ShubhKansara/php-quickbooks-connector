<?php

namespace ShubhKansara\PhpQuickbooksConnector\Models;

use Illuminate\Database\Eloquent\Model;

class QbSyncQueue extends Model
{
    protected $table = 'qb_sync_queue';

    protected $guarded = [];

    protected $casts = [
        'payload' => 'array',
        'queued_at' => 'datetime',
        'available_at' => 'datetime',
    ];
}
