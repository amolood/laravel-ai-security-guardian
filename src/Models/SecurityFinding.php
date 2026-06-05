<?php

namespace Abdalmolood\AiSecurityGuardian\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SecurityFinding extends Model
{
    protected $table = 'security_findings';

    protected $fillable = [
        'scan_id',
        'scanner_name',
        'severity',
        'category',
        'package_name',
        'cve',
        'advisory_url',
        'affected_file',
        'affected_line',
        'title',
        'description',
        'business_impact',
        'technical_impact',
        'recommendation',
        'test_plan',
        'references',
        'status',
        'safe_auto_fix_allowed',
        'human_review_required',
    ];

    protected $casts = [
        'safe_auto_fix_allowed' => 'boolean',
        'human_review_required' => 'boolean',
        'references' => 'array',
    ];

    public function getConnectionName()
    {
        return config('ai-security-guardian.database.connection') ?? parent::getConnectionName();
    }

    public function scan(): BelongsTo
    {
        return $this->belongsTo(SecurityScan::class, 'scan_id');
    }

    public function patches(): HasMany
    {
        return $this->hasMany(SecurityPatch::class, 'finding_id');
    }
}
