<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EraporAuditLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'actor_id',
        'action',
        'entity_type',
        'entity_id',
        'before_state',
        'after_state',
        'reason',
        'request_id',
    ];

    protected $casts = [
        'before_state' => 'array',
        'after_state' => 'array',
    ];

    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public static function record(
        string $action,
        string $entityType,
        string $entityId,
        ?array $before = null,
        ?array $after = null,
        ?string $reason = null,
        ?int $actorId = null
    ): self {
        return self::create([
            'actor_id' => $actorId ?? auth()->id(),
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'before_state' => $before,
            'after_state' => $after,
            'reason' => $reason,
            'request_id' => 'audit_' . (string) \Illuminate\Support\Str::uuid(),
        ]);
    }
}
