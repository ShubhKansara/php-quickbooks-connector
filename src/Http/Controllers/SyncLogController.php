<?php
namespace ShubhKansara\PhpQuickbooksConnector\Http\Controllers;

use ShubhKansara\PhpQuickbooksConnector\Models\QbSyncLog;

class SyncLogController extends \App\Http\Controllers\Controller
{
    public function index()
    {
        $logs = QbSyncLog::orderByDesc('created_at')->paginate(50);
        return view('php-quickbooks::sync-monitor.logs', compact('logs'));
    }
}