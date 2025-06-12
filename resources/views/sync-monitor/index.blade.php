{{-- filepath: resources/views/quickbooks/sync-monitor.blade.php --}}
@extends('php-quickbooks::layouts.app')

@section('content')
    <div class="max-w-7xl mx-auto">
        <h2 class="text-2xl font-bold mb-6">QuickBooks Sync Manager</h2>
        <a href="{{ url('/admin/quickbooks') }}" class="inline-block mb-4 text-blue-600 hover:underline">&larr; Back to Dashboard</a>
        <a href="{{ route('qb.sync.monitor.logs') }}" class="inline-block mb-4 ml-4 text-green-600 hover:underline">View Detailed Logs</a>
        <form method="get" class="mb-4 flex flex-wrap gap-4 items-center">
            <select name="status" onchange="this.form.submit()" class="border rounded px-3 py-2">
                <option value="">All Statuses</option>
                <option value="pending" {{ $status == 'pending' ? 'selected' : '' }}>Pending</option>
                <option value="processing" {{ $status == 'processing' ? 'selected' : '' }}>Processing</option>
                <option value="completed" {{ $status == 'completed' ? 'selected' : '' }}>Completed</option>
                <option value="error" {{ $status == 'error' ? 'selected' : '' }}>Failed</option>
            </select>
            <select name="entity" onchange="this.form.submit()" class="border rounded px-3 py-2">
                <option value="">All Entities</option>
                @foreach($entities as $e)
                    <option value="{{ $e }}" {{ (isset($entity) && $entity == $e) ? 'selected' : '' }}>{{ $e }}</option>
                @endforeach
            </select>
            <select name="priority" onchange="this.form.submit()" class="border rounded px-3 py-2">
                <option value="">All Priorities</option>
                @foreach($priorities as $p)
                    <option value="{{ $p }}" {{ (isset($priority) && $priority == $p) ? 'selected' : '' }}>{{ ucfirst($p) }}</option>
                @endforeach
            </select>
        </form>
        <div class="overflow-x-auto">
            <table class="min-w-full bg-white rounded shadow">
                <thead>
                    <tr class="bg-gray-100 text-left">
                        <th class="py-2 px-4">ID</th>
                        <th class="py-2 px-4">Entity</th>
                        <th class="py-2 px-4">Action</th>
                        <th class="py-2 px-4">Priority</th>
                        <th class="py-2 px-4">Status</th>
                        <th class="py-2 px-4">Queued At</th>
                        <th class="py-2 px-4">Processed At</th>
                        <th class="py-2 px-4">Log</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($jobs as $job)
                        <tr class="border-b">
                            <td class="py-2 px-4">{{ $job->id }}</td>
                            <td class="py-2 px-4">{{ $job->entity_type }}</td>
                            <td class="py-2 px-4">{{ $job->action }}</td>
                            <td class="py-2 px-4">{{ ucfirst($job->priority) }}</td>
                            <td class="py-2 px-4">
                                <span class="px-2 py-1 rounded text-xs
                                    @if($job->status == 'pending') bg-yellow-100 text-yellow-800
                                    @elseif($job->status == 'processing') bg-blue-100 text-blue-800
                                    @elseif($job->status == 'completed') bg-green-100 text-green-800
                                    @elseif($job->status == 'error') bg-red-100 text-red-800
                                    @else bg-gray-100 text-gray-800 @endif
                                ">
                                    {{ ucfirst($job->status) }}
                                </span>
                            </td>
                            <td class="py-2 px-4">{{ $job->queued_at }}</td>
                            <td class="py-2 px-4">{{ $job->processed_at }}</td>
                            <td class="py-2 px-4">
                                <a href="{{ route('qb.sync.monitor.show', $job->id) }}" class="text-blue-600 hover:underline">View</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="mt-4">
                {{ $jobs->links() }}
            </div>
        </div>
    </div>
@endsection
