@csrf
<div class="mb-3">
    <label for="name" class="form-label">Entity Name</label>
    <input type="text" name="name" id="name" class="form-control" value="{{ old('name', $qbEntity->name ?? '') }}" required>
</div>
<div class="mb-3">
    <label for="active" class="form-label">Active</label>
    <input type="checkbox" name="active" id="active" value="1" {{ old('active', $qbEntity->active ?? true) ? 'checked' : '' }}>
</div>

<hr>
<h5>Actions</h5>
<div id="actions-list">
    @php
        $actions = old('actions', isset($qbEntity) ? $qbEntity->actions->toArray() : [ ['action'=>'sync'] ]);
    @endphp
    @foreach($actions as $i => $action)
        <div class="card mb-2 p-2 action-block">
            <input type="hidden" name="actions[{{ $i }}][id]" value="{{ $action['id'] ?? '' }}">
            <div class="row">
                <div class="col-md-2">
                    <label>Action</label>
                    <input type="text" name="actions[{{ $i }}][action]" class="form-control" value="{{ $action['action'] ?? '' }}" required>
                </div>
                <div class="col-md-3">
                    <label>Handler Class</label>
                    <input type="text" name="actions[{{ $i }}][handler_class]" class="form-control" value="{{ $action['handler_class'] ?? '' }}">
                </div>
                <div class="col-md-2">
                    <label>Active</label>
                    <input type="checkbox" name="actions[{{ $i }}][active]" value="1" {{ ($action['active'] ?? true) ? 'checked' : '' }}>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="button" class="btn btn-danger btn-sm remove-action">Remove</button>
                </div>
            </div>
            <div class="mb-2">
                <label>Request Template (QBXML)</label>
                <textarea name="actions[{{ $i }}][request_template]" class="form-control" rows="3" required>{{ $action['request_template'] ?? '' }}</textarea>
            </div>
            <div class="mb-2">
                <label>Response Fields (JSON)</label>
                <textarea name="actions[{{ $i }}][response_fields]" class="form-control" rows="2">{{ isset($action['response_fields']) ? (is_array($action['response_fields']) ? json_encode($action['response_fields'], JSON_PRETTY_PRINT) : $action['response_fields']) : '' }}</textarea>
            </div>
        </div>
    @endforeach
</div>
<button type="button" class="btn btn-secondary" id="add-action">Add Action</button>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    console.log("Script loaded for QuickBooks Entity Actions");

    let actionIndex = {{ count($actions) }};
    document.getElementById('add-action').addEventListener('click', function () {
        console.log("Adding new action block");

        let html = `
        <div class="card mb-2 p-2 action-block">
            <div class="row">
                <div class="col-md-2">
                    <label>Action</label>
                    <input type="text" name="actions[\${actionIndex}][action]" class="form-control" required>
                </div>
                <div class="col-md-3">
                    <label>Handler Class</label>
                    <input type="text" name="actions[\${actionIndex}][handler_class]" class="form-control">
                </div>
                <div class="col-md-2">
                    <label>Active</label>
                    <input type="checkbox" name="actions[\${actionIndex}][active]" value="1" checked>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="button" class="btn btn-danger btn-sm remove-action">Remove</button>
                </div>
            </div>
            <div class="mb-2">
                <label>Request Template (QBXML)</label>
                <textarea name="actions[\${actionIndex}][request_template]" class="form-control" rows="3" required></textarea>
            </div>
            <div class="mb-2">
                <label>Response Fields (JSON)</label>
                <textarea name="actions[\${actionIndex}][response_fields]" class="form-control" rows="2"></textarea>
            </div>
        </div>
        `;
        console.log(html);
        document.getElementById('actions-list').insertAdjacentHTML('beforeend', html);
        actionIndex++;
    });

    document.getElementById('actions-list').addEventListener('click', function (e) {
        if (e.target.classList.contains('remove-action')) {
            e.target.closest('.action-block').remove();
        }
    });
});
</script>
@endpush
