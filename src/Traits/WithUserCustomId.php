<?php

namespace Luttje\UserCustomId\Traits;

use Luttje\UserCustomId\Facades\UserCustomId;

/**
 * Will automatically generate a custom id for the user using
 * UserCustomId::generateFor($this, $this->owner).
 */
trait WithUserCustomId
{
    public static function bootWithUserCustomId(): void
    {
        static::creating(function ($model) {
            $owner = $model->getOwner();

            if (! $owner) {
                throw new \Exception('Cannot create a custom id for a model without an owner. Did you forget to implement the getOwner() method?');
            }

            UserCustomId::generateFor($model, $owner);
        });
    }

    public function getOwner(): mixed
    {
        return $this->owner;
    }
}
