<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\HealthCheckService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Mockery;
use Exception;
use PHPUnit\Framework\Attributes\Test;

class HealthCheckServiceTest extends TestCase
{
    protected HealthCheckService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new HealthCheckService();
    }

    #[Test]
    public function check_database_returns_up_status_when_connection_is_successful()
    {
        // Mock DB connection success
        $connectionMock = Mockery::mock('Illuminate\Database\Connection');
        $connectionMock->shouldReceive('getPdo')->once()->andReturn(new \stdClass());

        DB::shouldReceive('connection')
            ->once()
            ->andReturn($connectionMock);

        $result = $this->service->checkDatabase();

        $this->assertEquals('up', $result['status']);
        $this->assertEquals('Database is accessible', $result['message']);
        $this->assertStringContainsString('ms', $result['response_time']);
    }

    #[Test]
    public function check_database_returns_down_status_when_connection_fails()
    {
        // Mock DB connection success but PDO failure
        $connectionMock = Mockery::mock('Illuminate\Database\Connection');
        $connectionMock->shouldReceive('getPdo')->once()->andThrow(new Exception('Connection refused'));

        DB::shouldReceive('connection')
            ->once()
            ->andReturn($connectionMock);

        $result = $this->service->checkDatabase();

        $this->assertEquals('down', $result['status']);
        $this->assertStringContainsString('Database connection failed: Connection refused', $result['message']);
    }

    #[Test]
    public function check_redis_returns_up_status_when_ping_is_successful()
    {
        // Mock Redis ping success
        Redis::shouldReceive('ping')
            ->once()
            ->andReturn(true);

        $result = $this->service->checkRedis();

        $this->assertEquals('up', $result['status']);
        $this->assertEquals('Redis is accessible', $result['message']);
        $this->assertStringContainsString('ms', $result['response_time']);
    }

    #[Test]
    public function check_redis_returns_down_status_when_ping_fails()
    {
        // Mock Redis ping failure
        Redis::shouldReceive('ping')
            ->once()
            ->andThrow(new Exception('Redis unavailable'));

        $result = $this->service->checkRedis();

        $this->assertEquals('down', $result['status']);
        $this->assertStringContainsString('Redis connection failed: Redis unavailable', $result['message']);
    }

    #[Test]
    public function check_queue_returns_up_status_when_jobs_are_low()
    {
        // Mock the query builder
        $builderMock = Mockery::mock('Illuminate\Database\Query\Builder');

        // When checking 'jobs' table
        DB::shouldReceive('table')
            ->with('jobs')
            ->once()
            ->andReturn($builderMock);

        // When checking 'failed_jobs' table
        DB::shouldReceive('table')
            ->with('failed_jobs')
            ->once()
            ->andReturn($builderMock);

        // The builder should receive count() twice
        $builderMock->shouldReceive('count')
            ->twice()
            ->andReturn(50, 0); // First call returns 50, second returns 0

        $result = $this->service->checkQueue();

        $this->assertEquals('up', $result['status']);
        $this->assertEquals(50, $result['pending_jobs']);
        $this->assertEquals(0, $result['failed_jobs']);
        $this->assertEquals('Queue is healthy', $result['message']);
    }

    #[Test]
    public function check_queue_returns_degraded_status_when_jobs_are_high()
    {
        $builderMock = Mockery::mock('Illuminate\Database\Query\Builder');

        DB::shouldReceive('table')
            ->with('jobs')
            ->once()
            ->andReturn($builderMock);

        DB::shouldReceive('table')
            ->with('failed_jobs')
            ->once()
            ->andReturn($builderMock);

        $builderMock->shouldReceive('count')
            ->twice()
            ->andReturn(1500, 10); // 1500 pending, 10 failed

        $result = $this->service->checkQueue();

        $this->assertEquals('degraded', $result['status']);
        $this->assertEquals(1500, $result['pending_jobs']);
        $this->assertEquals('Queue backlog detected', $result['message']);
    }

    #[Test]
    public function check_queue_returns_down_status_when_query_fails()
    {
        // Mock DB query failure
        DB::shouldReceive('table')
            ->with('jobs')
            ->once()
            ->andThrow(new Exception('Table not found'));

        $result = $this->service->checkQueue();

        $this->assertEquals('down', $result['status']);
        $this->assertStringContainsString('Queue check failed: Table not found', $result['message']);
    }
}
