{{-- filepath: c:\xampp\htdocs\b2b_cf_backend\packages\ShubhKansara\php-quickbooks-connector\resources\views\sync-monitor\logs.blade.php --}}

@extends('php-quickbooks::layouts.app')

@section('content')
    <a href="{{ route('qb.sync.monitor') }}" class="inline-block mb-4 text-blue-600 hover:underline">&larr; Back to List</a>
    <h2 class="text-2xl font-bold mb-4">QuickBooks Sync Logs</h2>
    <div class="overflow-x-auto">
        <table class="min-w-full bg-white rounded shadow text-sm">
            <thead>
                <tr class="bg-gray-100 text-left">
                    <th class="py-2 px-4">Time</th>
                    <th class="py-2 px-4">Level</th>
                    <th class="py-2 px-4">Message</th>
                    <th class="py-2 px-4">Context</th>
                </tr>
            </thead>
            <tbody>
                @foreach($logs as $log)
                    @php
                        $level = strtolower($log->level);
                        $rowClass = match($level) {
                            'info' => 'bg-green-50',
                            'warning' => 'bg-yellow-50',
                            'error' => 'bg-red-50',
                            'debug' => 'bg-blue-50',
                            default => 'bg-gray-50'
                        };
                        $badgeClass = match($level) {
                            'info' => 'bg-green-200 text-green-800',
                            'warning' => 'bg-yellow-200 text-yellow-800',
                            'error' => 'bg-red-200 text-red-800',
                            'debug' => 'bg-blue-200 text-blue-800',
                            default => 'bg-gray-200 text-gray-800'
                        };
                        $context = $log->context ?? [];
                    @endphp
                    <tr class="{{ $rowClass }}">
                        <td class="py-2 px-4">{{ $log->created_at }}</td>
                        <td class="py-2 px-4">
                            <span class="px-2 py-1 rounded text-xs {{ $badgeClass }}">{{ strtoupper($log->level) }}</span>
                        </td>
                        <td class="py-2 px-4">{{ $log->message }}</td>
                        <td class="py-2 px-4">
                            @foreach($context as $key => $value)
                                <div>
                                    <strong>{{ $key }}:</strong>
                                    @if(is_string($value) && Str::startsWith(trim($value), '<?xml'))
                                        <details>
                                            <summary class="cursor-pointer text-blue-600">Show XML</summary>
                                            <div class="bg-gray-900 text-purple-200 p-2 rounded my-1 overflow-x-auto text-xs">{{ htmlentities($value) }}</div>
                                            <div class="bg-gray-100 text-gray-800 p-2 rounded text-xs">{!! highlight_string(
                                                preg_replace('/></', ">\n<", $value), true
                                            ) !!}</div>
                                        </details>
                                    @else
                                        <pre class="inline text-xs">{{ is_scalar($value) ? $value : json_encode($value, JSON_PRETTY_PRINT) }}</pre>
                                    @endif
                                </div>
                            @endforeach
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <div class="mt-4">
            {{ $logs->links() }}
        </div>
    </div>
@endsection
