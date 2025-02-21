<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Instance extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'domain',
        'name',
        'api_key',
        'software',
        'software_version',
        'total_users',
        'admin_email',
        'status',
        'settings',
        'metadata',
    ];

    protected $casts = [
        'settings' => 'array',
        'metadata' => 'array',
        'verified_at' => 'datetime',
        'last_seen_at' => 'datetime',
        'suspended_at' => 'datetime',
        'total_users' => 'integer',
        'reports_count' => 'integer',
        'threats_detected' => 'integer',
    ];

    protected $attributes = [
        'settings' => '{"reporting_threshold":3,"auto_block":false,"notify_on_high_risk":true,"allowed_reporters":["admin","moderator"]}',
        'metadata' => '{"registration_policy":"open","server_stats":{"storage":0,"media_attachments":0,"status_count":0}}'
    ];

    // Relationships
    public function reports(): HasMany
    {
        return $this->hasMany(Report::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(InstanceActivity::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeVerified($query)
    {
        return $query->whereNotNull('verified_at');
    }

    public function scopeRecentlyActive($query, $days = 7)
    {
        return $query->where('last_seen_at', '>=', now()->subDays($days));
    }

    // Methods
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isVerified(): bool
    {
        return !is_null($this->verified_at);
    }

    public function isSuspended(): bool
    {
        return $this->status === 'suspended';
    }

    public function suspend(string $reason): void
    {
        $this->update([
            'status' => 'suspended',
            'suspended_at' => now(),
            'suspension_reason' => $reason
        ]);

        $this->logActivity('suspended', ['reason' => $reason]);
    }

    public function activate(): void
    {
        if ($this->status === 'pending' && !$this->isVerified()) {
            $this->verified_at = now();
        }

        $this->update([
            'status' => 'active',
            'last_seen_at' => now()
        ]);

        $this->logActivity('activated');
    }

    public function updateLastSeen(): void
    {
        $this->update(['last_seen_at' => now()]);
    }

    public function incrementReportsCount(): void
    {
        $this->increment('reports_count');
    }

    public function incrementThreatsDetected(): void
    {
        $this->increment('threats_detected');
    }

    protected function logActivity(string $action, array $details = []): void
    {
        $this->activities()->create([
            'action' => $action,
            'details' => $details
        ]);
    }

    // Settings helpers
    public function getReportingThreshold(): int
    {
        return $this->settings['reporting_threshold'] ?? 3;
    }

    public function getAutoBlock(): bool
    {
        return $this->settings['auto_block'] ?? false;
    }

    public function getAllowedReporters(): array
    {
        return $this->settings['allowed_reporters'] ?? ['admin', 'moderator'];
    }

    public function setSettings(array $settings): void
    {
        $this->settings = array_merge($this->settings ?? [], $settings);
        $this->save();
    }
}
