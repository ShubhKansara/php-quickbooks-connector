<?php

namespace ShubhKansara\PhpQuickbooksConnector\Http\Controllers;

use Illuminate\Http\Request;
use ShubhKansara\PhpQuickbooksConnector\Models\QbSyncQueue;

class SyncMonitorController extends \App\Http\Controllers\Controller {
    public function index( Request $request ) {
        // Get filter values from request
        $status = $request->get( 'status' );
        $entity = $request->get( 'entity' );
        $priority = $request->get( 'priority' );

        // Get distinct entities and priorities for filter dropdowns
        $entities = QbSyncQueue::select('entity_type')->distinct()->pluck('entity_type');
        $priorities = QbSyncQueue::select('priority')->distinct()->pluck('priority');

        // Build the query with filters
        $jobs = QbSyncQueue::query()
            ->when($status, fn($q) => $q->where('status', $status))
            ->when($entity, fn($q) => $q->where('entity_type', $entity))
            ->when($priority, fn($q) => $q->where('priority', $priority))
            ->orderByDesc('id')
            ->paginate(20)
            ->appends([
                'status' => $status,
                'entity' => $entity,
                'priority' => $priority,
            ]);

        // Pass all filter values to the view
        return view(
            'php-quickbooks::sync-monitor.index',
            compact( 'jobs', 'status', 'entity', 'priority', 'entities', 'priorities' )
        );
    }

    public function show( $id ) {
        $job = QbSyncQueue::findOrFail( $id );
        return view( 'php-quickbooks::sync-monitor.show', compact( 'job' ) );
    }
}
