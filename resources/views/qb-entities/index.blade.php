@extends('php-quickbooks::layouts.app')
@section('content')
<h2 class="text-2xl font-bold mb-4">QuickBooks Entities</h2>
<a href="{{ url('/admin/quickbooks') }}" class="inline-block mb-4 text-blue-600 hover:underline">&larr; Back to Dashboard</a>
<a href="{{ route('qb-entities.create') }}" class="inline-block mb-4 bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Add Entity</a>
<div class="overflow-x-auto">
<table class="min-w-full bg-white rounded shadow">
    <thead>
        <tr class="bg-gray-100 text-left">
            <th class="py-2 px-4">Name</th>
            <th class="py-2 px-4">Active</th>
            <th class="py-2 px-4">Actions</th>
            <th class="py-2 px-4">Entity Actions</th>
        </tr>
    </thead>
    <tbody>
        @foreach($entities as $entity)
            <tr class="border-b">
                <td class="py-2 px-4">{{ $entity->name }}</td>
                <td class="py-2 px-4">{{ $entity->active ? 'Yes' : 'No' }}</td>
                <td class="py-2 px-4">
                    <a href="{{ route('qb-entities.edit', ['qb_entity' => $entity->id]) }}" class="text-yellow-600 hover:underline">Edit</a>
                    <form action="{{ route('qb-entities.destroy', ['qb_entity' => $entity->id]) }}" method="POST" class="inline">
                        @csrf
                        @method('DELETE')
                        <button class="text-red-600 hover:underline ml-2" onclick="return confirm('Delete this entity and all actions?')">Delete</button>
                    </form>
                </td>
                <td class="py-2 px-4">
                    @foreach($entity->actions as $action)
                        <div class="mb-1">
                            <span class="font-semibold">{{ $action->action }}</span>
                            <span class="text-xs text-gray-500">({{ $action->active ? 'Active' : 'Inactive' }})</span>
                        </div>
                    @endforeach
                </td>
            </tr>
        @endforeach
    </tbody>
</table>
</div>
<div class="mt-4">
    {{ $entities->links() }}
</div>
@endsection
