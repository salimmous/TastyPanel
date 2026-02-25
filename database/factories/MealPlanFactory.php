<?php

namespace Database\Factories;

use App\Models\MealPlan;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;

class MealPlanFactory extends Factory
{
    protected $model = MealPlan::class;

    public function definition(): array
    {
        $startDate = Carbon::now()->startOfWeek();
        $endDate = Carbon::now()->endOfWeek();
        return [
            'tenant_id' => Tenant::factory(),
            'user_id' => User::factory(),
            'name' => 'Weekly Plan',
            'start_date' => $startDate,
            'end_date' => $endDate,
            'is_active' => true,
        ];
    }
}
