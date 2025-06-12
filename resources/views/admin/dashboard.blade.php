{{-- filepath: c:\xampp\htdocs\b2b_cf_backend\packages\ShubhKansara\php-quickbooks-connector\resources\views\admin\dashboard.blade.php --}}
@extends('php-quickbooks::layouts.app')

@section('content')
<div class="max-w-lg mx-auto mt-16 bg-white rounded shadow p-8 flex flex-col items-center">
    <h2 class="text-2xl font-bold mb-8 text-blue-700">QuickBooks Admin Dashboard</h2>
    <div class="w-full flex flex-col space-y-4">
        <a href="{{ route('qb-entities.index') }}" class="block w-full text-center bg-blue-600 text-white px-6 py-3 rounded hover:bg-blue-700 font-semibold text-lg">Manage Entities</a>
        <a href="{{ route('qb.sync.monitor') }}" class="block w-full text-center bg-green-600 text-white px-6 py-3 rounded hover:bg-green-700 font-semibold text-lg">View Sync Logs</a>
    </div>
</div>
@endsection
