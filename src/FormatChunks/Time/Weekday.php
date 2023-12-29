<?php

namespace Luttje\UserCustomId\FormatChunks\Time;

use Illuminate\Database\Eloquent\Model;
use Luttje\UserCustomId\FormatChunks\FormatChunk;

class Weekday extends FormatChunk
{
    public static function getChunkId(): string
    {
        return 'weekday';
    }

    public function getNextValue(Model $target, Model $owner): mixed
    {
        return now()->translatedFormat('l');
    }
}
