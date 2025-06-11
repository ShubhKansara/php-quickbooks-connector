{{-- filepath: c:\xampp\htdocs\b2b_cf_backend\packages\ShubhKansara\php-quickbooks-connector\resources\views\qb-entities\create.blade.php --}}
@extends('php-quickbooks::layouts.app')
@section('content')
<h2>Create QuickBooks Entity</h2>
<form method="POST" action="{{ route('qb-entities.store') }}">
    @csrf
    @include('php-quickbooks::qb-entities.form')
    <button class="btn btn-primary">Create</button>
</form>
@endsection
