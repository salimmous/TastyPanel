<?php

namespace Tests\Unit\Services;

use App\Models\Tenant;
use App\Services\WhiteLabelService;
use PHPUnit\Framework\TestCase;

class WhiteLabelServiceTest extends TestCase
{
    public function test_it_sanitizes_custom_css_to_prevent_xss()
    {
        $service = new WhiteLabelService();
        $tenant = new Tenant();
        $tenant->custom_css = 'body { color: red; } </style><script>alert("xss")</script>';
        $tenant->custom_js = 'console.log("hello");';

        $config = $service->getBrandingConfig($tenant);

        $this->assertStringNotContainsString('</style>', $config['custom_css'], 'Custom CSS should not contain closing style tag');
        $this->assertStringContainsString('<\/style', $config['custom_css'], 'Custom CSS should contain escaped style tag');
        $this->assertStringContainsString('body { color: red; }', $config['custom_css']);
    }

    public function test_it_sanitizes_custom_js_to_prevent_breaking_out_of_script_tag()
    {
        $service = new WhiteLabelService();
        $tenant = new Tenant();
        $tenant->custom_css = 'body { color: blue; }';
        $tenant->custom_js = 'console.log("hello"); </script><script>alert("xss")</script>';

        $config = $service->getBrandingConfig($tenant);

        $this->assertStringNotContainsString('</script>', $config['custom_js'], 'Custom JS should not contain closing script tag');
        $this->assertStringContainsString('<\/script', $config['custom_js'], 'Custom JS should contain escaped script tag');
        $this->assertStringContainsString('console.log("hello");', $config['custom_js']);
    }

    public function test_it_removes_dangerous_css_patterns()
    {
        $service = new WhiteLabelService();
        $tenant = new Tenant();
        $tenant->custom_css = 'background-image: url("javascript:alert(1)"); behavior: url(x.htc);';
        $tenant->custom_js = '';

        $config = $service->getBrandingConfig($tenant);

        $this->assertStringNotContainsString('javascript:', $config['custom_css']);
        $this->assertStringNotContainsString('expression', $config['custom_css']); // Just in case
    }
}
