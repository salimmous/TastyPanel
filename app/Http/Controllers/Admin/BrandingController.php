<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PlatformSetting;

class BrandingController extends Controller
{
    public function show()
    {
        $defaults = [
            'brand_name' => 'TastyPanel',
            'brand_logo_url' => '',
            'brand_favicon_url' => '',
            'brand_primary_color' => '#2563eb',
            'brand_secondary_color' => '#111827',
            'brand_accent_color' => '#f97316',
            'brand_login_message' => 'Admin Dashboard',
        ];

        $settings = PlatformSetting::getData();

        return response()->json(array_merge($defaults, array_intersect_key($settings, $defaults)));
    }
}
