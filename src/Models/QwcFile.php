<?php
namespace ShubhKansara\PhpQuickbooksConnector\Models;

use Illuminate\Database\Eloquent\Model;

class QwcFile extends Model
{
    protected $fillable = [
        'title', 'username', 'password', 'description', 'run_every_n_minutes',
        'file_path', 'download_url', 'enabled'
    ];
}
