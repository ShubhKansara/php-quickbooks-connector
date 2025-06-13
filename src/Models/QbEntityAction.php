<?php

namespace ShubhKansara\PhpQuickbooksConnector\Models;

use Illuminate\Database\Eloquent\Model;

class QbEntityAction extends Model
{
    protected $fillable = [
        'qb_entity_id',
        'action',
        'request_template',
        'response_fields',
        'handler_class',
        'active',
    ];

    protected $casts = [
        'response_fields' => 'array',
        'active' => 'boolean',
    ];

    public function entity()
    {
        return $this->belongsTo(QbEntity::class, 'qb_entity_id');
    }
}
