<?php

namespace Luttje\UserCustomId\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Luttje\UserCustomId\FormatChunks\FormatChunk;
use Luttje\UserCustomId\FormatChunks\FormatChunkCollection;

class FormatChunkCollectionCast implements CastsAttributes
{
    /**
     * Cast the given value.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?FormatChunkCollection
    {
        if ($value === null) {
            return null;
        }

        $items = json_decode($value, true);

        $items = array_map(function (array $chunk) {
            return FormatChunk::fromArray($chunk);
        }, $items);

        return new FormatChunkCollection($items);
    }

    /**
     * Prepare the given value for storage.
     *
     * @param  FormatChunkCollection|null  $value
     * @param  array<string, mixed>  $attributes
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null) {
            return null;
        }

        return $value->toJson();
    }
}
