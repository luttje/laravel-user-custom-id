<?php

namespace Luttje\UserCustomId;

use Luttje\UserCustomId\FormatChunks\FormatChunk;
use Luttje\UserCustomId\FormatChunks\FormatChunkCollection;

class FormatChunkRepository
{
    protected $registeredChunkTypes = [];

    public function registerDefaultChunkTypes()
    {
        $this->registerChunkType(FormatChunks\Increment::class);
        $this->registerChunkType(FormatChunks\Literal::class);
        $this->registerChunkType(FormatChunks\Random::class);
        $this->registerChunkType(FormatChunks\TargetAttribute::class);
    }

    /**
     * Registers a new type of chunk (e.g: {increment})
     */
    public function registerChunkType(string $chunkType)
    {
        if (! is_subclass_of($chunkType, FormatChunk::class)) {
            throw new \Exception('The given chunk type must be an instance of FormatChunk.');
        }

        /** @var FormatChunk $chunkType */
        $id = $chunkType::getChunkId();

        $this->registeredChunkTypes[$id] = $chunkType;
    }

    /**
     * Returns the chunk type for the given chunk id.
     */
    public function getChunkType(string $id): ?string
    {
        return $this->registeredChunkTypes[$id] ?? null;
    }

    /**
     * Returns all registered chunk types.
     */
    public function getChunkTypes(): array
    {
        return $this->registeredChunkTypes;
    }

    /**
     * Given a part of the format string, this method will find
     * the appropriate chunk type and return an instance of it.
     *
     * An example of a format string is: {increment:5:00000}
     *
     * The lastValueChunks parameter contains the last value that was
     * generated for this format. It's already parsed into chunks (because
     * that is how it's saved in the database)
     *
     * @param  string  $chunkString The chunk string to parse (e.g: {increment:5:00000})
     * @param  FormatChunkCollection  $lastValueChunks The last value that was generated for this format.
     */
    public function getChunk(string $chunkString, ?FormatChunkCollection $lastValueChunks = null): ?FormatChunk
    {
        $chunkString = trim($chunkString, '{}');
        $parts = explode(':', $chunkString);
        $id = $parts[0];
        $chunkType = $this->getChunkType($id);

        if (! $chunkType) {
            return null;
        }

        /** @var FormatChunk */
        $chunk = new $chunkType(...array_slice($parts, 1));

        if ($lastValueChunks) {
            foreach ($lastValueChunks as $lastValueChunk) {
                if ($lastValueChunk->getChunkId() === $id) {
                    $chunk->setValue($lastValueChunk->getValue());
                    break;
                }
            }
        }

        return $chunk;
    }
}
