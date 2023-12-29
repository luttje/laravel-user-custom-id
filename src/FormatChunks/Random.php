<?php

namespace Luttje\UserCustomId\FormatChunks;

use Illuminate\Database\Eloquent\Model;

class Random extends FormatChunk
{
    public static function getChunkId(): string
    {
        return 'random';
    }

    public static function getParameters(): array
    {
        return [
            new FormatChunkParameter('length', 'integer', 10),
            new FormatChunkParameter('characters', 'string', 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789'),
        ];
    }

    public function getNextValue(Model $target, Model $owner): mixed
    {
        return $this->generateRandom((int) $this->getParameterValue('length'));
    }

    /**
     * Generates a random string of the given length.
     */
    public function generateRandom(int $length): string
    {
        $characters = $this->getParameterValue('characters');
        $charactersLength = strlen($characters);
        $random = '';

        for ($i = 0; $i < $length; $i++) {
            $random .= $characters[$this->getRandomNumber(0, $charactersLength - 1)];
        }

        return $random;
    }

    /**
     * Wrapper for the rand function to make it easier to mock.
     */
    public function getRandomNumber(int $min, int $max): int
    {
        return rand($min, $max);
    }
}
