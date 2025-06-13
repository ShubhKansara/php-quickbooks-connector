<?php

namespace ShubhKansara\PhpQuickbooksConnector\Models;

use Illuminate\Database\Eloquent\Model;

class QbSyncSession extends Model
{
    protected $table = 'qb_sync_sessions';

    /** These are the only fillable columns */
    protected $fillable = [
        'last_pull_at',
    ];

    protected $casts = [
        'last_pull_at' => 'datetime',
    ];
}
