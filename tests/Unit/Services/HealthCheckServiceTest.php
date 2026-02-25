<?php

namespace Tests\Unit\Services;

use App\Services\HealthCheckService;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use ReflectionMethod;

class HealthCheckServiceTest extends TestCase
{
    /**
     * Helper to access the protected getOverallStatus method.
     */
    protected function getProtectedMethod(string $methodName): ReflectionMethod
    {
        $class = new ReflectionMethod(HealthCheckService::class, $methodName);
        $class->setAccessible(true);
        return $class;
    }

    #[Test]
    public function it_returns_down_when_any_check_is_down()
    {
        $service = new HealthCheckService();
        $method = $this->getProtectedMethod('getOverallStatus');

        $checks = [
            ['status' => 'up'],
            ['status' => 'down'],
            ['status' => 'up'],
        ];

        $result = $method->invoke($service, $checks);

        $this->assertEquals('down', $result);
    }

    #[Test]
    public function it_returns_degraded_when_no_down_but_degraded_exists()
    {
        $service = new HealthCheckService();
        $method = $this->getProtectedMethod('getOverallStatus');

        $checks = [
            ['status' => 'up'],
            ['status' => 'degraded'],
            ['status' => 'up'],
        ];

        $result = $method->invoke($service, $checks);

        $this->assertEquals('degraded', $result);
    }

    #[Test]
    public function it_returns_healthy_when_all_checks_are_up()
    {
        $service = new HealthCheckService();
        $method = $this->getProtectedMethod('getOverallStatus');

        $checks = [
            ['status' => 'up'],
            ['status' => 'up'],
        ];

        $result = $method->invoke($service, $checks);

        $this->assertEquals('healthy', $result);
    }

    #[Test]
    public function it_prioritizes_down_over_degraded()
    {
        $service = new HealthCheckService();
        $method = $this->getProtectedMethod('getOverallStatus');

        $checks = [
            ['status' => 'degraded'],
            ['status' => 'down'],
        ];

        $result = $method->invoke($service, $checks);

        $this->assertEquals('down', $result);
    }

    #[Test]
    public function it_returns_healthy_for_empty_checks()
    {
        $service = new HealthCheckService();
        $method = $this->getProtectedMethod('getOverallStatus');

        $checks = [];

        $result = $method->invoke($service, $checks);

        $this->assertEquals('healthy', $result);
    }

    #[Test]
    public function it_handles_unknown_status_as_healthy()
    {
        $service = new HealthCheckService();
        $method = $this->getProtectedMethod('getOverallStatus');

        $checks = [
            ['status' => 'unknown'],
        ];

        $result = $method->invoke($service, $checks);

        $this->assertEquals('healthy', $result);
    }

    #[Test]
    public function it_ignores_checks_without_status_key()
    {
        $service = new HealthCheckService();
        $method = $this->getProtectedMethod('getOverallStatus');

        $checks = [
            ['other_key' => 'value'],
            ['status' => 'up'],
        ];

        $result = $method->invoke($service, $checks);

        $this->assertEquals('healthy', $result);
    }
}
