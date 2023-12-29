<?php

namespace Luttje\UserCustomId\FormatChunks\Time;

use Illuminate\Database\Eloquent\Model;
use Luttje\UserCustomId\FormatChunks\FormatChunk;

class Year extends FormatChunk
{
    public static function getChunkId(): string
    {
        return 'year';
    }

    public function getNextValue(Model $target, Model $owner): mixed
    {
        return now()->year;
    }
}
