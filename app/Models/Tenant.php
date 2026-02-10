<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tenant extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'theme_id',
        'staging_theme_id',
        'preview_theme_id',
        'status',
        'mail_driver',
        'mail_host',
        'mail_port',
        'mail_username',
        'mail_password',
        'mail_encryption',
        'mail_from_address',
        'mail_from_name',
        'mail_configured',
        'mail_local_enabled',
        'mail_provider',
        'mail_daily_limit',
        'mail_per_minute_limit',
        'staging_enabled',
        'preview_enabled',
        'instance_key',
        'instance_root',
        'instance_public_root',
        'instance_php_socket',
        'instance_db_name',
        'instance_db_user',
        'instance_db_password',
        'instance_system_user',
        'instance_ssh_user',
        'instance_ssh_home',
        'instance_ssh_port',
        'instance_status',
        'instance_last_error',
        'instance_installed_at',
        'backup_enabled',
        'backup_interval_hours',
        'backup_retention_days',
        'backup_s3_enabled',
        'backup_keep_local',
        'backup_s3_prefix',
    ];

    protected $casts = [
        'staging_enabled' => 'boolean',
        'preview_enabled' => 'boolean',
        'mail_port' => 'integer',
        'mail_configured' => 'boolean',
        'mail_local_enabled' => 'boolean',
        'mail_daily_limit' => 'integer',
        'mail_per_minute_limit' => 'integer',
        'instance_ssh_port' => 'integer',
        'instance_installed_at' => 'datetime',
        'backup_enabled' => 'boolean',
        'backup_s3_enabled' => 'boolean',
        'backup_keep_local' => 'boolean',
    ];

    protected $hidden = [
        'instance_db_password',
        'mail_password',
    ];

    public function theme()
    {
        return $this->belongsTo(Theme::class);
    }

    public function stagingTheme()
    {
        return $this->belongsTo(Theme::class, 'staging_theme_id');
    }

    public function previewTheme()
    {
        return $this->belongsTo(Theme::class, 'preview_theme_id');
    }

    public function domains()
    {
        return $this->hasMany(Domain::class);
    }

    public function primaryDomain()
    {
        return $this->hasOne(Domain::class)->where('is_primary', true);
    }

    public function settings()
    {
        return $this->hasOne(SiteSetting::class)->where('environment', 'production');
    }

    public function stagingSettings()
    {
        return $this->hasOne(SiteSetting::class)->where('environment', 'staging');
    }

    public function previewSettings()
    {
        return $this->hasOne(SiteSetting::class)->where('environment', 'preview');
    }

    public function provisioningJobs()
    {
        return $this->hasMany(ProvisioningJob::class);
    }

    public function latestProvisioningJob()
    {
        return $this->hasOne(ProvisioningJob::class)->latestOfMany();
    }

    public function subscriptions()
    {
        return $this->hasMany(TenantSubscription::class);
    }

    public function activeSubscription()
    {
        return $this->hasOne(TenantSubscription::class)
            ->where('status', 'active')
            ->latest('started_at');
    }

    public function categories()
    {
        return $this->hasMany(Category::class);
    }

    public function recipes()
    {
        return $this->hasMany(Recipe::class);
    }

    public function articles()
    {
        return $this->hasMany(Article::class);
    }

    public function apiKeys()
    {
        return $this->hasMany(ApiKey::class);
    }

    public function webhooks()
    {
        return $this->hasMany(Webhook::class);
    }

    public function uptimeChecks()
    {
        return $this->hasMany(UptimeCheck::class);
    }

    public function backupRuns()
    {
        return $this->hasMany(TenantBackupRun::class);
    }

    public function featureFlags()
    {
        return $this->hasMany(FeatureFlag::class);
    }

    public function securityProfile()
    {
        return $this->hasOne(TenantSecurityProfile::class);
    }

    public function queueProfile()
    {
        return $this->hasOne(TenantQueueProfile::class);
    }

    public function secrets()
    {
        return $this->hasMany(TenantSecret::class);
    }

    public function mailboxes()
    {
        return $this->hasMany(TenantMailbox::class);
    }

    public function mailEvents()
    {
        return $this->hasMany(TenantMailEvent::class);
    }
    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function alertRule()
    {
        return $this->hasOne(TenantAlertRule::class);
    }
}
