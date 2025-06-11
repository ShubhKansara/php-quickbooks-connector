@extends('php-quickbooks::layouts.app')
@section('content')
<h2>QuickBooks Entities</h2>
<a href="{{ route('qb-entities.create') }}" class="btn btn-primary mb-3">Add Entity</a>
<table class="table table-bordered">
    <thead>
        <tr>
            <th>Name</th>
            <th>Active</th>
            <th>Actions</th>
            <th>Entity Actions</th>
            <th></th>
        </tr>
    </thead>
    <tbody>
        @foreach($entities as $entity)
            <tr>
                <td>{{ $entity->name }}</td>
                <td>{{ $entity->active ? 'Yes' : 'No' }}</td>
                <td>
                    <a href="{{ route('qb-entities.edit', ['qb_entity' => $entity->id]) }}" class="btn btn-sm btn-warning">Edit</a>
                    <form action="{{ route('qb-entities.destroy', ['qb_entity' => $entity->id]) }}" method="POST" style="display:inline;">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-sm btn-danger" onclick="return confirm('Delete this entity and all actions?')">Delete</button>
                    </form>
                </td>
                <td>
                    @foreach($entity->actions as $action)
                        <div>
                            <strong>{{ $action->action }}</strong>
                            ({{ $action->active ? 'Active' : 'Inactive' }})
                        </div>
                    @endforeach
                </td>
            </tr>
        @endforeach
    </tbody>
</table>
{{ $entities->links() }}
@endsection
