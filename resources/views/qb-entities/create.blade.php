{{-- filepath: c:\xampp\htdocs\b2b_cf_backend\packages\ShubhKansara\php-quickbooks-connector\resources\views\qb-entities\create.blade.php --}}
@extends('php-quickbooks::layouts.app')
@section('content')
<h2>Create QuickBooks Entity</h2>
<a href="{{ url('/admin/quickbooks') }}" class="inline-block mb-4 text-blue-600 hover:underline">&larr; Back to Dashboard</a>
<form method="POST" action="{{ route('qb-entities.store') }}">
    @csrf
    @include('php-quickbooks::qb-entities.form')
    <button class="btn btn-primary">Create</button>
</form>
@endsection
