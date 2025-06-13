{{-- filepath: resources/views/quickbooks/sync-monitor-detail.blade.php --}}
@extends('php-quickbooks::layouts.app')

@section('content')
<div class="max-w-3xl mx-auto bg-white rounded shadow p-6">
    <h2 class="text-xl font-bold mb-4">Sync Job #{{ $job->id }}</h2>
    <ul class="mb-4 space-y-1">
        <li><strong>Entity:</strong> {{ $job->entity_type }}</li>
        <li><strong>Action:</strong> {{ $job->action }}</li>
        <li><strong>Status:</strong> {{ $job->status }}</li>
        <li><strong>Queued At:</strong> {{ $job->queued_at }}</li>
        <li><strong>Started At:</strong> {{ $job->started_at }}</li>
        <li><strong>Processed At:</strong> {{ $job->processed_at }}</li>
    </ul>
    <h4 class="font-semibold mb-1">Payload</h4>
    <pre class="bg-gray-100 rounded p-2 mb-4 text-xs overflow-x-auto">{{ json_encode($job->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
    <h4 class="font-semibold mb-1">Result / Log</h4>
    @php
        $result = $job->result;
        // Try to pretty print if it's JSON
        $prettyResult = null;
        if (is_string($result)) {
            $decoded = json_decode($result, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $prettyResult = json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            }
        }
    @endphp
    <pre class="bg-gray-100 rounded p-2 text-xs overflow-x-auto" style="max-height: 400px;">
{{ $prettyResult ?? $result }}
    </pre>
    <a href="{{ route('qb.sync.monitor') }}">Back to list</a>
        <form action="{{ route('qb.sync.monitor.restart', $job->id) }}" method="POST" class="inline-block ml-4">
            @csrf
            <button type="submit" class="bg-yellow-500 text-white px-4 py-1 rounded hover:bg-yellow-600"
                onclick="return confirm('Are you sure you want to restart this job?')">
                Restart Job
            </button>
        </form>
</div>
@endsection
