<?php

namespace Abdalmolood\AiSecurityGuardian\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $scan_id
 * @property string|null $scanner_name
 * @property string $severity
 * @property string $category
 * @property string|null $package_name
 * @property string|null $cve
 * @property string|null $advisory_url
 * @property string|null $affected_file
 * @property int|null $affected_line
 * @property string $title
 * @property string $description
 * @property string|null $business_impact
 * @property string|null $technical_impact
 * @property string|null $recommendation
 * @property string|null $test_plan
 * @property array|null $references
 * @property string $status
 * @property bool $safe_auto_fix_allowed
 * @property bool $human_review_required
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read SecurityScan $scan
 * @property-read \Illuminate\Database\Eloquent\Collection<int, SecurityPatch> $patches
 */
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
