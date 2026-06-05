<?php

namespace Abdalmolood\AiSecurityGuardian\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SecurityPatch extends Model
{
    protected $table = 'security_patches';

    protected $fillable = [
        'finding_id',
        'branch_name',
        'pull_request_url',
        'patch_file',
        'original_file_path',
        'backup_path',
        'tests_status',
        'status',
    ];

    public function getConnectionName()
    {
        return config('ai-security-guardian.database.connection') ?? parent::getConnectionName();
    }

    public function finding(): BelongsTo
    {
        return $this->belongsTo(SecurityFinding::class, 'finding_id');
    }
}
