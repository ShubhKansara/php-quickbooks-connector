{{-- filepath: resources/views/quickbooks/sync-monitor.blade.php --}}
@extends('php-quickbooks::layouts.app')

@section('content')
    <div class="container">
        <h2>QuickBooks Sync Manager</h2>
        <form method="get" class="mb-3">
            <select name="status" onchange="this.form.submit()">
                <option value="">All Statuses</option>
                <option value="pending" {{ $status == 'pending' ? 'selected' : '' }}>Pending</option>
                <option value="processing" {{ $status == 'processing' ? 'selected' : '' }}>Processing</option>
                <option value="success" {{ $status == 'success' ? 'selected' : '' }}>Success</option>
                <option value="failed" {{ $status == 'failed' ? 'selected' : '' }}>Failed</option>
            </select>
        </form>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Entity</th>
                    <th>Action</th>
                    <th>Status</th>
                    <th>Queued At</th>
                    <th>Started At</th>
                    <th>Processed At</th>
                    <th>Log</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($jobs as $job)
                    <tr>
                        <td>{{ $job->id }}</td>
                        <td>{{ $job->entity_type }}</td>
                        <td>{{ $job->action }}</td>
                        <td>{{ $job->status }}</td>
                        <td>{{ $job->queued_at }}</td>
                        <td>{{ $job->started_at }}</td>
                        <td>{{ $job->processed_at }}</td>
                        <td>
                            <a href="{{ route('qb.sync.monitor.show', $job->id) }}">View</a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        {{ $jobs->links() }}
    </div>
@endsection
