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
 * @property FormatChunkCollection|null $last_target_custom_id
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Model|null $owner
 */
class UserCustomId extends Model
{
    /**
     * Only the format is meant to come from user input.
     *
     * The other attributes decide who owns the configuration, which model
     * class it applies to and which attribute of that model gets written.
     * If those could be mass assigned, passing request data to fill() or
     * update() would let a user aim the generated id at any attribute.
     * Use UserCustomId::createFormat(...) to set them.
     */
    protected $fillable = [
        'format',
    ];

    protected $casts = [
        'last_target_custom_id' => FormatChunkCollectionCast::class,
    ];

    /**
     * Formats are written by users, so never store one that can't be
     * used to generate ids: it would break creating the target models.
     */
    protected static function booted(): void
    {
        static::saving(function (self $customId) {
            if ($customId->isDirty('format')) {
                app(UserCustomIdManager::class)->validateFormat((string) $customId->format);
            }
        });
    }

    public function owner()
    {
        return $this->morphTo();
    }
}
