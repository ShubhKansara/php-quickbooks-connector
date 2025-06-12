{{-- filepath: c:\xampp\htdocs\b2b_cf_backend\packages\ShubhKansara\php-quickbooks-connector\resources\views\qb-entities\form.blade.php --}}
@csrf
<div class="mb-6">
    <label for="name" class="block font-semibold mb-1">Entity Name</label>
    <input type="text" name="name" id="name" class="w-full border rounded px-3 py-2" value="{{ old('name', $qbEntity->name ?? '') }}" required>
</div>
<div class="mb-6 flex items-center space-x-2">
    <input type="checkbox" name="active" id="active" value="1" {{ old('active', $qbEntity->active ?? true) ? 'checked' : '' }}>
    <label for="active" class="font-semibold">Active</label>
</div>

<hr class="my-6">
<h3 class="text-lg font-bold mb-2">Actions</h3>
<div id="actions-list" class="space-y-4">
    @php
        $actions = old('actions', isset($qbEntity) ? $qbEntity->actions->toArray() : [ ['action'=>'sync'] ]);
    @endphp
    @foreach($actions as $i => $action)
        <div class="bg-white rounded shadow p-4 action-block relative">
            <input type="hidden" name="actions[{{ $i }}][id]" value="{{ $action['id'] ?? '' }}">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-2">
                <div>
                    <label class="block font-semibold mb-1">Action</label>
                    <input type="text" name="actions[{{ $i }}][action]" class="w-full border rounded px-2 py-1" value="{{ $action['action'] ?? '' }}" required>
                </div>
                <div>
                    <label class="block font-semibold mb-1">Handler Class</label>
                    <input type="text" name="actions[{{ $i }}][handler_class]" class="w-full border rounded px-2 py-1" value="{{ $action['handler_class'] ?? '' }}">
                </div>
                <div class="flex items-center space-x-2 mt-6 md:mt-0">
                    <input type="checkbox" name="actions[{{ $i }}][active]" value="1" {{ ($action['active'] ?? true) ? 'checked' : '' }}>
                    <label class="font-semibold">Active</label>
                </div>
                <div class="flex items-center justify-end mt-6 md:mt-0">
                    <button type="button" class="remove-action text-red-600 hover:text-red-800 font-bold">Remove</button>
                </div>
            </div>
            <div class="mb-2">
                <label class="block font-semibold mb-1">Request Template (QBXML)</label>
                <textarea name="actions[{{ $i }}][request_template]" class="w-full border rounded px-2 py-1" rows="3" required>{{ $action['request_template'] ?? '' }}</textarea>
            </div>
            <div>
                <label class="block font-semibold mb-1">Response Fields (JSON)</label>
                <textarea name="actions[{{ $i }}][response_fields]" class="w-full border rounded px-2 py-1" rows="2">{{ isset($action['response_fields']) ? (is_array($action['response_fields']) ? json_encode($action['response_fields'], JSON_PRETTY_PRINT) : $action['response_fields']) : '' }}</textarea>
            </div>
        </div>
    @endforeach
</div>
<button type="button" class="mt-4 bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700" id="add-action">Add Action</button>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    let actionIndex = {{ count($actions) }};
    document.getElementById('add-action').addEventListener('click', function () {
        let html = `
        <div class="bg-white rounded shadow p-4 action-block relative mt-4">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-2">
                <div>
                    <label class="block font-semibold mb-1">Action</label>
                    <input type="text" name="actions[\${actionIndex}][action]" class="w-full border rounded px-2 py-1" required>
                </div>
                <div>
                    <label class="block font-semibold mb-1">Handler Class</label>
                    <input type="text" name="actions[\${actionIndex}][handler_class]" class="w-full border rounded px-2 py-1">
                </div>
                <div class="flex items-center space-x-2 mt-6 md:mt-0">
                    <input type="checkbox" name="actions[\${actionIndex}][active]" value="1" checked>
                    <label class="font-semibold">Active</label>
                </div>
                <div class="flex items-center justify-end mt-6 md:mt-0">
                    <button type="button" class="remove-action text-red-600 hover:text-red-800 font-bold">Remove</button>
                </div>
            </div>
            <div class="mb-2">
                <label class="block font-semibold mb-1">Request Template (QBXML)</label>
                <textarea name="actions[\${actionIndex}][request_template]" class="w-full border rounded px-2 py-1" rows="3" required></textarea>
            </div>
            <div>
                <label class="block font-semibold mb-1">Response Fields (JSON)</label>
                <textarea name="actions[\${actionIndex}][response_fields]" class="w-full border rounded px-2 py-1" rows="2"></textarea>
            </div>
        </div>
        `;
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
