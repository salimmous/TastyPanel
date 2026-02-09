<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Models\Invoice;
use App\Services\RevenueService;
use Illuminate\Http\Request;

class RevenueController extends Controller
{
    public function __construct(
        protected RevenueService $revenue
    ) {
    }

    /**
     * Revenue dashboard
     */
    public function index()
    {
        $dashboard = $this->revenue->getDashboard();
        return view('platform.revenue.index', compact('dashboard'));
    }

    public function dashboard()
    {
        return response()->json([
            'data' => $this->revenue->getDashboard(),
        ]);
    }

    /**
     * MRR chart data
     */
    public function mrrChart(Request $request)
    {
        $months = (int) $request->get('months', 12);
        $months = max(3, min(24, $months));

        return response()->json([
            'data' => $this->revenue->getMRRChart($months),
        ]);
    }

    /**
     * Revenue by plan
     */
    public function byPlan()
    {
        return response()->json([
            'data' => $this->revenue->getRevenueByPlan(),
        ]);
    }

    /**
     * List subscriptions
     */
    public function subscriptions(Request $request)
    {
        $query = Subscription::with('tenant:id,name');

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        if ($plan = $request->get('plan')) {
            $query->where('plan_name', $plan);
        }

        $subscriptions = $query->orderByDesc('created_at')
            ->paginate($request->get('per_page', 20));

        return response()->json($subscriptions);
    }

    /**
     * Create subscription
     */
    public function createSubscription(Request $request)
    {
        $validated = $request->validate([
            'tenant_id' => ['required', 'exists:tenants,id'],
            'plan_name' => ['required', 'string', 'in:basic,pro,enterprise'],
            'plan_interval' => ['required', 'string', 'in:monthly,yearly'],
            'price' => ['required', 'numeric', 'min:0'],
            'currency' => ['string', 'size:3'],
            'trial_days' => ['nullable', 'integer', 'min:0'],
        ]);

        $subscription = Subscription::create([
            'tenant_id' => $validated['tenant_id'],
            'plan_name' => $validated['plan_name'],
            'plan_interval' => $validated['plan_interval'],
            'price' => $validated['price'],
            'currency' => $validated['currency'] ?? 'USD',
            'status' => $validated['trial_days'] ? 'trialing' : 'active',
            'trial_ends_at' => $validated['trial_days'] ? now()->addDays($validated['trial_days']) : null,
            'current_period_start' => now(),
            'current_period_end' => $validated['plan_interval'] === 'yearly'
                ? now()->addYear()
                : now()->addMonth(),
        ]);

        return response()->json([
            'data' => $subscription->load('tenant:id,name'),
            'message' => 'Subscription created',
        ], 201);
    }

    /**
     * Update subscription
     */
    public function updateSubscription(Request $request, Subscription $subscription)
    {
        $validated = $request->validate([
            'plan_name' => ['sometimes', 'string', 'in:basic,pro,enterprise'],
            'plan_interval' => ['sometimes', 'string', 'in:monthly,yearly'],
            'price' => ['sometimes', 'numeric', 'min:0'],
            'status' => ['sometimes', 'string', 'in:active,cancelled,past_due,trialing'],
        ]);

        $subscription->update($validated);

        return response()->json([
            'data' => $subscription->fresh()->load('tenant:id,name'),
            'message' => 'Subscription updated',
        ]);
    }

    /**
     * Cancel subscription
     */
    public function cancelSubscription(Subscription $subscription)
    {
        $subscription->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
        ]);

        return response()->json([
            'data' => $subscription->fresh(),
            'message' => 'Subscription cancelled',
        ]);
    }

    /**
     * List invoices
     */
    public function invoices(Request $request)
    {
        $query = Invoice::with('tenant:id,name');

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        if ($tenantId = $request->get('tenant_id')) {
            $query->where('tenant_id', $tenantId);
        }

        $invoices = $query->orderByDesc('invoice_date')
            ->paginate($request->get('per_page', 20));

        return response()->json($invoices);
    }

    /**
     * Mark invoice as paid
     */
    public function markInvoicePaid(Invoice $invoice)
    {
        $invoice->markAsPaid();

        return response()->json([
            'data' => $invoice->fresh(),
            'message' => 'Invoice marked as paid',
        ]);
    }
}
