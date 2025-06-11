<?php

namespace ShubhKansara\PhpQuickbooksConnector\Models;

use Illuminate\Database\Eloquent\Model;

class QbEntity extends Model
{
    protected $fillable = ['name', 'active'];

    public function actions()
    {
        return $this->hasMany(QbEntityAction::class);
    }
}
