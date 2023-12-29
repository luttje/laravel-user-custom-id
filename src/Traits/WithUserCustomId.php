<?php

namespace Luttje\UserCustomId\Traits;

use Illuminate\Support\Facades\DB;
use Luttje\UserCustomId\Facades\UserCustomId;
use Luttje\UserCustomId\FormatChunks\FormatChunkCollection;
use Luttje\UserCustomId\UserCustomId as UserCustomIdModel;

/**
 * Will automatically generate a custom id for the user using
 * UserCustomId::generateFor($this, $this->owner).
 */
trait WithUserCustomId
{
    /**
     * @var array<array{custom_id: UserCustomIdModel, chunks: FormatChunkCollection}>
     */
    protected array $queued_custom_id_updates = [];

    public static function bootWithUserCustomId(): void
    {
        static::creating(function ($model) {
            $owner = $model->getOwner();

            if (! $owner) {
                throw new \Exception('Cannot create a custom id for a model without an owner. Did you forget to implement the getOwner() method?');
            }

            DB::beginTransaction();
            UserCustomId::generateFor($model, $owner);
        });

        static::created(function ($model) {
            foreach ($model->queued_custom_id_updates as $update) {
                $customId = $update['custom_id'];
                $chunks = $update['chunks'];

                $customId->update([
                    'last_target_custom_id' => $chunks,
                ]);
            }

            DB::commit();
            $model->queued_custom_id_updates = [];
        });
    }

    public function queueCustomIdUpdate(UserCustomIdModel $customId, FormatChunkCollection $chunks): void
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
