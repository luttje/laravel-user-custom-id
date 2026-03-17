<?php

namespace Luttje\UserCustomId;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Luttje\UserCustomId\Casts\FormatChunkCollectionCast;
use Luttje\UserCustomId\FormatChunks\FormatChunkCollection;

/**
 * @property string $id
 * @property string $owner_id
 * @property string $owner_type
 * @property string $target_type
 * @property string $target_attribute
 * @property string $format
 * @property FormatChunkCollection $last_target_custom_id
 * @property Carbon $created_at
 * @property Carbon $updated_at
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
        'last_target_custom_id' => FormatChunkCollectionCast::class,
    ];

    public function owner()
    {
        return $this->morphTo();
    }
}
