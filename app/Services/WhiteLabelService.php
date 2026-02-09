<?php

namespace App\Services;

use App\Models\Tenant;

class WhiteLabelService
{
    /**
     * Get branding configuration for tenant
     */
    public function getBrandingConfig(Tenant $tenant): array
    {
        return [
            'name' => $tenant->brand_name ?? $tenant->name,
            'logo' => $tenant->brand_logo,
            'favicon' => $tenant->brand_favicon ?? '/favicon.ico',
            'colors' => [
                'primary' => $tenant->brand_color,
                'secondary' => $tenant->brand_secondary_color,
            ],
            'white_label' => $tenant->white_label_enabled,
            'custom_css' => $tenant->custom_css,
            'custom_js' => $tenant->custom_js,
            'custom_footer' => $tenant->custom_footer,
        ];
    }

    /**
     * Should show "Powered by TastyPanel"
     */
    public function shouldShowPoweredBy(Tenant $tenant): bool
    {
        return !($tenant->white_label_enabled && $tenant->hide_powered_by);
    }

    /**
     * Get CSS variables for tenant branding
     */
    public function getCssVariables(Tenant $tenant): string
    {
        return <<<CSS
        :root {
            --brand-primary: {$tenant->brand_color};
            --brand-secondary: {$tenant->brand_secondary_color};
            --brand-primary-rgb: {$this->hexToRgb($tenant->brand_color)};
            --brand-secondary-rgb: {$this->hexToRgb($tenant->brand_secondary_color)};
        }
        CSS;
    }

    /**
     * Convert hex color to RGB
     */
    private function hexToRgb(string $hex): string
    {
        $hex = ltrim($hex, '#');

        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }

        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));

        return "{$r}, {$g}, {$b}";
    }

    /**
     * Validate custom CSS
     */
    public function validateCustomCss(string $css): array
    {
        $errors = [];

        // Check for dangerous patterns
        $dangerous = ['<script', 'javascript:', 'expression(', '@import'];

        foreach ($dangerous as $pattern) {
            if (stripos($css, $pattern) !== false) {
                $errors[] = "Dangerous pattern detected: {$pattern}";
            }
        }

        return $errors;
    }
}
