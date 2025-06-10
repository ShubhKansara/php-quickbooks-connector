{{-- filepath: resources/views/quickbooks/sync-monitor-detail.blade.php --}}
@extends('php-quickbooks::layouts.app')

@section('content')
<div class="container">
    <h2>Sync Job #{{ $job->id }}</h2>
    <ul>
        <li><strong>Entity:</strong> {{ $job->entity_type }}</li>
        <li><strong>Action:</strong> {{ $job->action }}</li>
        <li><strong>Status:</strong> {{ $job->status }}</li>
        <li><strong>Queued At:</strong> {{ $job->queued_at }}</li>
        <li><strong>Started At:</strong> {{ $job->started_at }}</li>
        <li><strong>Processed At:</strong> {{ $job->processed_at }}</li>
    </ul>
    <h4>Payload</h4>
    <pre>{{ json_encode($job->payload, JSON_PRETTY_PRINT) }}</pre>
    <h4>Result / Log</h4>
    <pre>{{ $job->result }}</pre>
    <a href="{{ route('qb.sync.monitor') }}">Back to list</a>
</div>
@endsection
