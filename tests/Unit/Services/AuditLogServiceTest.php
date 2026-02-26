<?php

namespace Tests\Unit\Services;

use App\Models\AuditLog;
use App\Models\User;
use App\Models\Tenant;
use App\Services\AuditLogService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class AuditLogServiceTest extends TestCase
{
    use RefreshDatabase;

    protected AuditLogService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new AuditLogService();
    }

    public function test_log_deleted_creates_audit_log_entry()
    {
        // Arrange
        // Create a user without a tenant first to avoid potential foreign key issues
        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'tenant_id' => null,
        ]);

        // Act
        $log = $this->service->logDeleted($user);

        // Assert
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'delete',
            'resource_type' => User::class,
            'resource_id' => $user->id,
            'description' => "Deleted User: Test User",
        ]);

        $this->assertInstanceOf(AuditLog::class, $log);
        $this->assertEquals('delete', $log->action);
        $this->assertEquals($user->getAttributes(), $log->old_values);
        $this->assertNull($log->new_values);
    }

    public function test_log_deleted_captures_user_and_request_info()
    {
        // Arrange
        // Create a tenant to satisfy foreign key constraints for the actor
        $tenant = Tenant::create([
            'name' => 'Test Tenant',
            'slug' => 'test-tenant-' . uniqid()
        ]);

        $actor = User::factory()->create(['tenant_id' => $tenant->id]);
        $this->actingAs($actor);

        // Mock request via server vars
        $this->withServerVariables([
            'REMOTE_ADDR' => '192.168.1.1',
            'HTTP_USER_AGENT' => 'TestAgent/1.0',
        ]);

        $target = User::factory()->create(['name' => 'Target User', 'tenant_id' => null]);

        // Act
        // Ensure request properties are set for the service call
        request()->server->set('REMOTE_ADDR', '192.168.1.1');
        request()->headers->set('User-Agent', 'TestAgent/1.0');

        $log = $this->service->logDeleted($target);

        // Assert
        $this->assertEquals($actor->id, $log->user_id);
        $this->assertEquals($tenant->id, $log->tenant_id);
        $this->assertEquals('192.168.1.1', $log->ip_address);
        $this->assertEquals('TestAgent/1.0', $log->user_agent);
    }

    public function test_log_deleted_uses_correct_resource_name_logic()
    {
        // 1. Model with getAuditName()
        $modelWithAuditName = new class extends Model {
            public function getAuditName(): string { return 'Custom Audit Name'; }
        };
        $description1 = $this->invokeMethod($this->service, 'getResourceName', [$modelWithAuditName]);
        $this->assertEquals('Custom Audit Name', $description1);

        // 2. Model with title
        $modelWithTitle = new class extends Model {
            public $title = 'Some Title';
        };
        $description2 = $this->invokeMethod($this->service, 'getResourceName', [$modelWithTitle]);
        $this->assertStringContainsString('Some Title', $description2);

        // 3. Model with name
        $modelWithName = new class extends Model {
            public $name = 'Some Name';
        };
        $description3 = $this->invokeMethod($this->service, 'getResourceName', [$modelWithName]);
        $this->assertStringContainsString('Some Name', $description3);

        // 4. Model with only ID
        $modelWithId = new class extends Model {
            public $id = 999;
        };
        $description4 = $this->invokeMethod($this->service, 'getResourceName', [$modelWithId]);
        $this->assertStringContainsString('#999', $description4);
    }

    // Helper to access protected methods
    protected function invokeMethod($object, $methodName, array $parameters = [])
    {
        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);
        return $method->invokeArgs($object, $parameters);
    }
}
