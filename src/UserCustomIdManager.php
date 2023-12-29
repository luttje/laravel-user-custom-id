<?php

namespace Luttje\UserCustomId;

use Illuminate\Database\Eloquent\Model;
use Luttje\UserCustomId\Contracts\HasUserCustomId;
use Luttje\UserCustomId\FormatChunks\FormatChunk;
use Luttje\UserCustomId\FormatChunks\FormatChunkCollection;
use Luttje\UserCustomId\FormatChunks\Literal;

class UserCustomIdManager
{
    public function __construct(
        protected ?FormatChunkRepository $formatChunkRepository = null,
    ) {
        $this->formatChunkRepository = $formatChunkRepository ?? new FormatChunkRepository;
        $this->formatChunkRepository->registerDefaultChunkTypes();
    }

    public function getChunkType(string $id): ?string
    {
        return $this->formatChunkRepository->getChunkType($id);
    }

    public function registerChunkType(string $chunkType): void
    {
        $this->formatChunkRepository->registerChunkType($chunkType);
    }

    public function create(Model|string $targetOrClass, Model $owner, string $format, string $targetAttribute, ?FormatChunkCollection $lastValueChunks = null)
    {
        $targetClass = $targetOrClass instanceof Model
            ? $targetOrClass->getMorphClass()
            : $targetOrClass;

        $customId = UserCustomId::create([
            'target_type' => $targetClass,
            'owner_id' => $owner->getKey(),
            'owner_type' => $owner->getMorphClass(),
            'format' => $format,
            'target_attribute' => $targetAttribute,
            'last_target_custom_id' => $lastValueChunks,
        ]);

        return $customId;
    }

    public function get(Model|string $targetOrClass, Model $owner): ?UserCustomId
    {
        $targetClass = $targetOrClass instanceof Model
            ? $targetOrClass->getMorphClass()
            : $targetOrClass;

        $customId = UserCustomId::where('target_type', $targetClass)
            ->where('owner_id', $owner->getKey())
            ->where('owner_type', $owner->getMorphClass())
            ->first();

        return $customId;
    }

    public function getId(Model $target, Model $owner): ?string
    {
        $customId = $this->get($target, $owner);

        if ($customId) {
            return $target->{$customId->target_attribute};
        }

        return null;
    }

    public function getFormat(Model|string $targetOrClass, Model $owner): ?string
    {
        $customId = $this->get($targetOrClass, $owner);

        if ($customId) {
            return $customId->format;
        }

        return null;
    }

    /**
     * Generates a whole new custom id for the given target.
     */
    public function generateFor(Model $target, Model $owner): ?string
    {
        $customId = $this->get($target, $owner);

        if ($customId) {
            $chunks = $this->parseCustomId($customId);
            $nextCustomId = $this->generateNextCustomId($chunks, $target, $owner);
            $shouldUpdateLatest = true;

            $target->{$customId->target_attribute} = $nextCustomId;

            if (in_array(HasUserCustomId::class, class_implements($target))) {
                // The trait will save in the eloquent created event.
                $shouldUpdateLatest = false;
                /** @var HasUserCustomId $target */
                $target->queueCustomIdUpdate($customId, $chunks);
            }

            if ($shouldUpdateLatest) {
                $customId->update([
                    'last_target_custom_id' => $chunks,
                ]);
            }

            return $nextCustomId;
        }

        return null;
    }

    /**
     * Generates a whole new custom id based on the given format.
     *
     * The target and owner models are passed to the chunk types so they
     * can use the values of the models to generate the next value.
     *
     * @param FormatChunkCollection $chunks
     */
    public function generateNextCustomId(FormatChunkCollection $chunks, Model $target, Model $owner): string
    {
        $generated = '';

        foreach ($chunks as $chunk) {
            $generated .= $chunk->generateNextValue($target, $owner);
        }

        return $generated;
    }

    /**
     * Parses the given custom id and returns an collection of chunks.
     * Chunks are identified by the curly braces. If the curly braces
     * are prefixed with a backslash, they will be treated as a literal
     * curly brace and not as a chunk.
     *
     * For literal texts a Literal chunk will be used, for other chunks
     * the appropriate chunk type will be used from FormatChunkRepository.
     */
    protected function parseCustomId(UserCustomId $customId): FormatChunkCollection
    {
        $lastValueChunks = $customId->last_target_custom_id;
        $format = $customId->format;
        $chunks = [];

        $currentChunkString = '';

        $isEscaped = false;

        for ($i = 0; $i < strlen($format); $i++) {
            $char = $format[$i];

            if ($char === '\\') {
                $isEscaped = true;

                continue;
            }

            if ($char === '{' && ! $isEscaped) {
                if ($currentChunkString) {
                    $literal = new Literal();
                    $literal->setValue($currentChunkString);

                    $chunks[] = $literal;
                    $currentChunkString = '';
                }

                $currentChunkString .= $char;

                continue;
            }

            if ($char === '}' && ! $isEscaped) {
                $currentChunkString .= $char;

                $chunk = $this->formatChunkRepository->getChunk($currentChunkString, $lastValueChunks);

                if (! $chunk) {
                    throw new \Exception("The chunk '{$currentChunkString}' is not registered.");
                }

                $chunks[] = $chunk;

                $currentChunkString = '';

                continue;
            }

            $currentChunkString .= $char;
            $isEscaped = false;
        }

        if ($currentChunkString) {
            $literal = new Literal();
            $literal->setValue($currentChunkString);

            $chunks[] = $literal;
        }

        return new FormatChunkCollection($chunks);
    }
}
