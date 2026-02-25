<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AutomationRule;
use App\Services\PlatformAutomationService;
use Illuminate\Http\Request;

class AutomationRulesController extends Controller
{
    public function __construct(
        protected PlatformAutomationService $automation
    ) {}

    /**
     * List all automation rules
     */
    public function index(Request $request)
    {
        $query = AutomationRule::query();

        if ($type = $request->get('type')) {
            $query->byType($type);
        }

        if ($request->boolean('active_only')) {
            $query->active();
        }

        $rules = $query->orderBy('priority', 'desc')
            ->orderBy('name')
            ->paginate($request->get('per_page', 20));

        return response()->json($rules);
    }

    /**
     * Get single rule
     */
    public function show(AutomationRule $rule)
    {
        return response()->json([
            'data' => $rule,
        ]);
    }

    /**
     * Create new rule
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'type' => ['required', 'string', 'in:maintenance,cleanup,alert,action'],
            'trigger_type' => ['required', 'string', 'in:schedule,event,condition'],
            'trigger_config' => ['nullable', 'array'],
            'conditions' => ['nullable', 'array'],
            'actions' => ['required', 'array', 'min:1'],
            'is_active' => ['boolean'],
            'priority' => ['integer', 'min:0', 'max:100'],
        ]);

        $rule = AutomationRule::create($validated);

        return response()->json([
            'data' => $rule,
            'message' => 'Automation rule created',
        ], 201);
    }

    /**
     * Update rule
     */
    public function update(Request $request, AutomationRule $rule)
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'type' => ['sometimes', 'string', 'in:maintenance,cleanup,alert,action'],
            'trigger_type' => ['sometimes', 'string', 'in:schedule,event,condition'],
            'trigger_config' => ['nullable', 'array'],
            'conditions' => ['nullable', 'array'],
            'actions' => ['sometimes', 'array', 'min:1'],
            'is_active' => ['boolean'],
            'priority' => ['integer', 'min:0', 'max:100'],
        ]);

        $rule->update($validated);

        return response()->json([
            'data' => $rule->fresh(),
            'message' => 'Automation rule updated',
        ]);
    }

    /**
     * Delete rule
     */
    public function destroy(AutomationRule $rule)
    {
        $rule->delete();

        return response()->json([
            'message' => 'Automation rule deleted',
        ]);
    }

    /**
     * Toggle rule active status
     */
    public function toggle(AutomationRule $rule)
    {
        $rule->is_active = ! $rule->is_active;
        $rule->save();

        return response()->json([
            'data' => $rule,
            'message' => $rule->is_active ? 'Rule activated' : 'Rule deactivated',
        ]);
    }

    /**
     * Run a specific rule manually
     */
    public function run(AutomationRule $rule)
    {
        $result = $this->automation->executeRule($rule);

        return response()->json([
            'data' => $result,
            'rule' => $rule->fresh(),
        ]);
    }

    /**
     * Run all due scheduled rules
     */
    public function runScheduled()
    {
        $results = $this->automation->runScheduledRules();

        return response()->json([
            'data' => $results,
            'message' => 'Scheduled rules executed: '.count($results),
        ]);
    }

    /**
     * Get available actions
     */
    public function availableActions()
    {
        return response()->json([
            'data' => [
                'types' => AutomationRule::getTypes(),
                'trigger_types' => AutomationRule::getTriggerTypes(),
                'actions' => PlatformAutomationService::getAvailableActions(),
            ],
        ]);
    }

    /**
     * Get rule execution logs
     */
    public function logs(AutomationRule $rule)
    {
        return response()->json([
            'data' => [
                'run_count' => $rule->run_count,
                'success_count' => $rule->success_count,
                'failure_count' => $rule->failure_count,
                'last_run_at' => $rule->last_run_at,
                'last_run_status' => $rule->last_run_status,
                'last_run_output' => $rule->last_run_output,
            ],
        ]);
    }
}
