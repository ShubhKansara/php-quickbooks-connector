{{-- filepath: c:\xampp\htdocs\b2b_cf_backend\packages\ShubhKansara\php-quickbooks-connector\resources\views\qb-entities\edit.blade.php --}}
@extends('php-quickbooks::layouts.app')
@section('content')
<h2>Edit QuickBooks Entity</h2>
<a href="{{ url('/admin/quickbooks') }}" class="inline-block mb-4 text-blue-600 hover:underline">&larr; Back to Dashboard</a>
<form method="POST" action="{{ route('qb-entities.update', ['qb_entity' => $qbEntity->id]) }}">
    @method('PUT')
    @csrf
    @include('php-quickbooks::qb-entities.form', ['qbEntity' => $qbEntity])
    <button class="btn btn-success">Update</button>
</form>
@endsection
