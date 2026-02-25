<?php

namespace App\Services;

class NginxSafeDeployService
{
    /**
     * Test Nginx configuration.
     */
    public function testConfig(): array
    {
        $output = [];
        $exit = 0;
        // 2>&1 redirects stderr to stdout so we capture error messages
        @exec('sudo nginx -t 2>&1', $output, $exit);

        return [
            'success' => $exit === 0,
            'message' => implode("\n", $output),
        ];
    }

    /**
     * Safely deploy Nginx configuration (Test -> Reload).
     */
    public function deploy(): array
    {
        // 1. Test Configuration
        $test = $this->testConfig();

        if (! $test['success']) {
            return [
                'success' => false,
                'message' => "Configuration test failed:\n".$test['message'],
            ];
        }

        // 2. Reload Nginx
        $output = [];
        $exit = 0;
        @exec('sudo systemctl reload nginx 2>&1', $output, $exit);

        if ($exit !== 0) {
            return [
                'success' => false,
                'message' => "Failed to reload Nginx:\n".implode("\n", $output),
            ];
        }

        return [
            'success' => true,
            'message' => 'Configuration passed and Nginx reloaded successfully.',
        ];
    }
}
