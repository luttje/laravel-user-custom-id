<?php

namespace Luttje\UserCustomId\Traits;

use Luttje\UserCustomId\Facades\UserCustomId;
use Luttje\UserCustomId\UserCustomId as UserCustomIdModel;

/**
 * Will automatically generate a custom id for the user using
 * UserCustomId::generateFor($this, $this->owner).
 */
trait WithUserCustomId
{
    protected array $queued_custom_id_updates = [];

    public static function bootWithUserCustomId(): void
    {
        static::creating(function ($model) {
            UserCustomId::generateFor($model, $model->getOwner());
        });

        static::created(function ($model) {
            foreach ($model->queued_custom_id_updates as $update) {
                $customId = $update['custom_id'];
                $chunks = $update['chunks'];

                $customId->update([
                    'last_target_custom_id' => $chunks,
                ]);
            }

            $model->queued_custom_id_updates = [];
        });
    }

    public function queueCustomIdUpdate(UserCustomIdModel $customId, array $chunks): void
    {
        $this->queued_custom_id_updates[] = [
            'custom_id' => $customId,
            'chunks' => $chunks,
        ];
    }

    public function getOwner(): mixed
    {
        return $this->owner;
    }
}
