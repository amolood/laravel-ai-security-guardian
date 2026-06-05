<?php

namespace Abdalmolood\AiSecurityGuardian\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $finding_id
 * @property string|null $branch_name
 * @property string|null $pull_request_url
 * @property string|null $patch_file
 * @property string|null $original_file_path
 * @property string|null $backup_path
 * @property string|null $tests_status
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read SecurityFinding $finding
 */
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
