<?php

namespace Database\Factories;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class TenantFactory extends Factory
{
    protected $model = Tenant::class;

    public function definition(): array
    {
        $name = $this->faker->company();
        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'status' => 'active',
            'instance_root' => '/var/www/tenants/' . Str::slug($name),
            'instance_public_root' => '/var/www/tenants/' . Str::slug($name) . '/public',
        ];
    }
}
