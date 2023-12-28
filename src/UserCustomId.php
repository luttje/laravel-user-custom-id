<?php

namespace Luttje\UserCustomId;

use Illuminate\Database\Eloquent\Model;
use Luttje\UserCustomId\Casts\FormatChunks;

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
