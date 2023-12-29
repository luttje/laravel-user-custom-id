<?php

namespace Luttje\UserCustomId\Contracts;

use Luttje\UserCustomId\FormatChunks\FormatChunk;
use Luttje\UserCustomId\UserCustomId;

interface HasUserCustomId
{
    public static function bootWithUserCustomId(): void;

    /**
     * Queue an update for the custom id.
     *
     * @param  FormatChunk[]  $chunks
     */
    public function queueCustomIdUpdate(UserCustomId $customId, array $chunks): void;
}
