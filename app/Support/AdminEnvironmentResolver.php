<?php

namespace App\Support;

use Illuminate\Http\Request;

class AdminEnvironmentResolver
{
    public static function resolve(Request $request): string
    {
        $header = $request->header('X-Environment');
        $param = $request->input('environment') ?? $request->query('environment');
        $value = $header ?? $param ?? 'production';
        $value = strtolower(trim((string) $value));

        if (! in_array($value, ['production', 'staging', 'preview'], true)) {
            return 'production';
        }

        return $value;
    }
}
