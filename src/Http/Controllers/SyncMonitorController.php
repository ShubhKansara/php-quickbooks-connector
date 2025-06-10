<?php

namespace ShubhKansara\PhpQuickbooksConnector\Http\Controllers;

use Illuminate\Http\Request;
use ShubhKansara\PhpQuickbooksConnector\Models\QbSyncQueue;

class SyncMonitorController extends \App\Http\Controllers\Controller {
    public function index( Request $request ) {
        $status = $request->get( 'status' );
        $query = QbSyncQueue::query();

        if ( $status ) {
            $query->where( 'status', $status );
        }

        $jobs = $query->orderByDesc( 'created_at' )->paginate( 20 );

        return view( 'php-quickbooks::sync-monitor.index', compact( 'jobs', 'status' ) );
    }

    public function show( $id ) {
        $job = QbSyncQueue::findOrFail( $id );
        return view( 'php-quickbooks::sync-monitor.show', compact( 'job' ) );
    }
}
