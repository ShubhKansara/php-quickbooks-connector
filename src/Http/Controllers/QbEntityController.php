<?php

namespace ShubhKansara\PhpQuickbooksConnector\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
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
        $request->validate([
            'name' => 'required|unique:qb_entities,name',
            'actions' => 'required|array',
            'actions.*.action' => 'required|string',
            'actions.*.request_template' => 'required|string',
            'actions.*.response_fields' => 'nullable|json',
            'actions.*.handler_class' => 'nullable|string',
        ]);

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
                'active' => $actionData['active'] ?? true,
            ]);
        }

        return redirect()->route('qb-entities.index')->with('success', 'Entity and actions added!');
    }

    public function edit($id)
    {
        $qbEntity = QbEntity::with('actions')->findOrFail($id);
        return view('php-quickbooks::qb-entities.edit', compact('qbEntity'));
    }

    public function update(Request $request, QbEntity $qbEntity)
    {
        $request->validate([
            'name' => 'required|unique:qb_entities,name,' . $qbEntity->id,
            'actions' => 'required|array',
            'actions.*.id' => 'nullable|integer',
            'actions.*.action' => 'required|string',
            'actions.*.request_template' => 'required|string',
            'actions.*.response_fields' => 'nullable|json',
            'actions.*.handler_class' => 'nullable|string',
        ]);

        $qbEntity->update([
            'name' => $request->name,
            'active' => $request->input('active', true),
        ]);

        // Update or create actions
        foreach ($request->actions as $actionData) {
            if (!empty($actionData['id'])) {
                $action = QbEntityAction::find($actionData['id']);
                if ($action) {
                    $action->update([
                        'action' => $actionData['action'],
                        'request_template' => $actionData['request_template'],
                        'response_fields' => isset($actionData['response_fields']) ? json_decode($actionData['response_fields'], true) : null,
                        'handler_class' => $actionData['handler_class'] ?? null,
                        'active' => $actionData['active'] ?? true,
                    ]);
                }
            } else {
                QbEntityAction::create([
                    'qb_entity_id' => $qbEntity->id,
                    'action' => $actionData['action'],
                    'request_template' => $actionData['request_template'],
                    'response_fields' => isset($actionData['response_fields']) ? json_decode($actionData['response_fields'], true) : null,
                    'handler_class' => $actionData['handler_class'] ?? null,
                    'active' => $actionData['active'] ?? true,
                ]);
            }
        }

        return redirect()->route('qb-entities.index')->with('success', 'Entity and actions updated!');
    }

    public function destroy(QbEntity $qbEntity)
    {
        $qbEntity->actions()->delete();
        $qbEntity->delete();
        return redirect()->route('qb-entities.index')->with('success', 'Entity and its actions deleted!');
    }
}
