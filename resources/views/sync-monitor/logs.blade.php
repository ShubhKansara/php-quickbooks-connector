{{-- filepath: packages/ShubhKansara/php-quickbooks-connector/resources/views/sync-monitor/logs.blade.php --}}
@extends('php-quickbooks::layouts.app')

@section('content')
    <h2>QuickBooks Sync Logs</h2>
    <style>
        .log-row-info    { background: #e9f7ef; }
        .log-row-warning { background: #fffbe6; }
        .log-row-error   { background: #fdecea; }
        .log-row-debug   { background: #f4f6fb; }
        .xml-block       { background: #222; color: #b5f; padding: 8px; border-radius: 4px; margin-bottom: 4px; }
        .pretty-xml      { background: #f8f9fa; color: #333; padding: 8px; border-radius: 4px; font-size: 12px; }
    </style>
    <table class="table table-bordered table-sm">
        <thead>
            <tr>
                <th>Time</th>
                <th>Level</th>
                <th>Message</th>
                <th>Context</th>
            </tr>
        </thead>
        <tbody>
            @foreach($logs as $log)
                @php
                    $level = strtolower($log->level);
                    $rowClass = "log-row-{$level}";
                    $badgeClass = [
                        'info' => 'bg-info text-dark',
                        'warning' => 'bg-warning text-dark',
                        'error' => 'bg-danger',
                        'debug' => 'bg-secondary'
                    ][$level] ?? 'bg-light text-dark';

                    $context = $log->context ?? [];
                @endphp
                <tr class="{{ $rowClass }}">
                    <td>{{ $log->created_at }}</td>
                    <td>
                        <span class="badge {{ $badgeClass }}">{{ strtoupper($log->level) }}</span>
                    </td>
                    <td>{{ $log->message }}</td>
                    <td>
                        @foreach($context as $key => $value)
                            <div>
                                <strong>{{ $key }}:</strong>
                                @if(is_string($value) && Str::startsWith(trim($value), '<?xml'))
                                    <details>
                                        <summary>Show XML</summary>
                                        <div class="xml-block">{{ htmlentities($value) }}</div>
                                        <div class="pretty-xml">
                                            {!! highlight_string(
                                                preg_replace('/></', ">\n<", $value), true
                                            ) !!}
                                        </div>
                                    </details>
                                @else
                                    <pre style="font-size:12px; display:inline;">{{ is_scalar($value) ? $value : json_encode($value, JSON_PRETTY_PRINT) }}</pre>
                                @endif
                            </div>
                        @endforeach
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
    {{ $logs->links() }}
@endsection
