<?php

namespace Tests\Unit\Services;

use App\Models\AuditLog;
use App\Services\AuditLogService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AuditLogServiceTest extends TestCase
{
    use RefreshDatabase;

    private AuditLogService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new AuditLogService();
        Carbon::setTestNow(now());
    }

    #[Test]
    public function it_retrieves_only_failed_login_attempts()
    {
        // Should be included
        AuditLog::create([
            'action' => 'login',
            'status' => 'failed',
            'created_at' => now()->subHour(),
        ]);

        // Should be excluded (successful login)
        AuditLog::create([
            'action' => 'login',
            'status' => 'success',
            'created_at' => now()->subHour(),
        ]);

        // Should be excluded (failed update action)
        AuditLog::create([
            'action' => 'update',
            'status' => 'failed',
            'created_at' => now()->subHour(),
        ]);

        $results = $this->service->getFailedLogins();

        $this->assertCount(1, $results);
        $this->assertEquals('failed', $results->first()->status);
        $this->assertEquals('login', $results->first()->action);
    }

    #[Test]
    public function it_respects_time_limit()
    {
        // Should be included (1 hour ago)
        AuditLog::create([
            'action' => 'login',
            'status' => 'failed',
            'created_at' => now()->subHour(),
        ]);

        // Should be excluded (25 hours ago, default limit is 24)
        AuditLog::create([
            'action' => 'login',
            'status' => 'failed',
            'created_at' => now()->subHours(25),
        ]);

        // Should be included if we increase the limit (48 hours ago)
        AuditLog::create([
            'action' => 'login',
            'status' => 'failed',
            'created_at' => now()->subHours(48),
        ]);

        // Default 24 hours
        $resultsDefault = $this->service->getFailedLogins();
        $this->assertCount(1, $resultsDefault);

        // Custom 50 hours
        $resultsCustom = $this->service->getFailedLogins(50);
        $this->assertCount(3, $resultsCustom);
    }

    #[Test]
    public function it_filters_by_ip_address()
    {
        // Should be included (matching IP)
        AuditLog::create([
            'action' => 'login',
            'status' => 'failed',
            'created_at' => now()->subHour(),
            'ip_address' => '127.0.0.1',
        ]);

        // Should be excluded (different IP)
        AuditLog::create([
            'action' => 'login',
            'status' => 'failed',
            'created_at' => now()->subHour(),
            'ip_address' => '192.168.1.1',
        ]);

        // No IP filter
        $resultsNoFilter = $this->service->getFailedLogins();
        $this->assertCount(2, $resultsNoFilter);

        // Filter by 127.0.0.1
        $resultsFilter = $this->service->getFailedLogins(24, '127.0.0.1');
        $this->assertCount(1, $resultsFilter);
        $this->assertEquals('127.0.0.1', $resultsFilter->first()->ip_address);
    }

    #[Test]
    public function it_orders_results_by_created_at_descending()
    {
        $log1 = AuditLog::create([
            'action' => 'login',
            'status' => 'failed',
            'created_at' => now()->subHours(3),
        ]);

        $log2 = AuditLog::create([
            'action' => 'login',
            'status' => 'failed',
            'created_at' => now()->subHours(1),
        ]);

        $log3 = AuditLog::create([
            'action' => 'login',
            'status' => 'failed',
            'created_at' => now()->subHours(2),
        ]);

        $results = $this->service->getFailedLogins();

        $this->assertCount(3, $results);
        $this->assertTrue($results[0]->is($log2)); // Newest first
        $this->assertTrue($results[1]->is($log3));
        $this->assertTrue($results[2]->is($log1)); // Oldest last
    }
}
