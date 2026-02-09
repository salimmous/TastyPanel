<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Domain extends Model
{
    protected $fillable = [
        'tenant_id',
        'hostname',
        'environment',
        'is_primary',
        'status',
        'nginx_status',
        'nginx_error',
        'nginx_custom_config',
        'http3_enabled',
        'http3_status',
        'http3_error',
        'http3_checked_at',
        'http3_udp_status',
        'http3_udp_error',
        'http3_udp_checked_at',
        'nginx_updated_at',
        'cf_zone_id',
        'cf_record_id',
        'last_error',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'http3_enabled' => 'boolean',
        'http3_checked_at' => 'datetime',
        'http3_udp_checked_at' => 'datetime',
        'nginx_updated_at' => 'datetime',
    ];

    protected $hidden = [
        'nginx_custom_config',
    ];

    protected $appends = [
        'has_custom_nginx',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function sslCertificate()
    {
        return $this->hasOne(SslCertificate::class);
    }

    public function nginxVersions()
    {
        return $this->hasMany(DomainNginxVersion::class);
    }

    public function getHasCustomNginxAttribute(): bool
    {
        return !empty($this->nginx_custom_config);
    }
    public function getIsActiveAttribute(): bool
    {
        return $this->status === 'active';
    }
}
