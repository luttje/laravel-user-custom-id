<?php

namespace Luttje\UserCustomId\FormatChunks;

use Illuminate\Database\Eloquent\Model;

class Random extends FormatChunk
{
    public static function getChunkId(): string
    {
        return 'random';
    }

    public static function getParametersConfig(): array
    {
        return [
            new FormatChunkParameter('length', 'integer', 10),
            new FormatChunkParameter('characters', 'string', 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789'),
        ];
    }

    /**
     * The most random characters a single chunk may generate.
     */
    public const MAX_LENGTH = 255;

    protected function validateParameters(): void
    {
        $length = (int) $this->getParameter('length');

        if ($length < 1 || $length > static::MAX_LENGTH) {
            throw new \InvalidArgumentException("The parameter 'length' for chunk type '".static::getChunkId()."' must be between 1 and ".static::MAX_LENGTH.'.');
        }

        if ($this->getParameter('characters') === '') {
            throw new \InvalidArgumentException("The parameter 'characters' for chunk type '".static::getChunkId()."' cannot be empty.");
        }
    }

    public function getNextValue(Model $target, Model $owner): mixed
    {
        return $this->generateRandom((int) $this->getParameter('length'));
    }

    /**
     * Generates a random string of the given length.
     */
    public function generateRandom(int $length): string
    {
        $characters = $this->getParameter('characters');
        $charactersLength = strlen($characters);
        $random = '';

        for ($i = 0; $i < $length; $i++) {
            $random .= $characters[$this->getRandomNumber(0, $charactersLength - 1)];
        }

        return $random;
    }

    /**
     * Wrapper for the random function to make it easier to mock.
     *
     * Uses the cryptographically secure generator: what rand() returns can
     * be predicted by someone that has seen enough earlier ids.
     */
    public function getRandomNumber(int $min, int $max): int
    {
        return random_int($min, $max);
    }
}
