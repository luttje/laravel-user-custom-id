<?php

namespace Luttje\UserCustomId\Facades;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Facade;
use Luttje\UserCustomId\FormatChunks\FormatChunk;

/**
 * @method static ?UserCustomId get(Model $target, Model $owner) Get the custom id for the given target.
 * @method static string getId(Model $target, Model $owner) Get the custom id for the given target.
 * @method static string getFormat(Model|string $targetOrClass, Model $owner) Get the custom id format for the given target.
 * @method static string generateFor(Model|string $targetOrClass, Model $owner) Generate a new custom id for the given target based on the format of this owner.
 * @method static FormatChunk[] generate(string $format, ?FormatChunkCollection $lastValueChunks = null) Generate a custom id based on the given format and possibly last value.
 * @method static string convertToString(FormatChunk[] $generated) Convert the given chunks to a string.
 * @method static ?string getChunkType(string $id) Get the chunk type for the given chunk id.
 * @method static void registerChunkType(string $chunkType) Register a new chunk type.
 *
 * @see \Luttje\UserCustomId\UserCustomId
 */
class UserCustomId extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \Luttje\UserCustomId\UserCustomIdManager::class;
    }
}
