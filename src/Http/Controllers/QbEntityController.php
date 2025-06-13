<?php

namespace ShubhKansara\PhpQuickbooksConnector\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use ShubhKansara\PhpQuickbooksConnector\Models\QbEntity;
use ShubhKansara\PhpQuickbooksConnector\Models\QbEntityAction;

class QbEntityController extends Controller
{
    public function index()
    {
        $entities = QbEntity::with('actions')->paginate(20);

        return view('php-quickbooks::qb-entities.index', compact('entities'));
    }

    public function create()
    {
        return view('php-quickbooks::qb-entities.create');
    }

    public function store(Request $request)
    {
        try {
            $request->validate($this->entityValidationRules());

            DB::beginTransaction();

            $entity = QbEntity::create([
                'name' => $request->name,
                'active' => $request->input('active', true),
            ]);

            foreach ($request->actions as &$action) {
                if ($request->has('generate_handler')) {
                    $handlerClass = $this->generateHandlerClass($entity->name, $action['action']);
                    $action['handler_class'] = $handlerClass;
                }
            }

            $this->upsertActions($entity, $request->actions);

            DB::commit();

            return redirect()->route('qb-entities.index')->with('success', 'Entity and actions added!');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->validator)->withInput();
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Error creating QB Entity: '.$e->getMessage(), ['exception' => $e]);

            return back()->with('error', 'An error occurred while creating the entity.')->withInput();
        }
    }

    public function edit($id)
    {
        $qbEntity = QbEntity::with('actions')->findOrFail($id);

        return view('php-quickbooks::qb-entities.edit', compact('qbEntity'));
    }

    public function update(Request $request, $id)
    {
        try {
            $qbEntity = QbEntity::with('actions')->findOrFail($id);

            $request->validate($this->entityValidationRules($qbEntity->id));

            DB::beginTransaction();

            $qbEntity->update([
                'name' => $request->name,
                'active' => $request->input('active', true),
            ]);

            $this->upsertActions($qbEntity, $request->actions);

            DB::commit();

            return redirect()->route('qb-entities.index')->with('success', 'Entity and actions updated!');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->validator)->withInput();
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Error updating QB Entity: '.$e->getMessage(), ['exception' => $e]);

            return back()->with('error', 'An error occurred while updating the entity.')->withInput();
        }
    }

    public function destroy($id)
    {
        try {
            $qbEntity = QbEntity::with('actions')->findOrFail($id);
            DB::beginTransaction();
            $qbEntity->actions()->delete();
            $qbEntity->delete();
            DB::commit();

            return redirect()->route('qb-entities.index')->with('success', 'Entity and its actions deleted!');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Error deleting QB Entity: '.$e->getMessage(), ['exception' => $e]);

            return back()->with('error', 'An error occurred while deleting the entity.');
        }
    }

    private function upsertActions($qbEntity, $actions)
    {
        $existingActionIds = $qbEntity->actions->pluck('id')->toArray();
        $submittedActionIds = collect($actions)->pluck('id')->filter()->map(fn ($id) => (int) $id)->toArray();

        // Delete removed actions
        $toDelete = array_diff($existingActionIds, $submittedActionIds);
        if (! empty($toDelete)) {
            QbEntityAction::whereIn('id', $toDelete)->delete();
        }

        // Update or create actions
        foreach ($actions as $actionData) {
            $fields = [
                'action' => $actionData['action'],
                'request_template' => $actionData['request_template'],
                'response_fields' => isset($actionData['response_fields']) ? json_decode($actionData['response_fields'], true) : null,
                'handler_class' => $actionData['handler_class'] ?? null,
                'active' => isset($actionData['active']) ? (bool) $actionData['active'] : true,
            ];
            if (! empty($actionData['id'])) {
                QbEntityAction::where('qb_entity_id', $qbEntity->id)
                    ->where('id', $actionData['id'])
                    ->update($fields);
            } else {
                QbEntityAction::create(array_merge(['qb_entity_id' => $qbEntity->id], $fields));
            }
        }
    }

    private function entityValidationRules($entityId = null)
    {
        return [
            'name' => 'required|unique:qb_entities,name'.($entityId ? ','.$entityId : ''),
            'actions' => 'required|array|min:1',
            'actions.*.id' => 'nullable|integer',
            'actions.*.action' => 'required|string',
            'actions.*.request_template' => 'required|string',
            'actions.*.response_fields' => 'nullable|json',
            'actions.*.handler_class' => 'nullable|string',
        ];
    }

    private function generateHandlerClass($entityName, $action)
    {
        $className = ucfirst($action).ucfirst($entityName).'Handler';
        $namespace = 'App\\QuickBooks\\Handlers';
        $fullClass = $namespace.'\\'.$className;
        $path = app_path("QuickBooks/Handlers/{$className}.php");

        if (! file_exists(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }

        if (! file_exists($path)) {
            $stub = <<<PHP
<?php

namespace $namespace;

use Illuminate\Support\Facades\Log;

class $className
{
    /**
     * Called before sending the request to QuickBooks.
     * Modify or enrich the payload as needed.
     */
    public function beforeSend(array \$payload): array
    {
        Log::info('$className beforeSend called', ['payload' => \$payload]);
        // TODO: Modify payload if needed.
        return \$payload;
    }

    /**
     * Called after receiving the response from QuickBooks.
     * Handle the response and update your local records.
     */
    public function afterReceive(array \$response)
    {
        Log::info('$className afterReceive called', ['response' => \$response]);
        // TODO: Implement your sync logic here.
    }
}
PHP;
            file_put_contents($path, $stub);
        }

        return "$namespace\\$className";
    }
}
