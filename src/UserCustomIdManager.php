<?php

namespace Luttje\UserCustomId;

use Illuminate\Database\Eloquent\Model;
use Luttje\UserCustomId\Contracts\HasUserCustomId;
use Luttje\UserCustomId\FormatChunks\FormatChunk;
use Luttje\UserCustomId\FormatChunks\Literal;

class UserCustomIdManager
{
    public function __construct(
        protected ?FormatChunkRepository $formatChunkRepository = null,
    ) {
        $this->formatChunkRepository = $formatChunkRepository ?? new FormatChunkRepository;
        $this->formatChunkRepository->registerDefaultChunkTypes();
    }

    public function getChunkType(string $chunkId): ?string
    {
        return $this->formatChunkRepository->getChunkType($chunkId);
    }

    public function create(Model|string $targetOrClass, Model $owner, string $format, string $targetAttribute, ?array $lastValueChunks = null)
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
    public function generateFor(Model|string $targetOrClass, Model $owner): ?string
    {
        $customId = $this->get($targetOrClass, $owner);

        if ($customId) {
            $chunks = $this->generate($customId->format, $customId->last_target_custom_id);
            $generated = $this->convertToString($chunks);
            $isModel = $targetOrClass instanceof Model;
            $shouldUpdateLatest = true;

            if ($isModel) {
                $targetOrClass->{$customId->target_attribute} = $generated;

                if (in_array(HasUserCustomId::class, class_implements($targetOrClass))) {
                    // The trait will save in the eloquent created event.
                    $shouldUpdateLatest = false;
                    $targetOrClass->queueCustomIdUpdate($customId, $chunks);
                }
            }

            if ($shouldUpdateLatest) {
                $customId->update([
                    'last_target_custom_id' => $chunks,
                ]);
            }

            return $generated;
        }

        return null;
    }

    /**
     * Generates a whole new custom id based on the given format.
     *
     * @return FormatChunk[]
     */
    public function generate(string $format, ?array $lastValueChunks = null): array
    {
        $chunks = $this->parseFormat($format, $lastValueChunks);

        return $chunks;
    }

    /**
     * Generates a whole new custom id based on the given format.
     *
     * @param FormatChunk[]
     */
    public function convertToString(array $chunks): string
    {
        $generated = '';

        foreach ($chunks as $chunk) {
            $generated .= $chunk->getNextValue();
        }

        return $generated;
    }

    /**
     * Parses the given format string and returns an array of chunks.
     * Chunks are identified by the curly braces. If the curly braces
     * are prefixed with a backslash, they will be treated as a literal
     * curly brace and not as a chunk.
     *
     * For literal texts a Literal chunk will be used, for other chunks
     * the appropriate chunk type will be used from FormatChunkRepository.
     *
     * @return FormatChunk[]
     */
    public function parseFormat(string $format, ?array $lastValueChunks = null): array
    {
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

        return $chunks;
    }
}
