<?php

namespace Luttje\UserCustomId\FormatChunks\Time;

use Illuminate\Database\Eloquent\Model;
use Luttje\UserCustomId\FormatChunks\FormatChunk;

class Millisecond extends FormatChunk
{
    public static function getChunkId(): string
    {
        return 'millisecond';
    }

    public function getNextValue(Model $target, Model $owner): mixed
    {
        return now()->millisecond;
    }
}
