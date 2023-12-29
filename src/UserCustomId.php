<?php

namespace Luttje\UserCustomId;

use Illuminate\Database\Eloquent\Model;
use Luttje\UserCustomId\Casts\FormatChunks;
use Luttje\UserCustomId\FormatChunks\FormatChunk;

/**
 * @property string $id
 * @property string $owner_id
 * @property string $owner_type
 * @property string $target_type
 * @property string $target_attribute
 * @property string $format
 * @property FormatChunk[] $last_target_custom_id
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class UserCustomId extends Model
{
    protected $fillable = [
        'owner_id',
        'owner_type',
        'target_type',
        'target_attribute',
        'format',
        'last_target_custom_id',
    ];

    protected $casts = [
        'last_target_custom_id' => FormatChunks::class,
    ];

    public function owner()
    {
        return $this->morphTo();
    }

    public function target()
    {
        return $this->morphTo();
    }

    public function scopeForOwner($query, Model $owner)
    {
        return $query->where('owner_id', $owner->getKey())
            ->where('owner_type', $owner->getMorphClass());
    }
}
