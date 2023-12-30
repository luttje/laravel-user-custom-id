<?php

namespace Luttje\UserCustomId\FormatChunks;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Model;
use Luttje\UserCustomId\Facades\UserCustomId;

abstract class FormatChunk implements Arrayable
{
    /**
     * The current value for this entire chunk.
     */
    protected mixed $value = null;

    /**
     * The values for the parameters of this chunk.
     */
    protected array $parameters = [];

    /**
     * The chunk, when instantiated will receive all the parameters as
     * a variable amount of arguments.
     */
    public function __construct(
        ...$parameters
    ) {
        $parameterTypes = static::getParametersConfig();

        foreach ($parameterTypes as $parameterType) {
            $name = $parameterType->getName();
            $type = $parameterType->getType();
            $value = array_shift($parameters);

            if ($value === null) {
                $value = static::getDefaultParameter($name);
            }

            if ($value === null) {
                throw new \InvalidArgumentException("The parameter '{$name}' is required for chunk type '".static::getChunkId()."'.");
            }

            // Try cast the value to the specified type
            $value = match ($type) {
                'integer' => (is_numeric($value) && is_int((int) $value)) ? (int) $value : $value,
                'float', 'double', 'numeric' => is_numeric($value) ? (float) $value : $value,
                'string' => (string) $value,
                'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
                default => $value,
            };

            if ($type === 'numeric') {
                if (! is_numeric($value)) {
                    throw new \InvalidArgumentException("The parameter '{$name}' must be numeric, '{$value}' given.");
                }
            } else {
                $valueType = gettype($value);

                if ($valueType !== $type) {
                    throw new \InvalidArgumentException("The parameter '{$name}' must be of type '{$type}', '{$valueType}' given.");
                }
            }

            $this->setParameter($name, $value);
        }
    }

    /**
     * Returns the chunk id as it appears in the format string.
     * E.g: 'random' for being used like this: {random}
     */
    abstract public static function getChunkId(): string;

    /**
     * Returns the parameter names and types that are allowed for this chunk.
     *
     * @return FormatChunkParameter[]
     */
    public static function getParametersConfig(): array
    {
        return [];
    }

    /**
     * Returns the parameter for the specified name.
     */
    public static function getParameterConfig(string $name): ?FormatChunkParameter
    {
        foreach (static::getParametersConfig() as $parameterConfig) {
            if ($parameterConfig->getName() === $name) {
                return $parameterConfig;
            }
        }

        return null;
    }

    /**
     * Returns the default parameter values for this chunk.
     *
     * @return array<string, string|null>
     */
    public static function getDefaultParameters(): array
    {
        $defaults = [];

        foreach (static::getParametersConfig() as $name => $parameter) {
            $defaults[$name] = $parameter->getDefaultValue();
        }

        return $defaults;
    }

    /**
     * Returns the default parameter value for the specified parameter.
     */
    public static function getDefaultParameter(string $name): mixed
    {
        return static::getParameterConfig($name)?->getDefaultValue() ?? null;
    }

    /**
     * Sets the current value for this chunk.
     */
    public function setValue(mixed $value): void
    {
        $this->value = $value;
    }

    /**
     * Returns the current value for this chunk.
     */
    public function getValue(): mixed
    {
        return $this->value;
    }

    /**
     * Returns the next value for this chunk. For example, if we want this
     * chunk to always increment by 1, this method should return the current
     * value + 1.
     *
     * The target and owner models are passed to the chunk types so they
     * can use the values of the models to generate the next value.
     */
    abstract public function getNextValue(Model $target, Model $owner): mixed;

    /**
     * Generates the next value for this chunk and sets it as the current value
     * so it's ready to be saved in the database.
     */
    public function generateNextValue(Model $target, Model $owner): mixed
    {
        $nextValue = $this->getNextValue($target, $owner);

        $this->setValue($nextValue);

        return $nextValue;
    }

    /**
     * Sets the specified parameter for this chunk.
     */
    public function setParameter(string $name, mixed $value): void
    {
        $this->parameters[$name] = $value;
    }

    /**
     * Returns the specified parameter for this chunk.
     */
    public function getParameter(string $name): string
    {
        return $this->parameters[$name] ?? static::getDefaultParameter($name);
    }

    /**
     * Returns the chunk as it should appear in the format string.
     * E.g: {random:5:A-Z}
     */
    public function __toString(): string
    {
        $parameters = [];

        foreach ($this->parameters as $parameter) {
            $parameters[] = $parameter;
        }

        return '{'.static::getChunkId().':'.implode(':', $parameters).'}';
    }

    /**
     * Serializes the chunk to an array.
     */
    public function toArray(): array
    {
        return [
            'id' => static::getChunkId(),
            'parameters' => $this->parameters,
            'value' => $this->value,
        ];
    }

    /**
     * Deserializes the chunk from an array.
     */
    public static function fromArray(array $data): FormatChunk
    {
        $id = $data['id'];
        $parameters = $data['parameters'];
        $value = $data['value'];

        $chunkType = UserCustomId::getChunkType($id);

        if (! $chunkType) {
            throw new \Exception("The chunk type '{$id}' is not registered.");
        }

        /** @var FormatChunk */
        $chunk = new $chunkType(...$parameters);

        $chunk->setValue($value);

        return $chunk;
    }
}
