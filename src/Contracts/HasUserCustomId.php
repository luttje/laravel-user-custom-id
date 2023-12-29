<?php

namespace Luttje\UserCustomId\Contracts;

use Luttje\UserCustomId\FormatChunks\FormatChunkCollection;
use Luttje\UserCustomId\UserCustomId;

interface HasUserCustomId
{
    public static function bootWithUserCustomId(): void;

    /**
     * Queue an update for the custom id.
     */
    public function queueCustomIdUpdate(UserCustomId $customId, FormatChunkCollection $chunks): void;

    /**
     * The owner of this model.
     */
    public function getOwner(): mixed;
}
