<?php

namespace Luttje\UserCustomId\FormatChunks\Time;

use Illuminate\Database\Eloquent\Model;
use Luttje\UserCustomId\FormatChunks\FormatChunk;

class Minute extends FormatChunk
{
    public static function getChunkId(): string
    {
        return 'minute';
    }

    public function getNextValue(Model $target, Model $owner): mixed
    {
        return now()->minute;
    }
}
