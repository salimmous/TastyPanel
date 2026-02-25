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
            'custom_css' => $this->sanitizeCustomCss($tenant->custom_css),
            'custom_js' => $this->sanitizeCustomJs($tenant->custom_js),
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
     * Sanitize custom CSS to prevent XSS
     */
    private function sanitizeCustomCss(?string $css): ?string
    {
        if (empty($css)) {
            return $css;
        }

        // Prevent breaking out of <style> tag
        // Use 4 backslashes to get 1 backslash in output via preg_replace
        $css = preg_replace('#</style#i', '<\\\\/style', $css);

        // Prevent javascript: protocol
        $css = preg_replace('#javascript:#i', '', $css);

        // Prevent expression() (IE legacy vector)
        $css = preg_replace('#expression\(#i', 'expression_disabled(', $css);

        // Prevent @import to mitigate external CSS injection risks
        $css = preg_replace('#@import#i', '@_import', $css);

        return $css;
    }

    /**
     * Sanitize custom JS to prevent breaking out of script tag
     */
    private function sanitizeCustomJs(?string $js): ?string
    {
        if (empty($js)) {
            return $js;
        }

        // Prevent breaking out of <script> tag
        return preg_replace('#</script#i', '<\\\\/script', $js);
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
