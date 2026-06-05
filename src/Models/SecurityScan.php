<?php

namespace Abdalmolood\AiSecurityGuardian\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property \Illuminate\Support\Carbon $started_at
 * @property \Illuminate\Support\Carbon|null $finished_at
 * @property string $status
 * @property int $risk_score
 * @property array|null $summary
 * @property string $provider
 * @property string $model
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, SecurityFinding> $findings
 */
class SecurityScan extends Model
{
    protected $table = 'security_scans';

    protected $fillable = [
        'started_at',
        'finished_at',
        'status',
        'risk_score',
        'summary',
        'provider',
        'model',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'summary' => 'array',
    ];

    public function getConnectionName()
    {
        return config('ai-security-guardian.database.connection') ?? parent::getConnectionName();
    }

    public function findings(): HasMany
    {
        return $this->hasMany(SecurityFinding::class, 'scan_id');
    }
}
