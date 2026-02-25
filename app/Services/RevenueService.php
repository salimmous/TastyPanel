<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Subscription;
use Illuminate\Support\Facades\DB;

class RevenueService
{
    /**
     * Get revenue dashboard overview
     */
    public function getDashboard(): array
    {
        return [
            'mrr' => $this->calculateMRR(),
            'arr' => $this->calculateARR(),
            'total_revenue' => $this->getTotalRevenue(),
            'subscriptions' => $this->getSubscriptionStats(),
            'recent_invoices' => $this->getRecentInvoices(),
        ];
    }

    /**
     * Calculate Monthly Recurring Revenue
     */
    public function calculateMRR(): float
    {
        $monthlyRevenue = Subscription::active()
            ->where('plan_interval', 'monthly')
            ->sum('price');

        $yearlyAsMonthly = Subscription::active()
            ->where('plan_interval', 'yearly')
            ->get()
            ->sum(fn ($sub) => $sub->monthlyPrice());

        return round($monthlyRevenue + $yearlyAsMonthly, 2);
    }

    /**
     * Calculate Annual Recurring Revenue
     */
    public function calculateARR(): float
    {
        return round($this->calculateMRR() * 12, 2);
    }

    /**
     * Get total revenue from paid invoices
     */
    public function getTotalRevenue(?int $year = null): float
    {
        $query = Invoice::paid();

        if ($year) {
            $query->whereYear('paid_at', $year);
        }

        return $query->sum('total');
    }

    /**
     * Get subscription statistics
     */
    public function getSubscriptionStats(): array
    {
        return [
            'total' => Subscription::count(),
            'active' => Subscription::active()->count(),
            'trialing' => Subscription::trialing()->count(),
            'cancelled' => Subscription::where('status', 'cancelled')->count(),
            'past_due' => Subscription::where('status', 'past_due')->count(),
            'by_plan' => Subscription::active()
                ->select('plan_name', DB::raw('count(*) as count'))
                ->groupBy('plan_name')
                ->pluck('count', 'plan_name'),
        ];
    }

    /**
     * Get MRR chart data
     */
    public function getMRRChart(int $months = 12): array
    {
        $data = [];

        for ($i = $months - 1; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $monthKey = $date->format('Y-m');

            // Approximate MRR for that month
            $mrr = Invoice::paid()
                ->whereYear('paid_at', $date->year)
                ->whereMonth('paid_at', $date->month)
                ->sum('total');

            $data[] = [
                'month' => $monthKey,
                'label' => $date->format('M Y'),
                'mrr' => round($mrr, 2),
            ];
        }

        return $data;
    }

    /**
     * Get recent invoices
     */
    public function getRecentInvoices(int $limit = 10): array
    {
        return Invoice::with('tenant:id,name')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    /**
     * Get revenue by plan
     */
    public function getRevenueByPlan(): array
    {
        return Subscription::active()
            ->select('plan_name', DB::raw('SUM(price) as total'), DB::raw('count(*) as count'))
            ->groupBy('plan_name')
            ->get()
            ->toArray();
    }

    /**
     * Create invoice for subscription
     */
    public function createInvoice(Subscription $subscription): Invoice
    {
        return Invoice::create([
            'tenant_id' => $subscription->tenant_id,
            'subscription_id' => $subscription->id,
            'invoice_number' => Invoice::generateInvoiceNumber(),
            'status' => 'pending',
            'subtotal' => $subscription->price,
            'tax' => 0,
            'total' => $subscription->price,
            'currency' => $subscription->currency,
            'invoice_date' => now(),
            'due_date' => now()->addDays(14),
            'line_items' => [
                [
                    'description' => "{$subscription->plan_name} - {$subscription->plan_interval}",
                    'quantity' => 1,
                    'unit_price' => $subscription->price,
                    'total' => $subscription->price,
                ],
            ],
        ]);
    }
}
