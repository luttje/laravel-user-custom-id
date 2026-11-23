<?php

namespace Luttje\UserCustomId;

use Illuminate\Database\Eloquent\Model;
use Luttje\UserCustomId\Exceptions\InvalidFormatException;
use Luttje\UserCustomId\FormatChunks\FormatChunkCollection;
use Luttje\UserCustomId\FormatChunks\Literal;

class UserCustomIdManager
{
    /**
     * The longest format that fits in the database column.
     */
    public const MAX_FORMAT_LENGTH = 255;

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

    public function createFormat(string $targetClass, Model $owner, string $format, string $targetAttribute, ?FormatChunkCollection $lastValueChunks = null): UserCustomId
    {
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

            $target->{$customId->target_attribute} = $nextCustomId;

            $customId->update([
                'last_target_custom_id' => $chunks,
            ]);

            return $nextCustomId;
        }

        return null;
    }

    /**
     * Generates a whole new custom id based on the given format.
     *
     * The target and owner models are passed to the chunk types so they
     * can use the values of the models to generate the next value.
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
     * Checks that the given format can be used to generate ids, without
     * generating one. Call this (or use the ValidFormat validation rule)
     * before storing a format that a user wrote.
     *
     * @throws InvalidFormatException with a message that can be shown to the user.
     */
    public function validateFormat(string $format): void
    {
        if ($format === '') {
            throw new InvalidFormatException('The format cannot be empty.');
        }

        if (strlen($format) > static::MAX_FORMAT_LENGTH) {
            throw new InvalidFormatException('The format cannot be longer than '.static::MAX_FORMAT_LENGTH.' characters.');
        }

        try {
            $this->parseFormat($format);
        } catch (\InvalidArgumentException $e) {
            if ($e instanceof InvalidFormatException) {
                throw $e;
            }

            // Thrown by the chunks when their parameters are wrong.
            throw new InvalidFormatException($e->getMessage(), previous: $e);
        }
    }

    public function isValidFormat(string $format): bool
    {
        try {
            $this->validateFormat($format);
        } catch (InvalidFormatException) {
            return false;
        }

        return true;
    }

    /**
     * Parses the format of the given custom id, continuing from the
     * last value that was generated for it.
     */
    protected function parseCustomId(UserCustomId $customId): FormatChunkCollection
    {
        return $this->parseFormat($customId->format, $customId->last_target_custom_id);
    }

    /**
     * Parses the given format and returns an collection of chunks.
     * Chunks are identified by the curly braces. If the curly braces
     * are prefixed with a backslash, they will be treated as a literal
     * curly brace and not as a chunk.
     *
     * For literal texts a Literal chunk will be used, for other chunks
     * the appropriate chunk type will be used from FormatChunkRepository.
     *
     * @param  FormatChunkCollection|null  $lastValueChunks  The last value that was generated for this format.
     *
     * @throws InvalidFormatException when the format refers to a chunk that isn't registered.
     * @throws \InvalidArgumentException when the parameters of a chunk are wrong.
     */
    public function parseFormat(string $format, ?FormatChunkCollection $lastValueChunks = null): FormatChunkCollection
    {
        $chunks = [];

        // How often each chunk type has been seen, so every chunk
        // continues from its own last value.
        $occurrences = [];

        $currentChunkString = '';

        $isEscaped = false;

        for ($i = 0; $i < strlen($format); $i++) {
            $char = $format[$i];

            if ($char === '\\') {
                $isEscaped = true;

                continue;
            }

            if ($char === '{' && ! $isEscaped) {
                if ($currentChunkString !== '') {
                    $literal = new Literal;
                    $literal->setValue($currentChunkString);

                    $chunks[] = $literal;
                    $currentChunkString = '';
                }

                $currentChunkString .= $char;

                continue;
            }

            if ($char === '}' && ! $isEscaped) {
                $currentChunkString .= $char;

                $id = explode(':', trim($currentChunkString, '{}'))[0];
                $occurrence = $occurrences[$id] ?? 0;

                // Literals are made from the text between chunks, they can't be asked for.
                $chunk = $id === Literal::getChunkId()
                    ? null
                    : $this->formatChunkRepository->getChunk($currentChunkString, $lastValueChunks, $occurrence);

                if (! $chunk) {
                    throw new InvalidFormatException("The chunk '{$currentChunkString}' is not registered.");
                }

                $occurrences[$id] = $occurrence + 1;
                $chunks[] = $chunk;

                $currentChunkString = '';

                continue;
            }

            $currentChunkString .= $char;
            $isEscaped = false;
        }

        if ($currentChunkString !== '') {
            $literal = new Literal;
            $literal->setValue($currentChunkString);

            $chunks[] = $literal;
        }

        return new FormatChunkCollection($chunks);
    }
}
