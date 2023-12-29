<?php

namespace Luttje\UserCustomId\Facades;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Facade;

/**
 * @method static \Luttje\UserCustomId\UserCustomId createFormat(string $targetClass, Model $owner, string $format, string $targetAttribute, ?FormatChunkCollection $lastValueChunks = null)
 * @method static ?UserCustomId get(Model $target, Model $owner) Get the custom id for the given target.
 * @method static string getId(Model $target, Model $owner) Get the custom id for the given target.
 * @method static string getFormat(Model|string $targetOrClass, Model $owner) Get the custom id format for the given target.
 * @method static string generateFor(Model $target, Model $owner) Generate a new custom id for the given target based on the format of this owner.
 * @method static string generateNextCustomId(FormatChunkCollection $chunks, Model $target, Model $owner) Convert the given chunks to a string.
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
