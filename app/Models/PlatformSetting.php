<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlatformSetting extends Model
{
    protected $fillable = [
        'data',
    ];

    protected $casts = [
        'data' => 'array',
    ];

    public static function getData(): array
    {
        try {
            $record = static::query()->first();
            return $record?->data ?? [];
        } catch (\Throwable $e) {
            return [];
        }
    }

    public static function updateData(array $data): self
    {
        try {
            $record = static::query()->first();
            if (!$record) {
                return static::create(['data' => $data]);
            }

            $record->data = $data;
            $record->save();

            return $record;
        } catch (\Throwable $e) {
            return new static(['data' => $data]);
        }
    }
}
