<?php

namespace ShubhKansara\PhpQuickbooksConnector\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use ShubhKansara\PhpQuickbooksConnector\Models\QbEntity;
use ShubhKansara\PhpQuickbooksConnector\Models\QbEntityAction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
            $request->validate([
                'name' => 'required|unique:qb_entities,name',
                'actions' => 'required|array|min:1',
                'actions.*.action' => 'required|string',
                'actions.*.request_template' => 'required|string',
                'actions.*.response_fields' => 'nullable|json',
                'actions.*.handler_class' => 'nullable|string',
            ]);

            DB::beginTransaction();

            $entity = QbEntity::create([
                'name' => $request->name,
                'active' => $request->input('active', true),
            ]);

            foreach ($request->actions as $actionData) {
                QbEntityAction::create([
                    'qb_entity_id' => $entity->id,
                    'action' => $actionData['action'],
                    'request_template' => $actionData['request_template'],
                    'response_fields' => isset($actionData['response_fields']) ? json_decode($actionData['response_fields'], true) : null,
                    'handler_class' => $actionData['handler_class'] ?? null,
                    'active' => isset($actionData['active']) ? (bool)$actionData['active'] : true,
                ]);
            }

            DB::commit();
            return redirect()->route('qb-entities.index')->with('success', 'Entity and actions added!');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->validator)->withInput();
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Error creating QB Entity: ' . $e->getMessage(), ['exception' => $e]);
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

            $request->validate([
                'name' => 'required|unique:qb_entities,name,' . $qbEntity->id,
                'actions' => 'required|array|min:1',
                'actions.*.id' => 'nullable|integer',
                'actions.*.action' => 'required|string',
                'actions.*.request_template' => 'required|string',
                'actions.*.response_fields' => 'nullable|json',
                'actions.*.handler_class' => 'nullable|string',
            ]);

            DB::beginTransaction();

            $qbEntity->update([
                'name' => $request->name,
                'active' => $request->input('active', true),
            ]);

            $existingActionIds = $qbEntity->actions->pluck('id')->toArray();
            $submittedActionIds = collect($request->actions)->pluck('id')->filter()->map(fn($id) => (int)$id)->toArray();

            // Delete removed actions
            $toDelete = array_diff($existingActionIds, $submittedActionIds);
            if (!empty($toDelete)) {
                QbEntityAction::whereIn('id', $toDelete)->delete();
            }

            // Update or create actions
            foreach ($request->actions as $actionData) {
                if (!empty($actionData['id'])) {
                    $action = QbEntityAction::where('qb_entity_id', $qbEntity->id)->where('id', $actionData['id'])->first();
                    if ($action) {
                        $action->update([
                            'action' => $actionData['action'],
                            'request_template' => $actionData['request_template'],
                            'response_fields' => isset($actionData['response_fields']) ? json_decode($actionData['response_fields'], true) : null,
                            'handler_class' => $actionData['handler_class'] ?? null,
                            'active' => isset($actionData['active']) ? (bool)$actionData['active'] : true,
                        ]);
                    }
                } else {
                    QbEntityAction::create([
                        'qb_entity_id' => $qbEntity->id,
                        'action' => $actionData['action'],
                        'request_template' => $actionData['request_template'],
                        'response_fields' => isset($actionData['response_fields']) ? json_decode($actionData['response_fields'], true) : null,
                        'handler_class' => $actionData['handler_class'] ?? null,
                        'active' => isset($actionData['active']) ? (bool)$actionData['active'] : true,
                    ]);
                }
            }

            DB::commit();
            return redirect()->route('qb-entities.index')->with('success', 'Entity and actions updated!');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->validator)->withInput();
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Error updating QB Entity: ' . $e->getMessage(), ['exception' => $e]);
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
            Log::error('Error deleting QB Entity: ' . $e->getMessage(), ['exception' => $e]);
            return back()->with('error', 'An error occurred while deleting the entity.');
        }
    }
}
