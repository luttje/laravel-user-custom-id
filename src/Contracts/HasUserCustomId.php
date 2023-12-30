<?php

namespace Luttje\UserCustomId\Contracts;

interface HasUserCustomId
{
    public static function bootWithUserCustomId(): void;

    /**
     * The owner of this model.
     */
    public function getOwner(): mixed;
}
