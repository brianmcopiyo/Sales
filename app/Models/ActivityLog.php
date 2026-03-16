<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use App\Traits\HasUuid;

class ActivityLog extends Model
{
    use HasUuid;
    protected $fillable = [
        'user_id',
        'action',
        'description',
        'model_type',
        'model_id',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function model(): MorphTo
    {
        return $this->morphTo('model');
    }

    /**
     * Log an activity for a user
     */
    public static function log(string $userId, string $action, string $description, ?string $modelType = null, ?string $modelId = null, ?array $metadata = null): self
    {
        return self::create([
            'user_id' => $userId,
            'action' => $action,
            'description' => $description,
            'model_type' => $modelType,
            'model_id' => $modelId,
            'metadata' => $metadata,
        ]);
    }
}
