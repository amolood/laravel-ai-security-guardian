<?php

namespace Abdalmolood\AiSecurityGuardian\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
