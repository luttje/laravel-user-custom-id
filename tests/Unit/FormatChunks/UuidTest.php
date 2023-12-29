<?php

namespace Luttje\UserCustomId\Tests\Unit\FormatChunks;

use DateTimeInterface;
use Ramsey\Uuid\Type\Hexadecimal;
use Ramsey\Uuid\Type\Integer as IntegerObject;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidFactory;
use Ramsey\Uuid\UuidFactoryInterface;
use Ramsey\Uuid\UuidInterface;
use Ramsey\Uuid\Validator\ValidatorInterface;

final class UuidTest extends FormatChunkTestCase
{
    private function setUuidMockFactory(string $expected)
    {
        $realFactory = new UuidFactory();

        Uuid::setFactory(new class($realFactory, $expected) implements UuidFactoryInterface
        {
            private $realFactory;

            private $expected;

            public function __construct(UuidFactoryInterface $realFactory, string $expected)
            {
                $this->realFactory = $realFactory;
                $this->expected = $expected;
            }

            public function uuid4(): UuidInterface
            {
                return $this->realFactory->fromString($this->expected);
            }

            // Create empty other functions that meet bare minimum requirements
            public function fromBytes(string $bytes): UuidInterface
            {
                return $this->realFactory->fromString($this->expected);
            }

            public function fromString(string $uuid): UuidInterface
            {
                return $this->realFactory->fromString($this->expected);
            }

            public function fromInteger(string $integer): UuidInterface
            {
                return $this->realFactory->fromString($this->expected);
            }

            public function fromDateTime(
                DateTimeInterface $dateTime,
                ?Hexadecimal $node = null,
                ?int $clockSeq = null
            ): UuidInterface {
                return $this->realFactory->fromString($this->expected);
            }

            public function getValidator(): ValidatorInterface
            {
                return new class implements ValidatorInterface
                {
                    public function getPattern(): string
                    {
                        return '/^[a-f0-9]{8}-([a-f0-9]{4}-){3}[a-f0-9]{12}$/i';
                    }

                    public function validate(string $uuid): bool
                    {
                        return true;
                    }
                };
            }

            public function uuid1($node = null, ?int $clockSeq = null): UuidInterface
            {
                return $this->realFactory->fromString($this->expected);
            }

            public function uuid2(
                int $localDomain,
                ?IntegerObject $localIdentifier = null,
                ?Hexadecimal $node = null,
                ?int $clockSeq = null
            ): UuidInterface {
                return $this->realFactory->fromString($this->expected);
            }

            public function uuid3($ns, string $name): UuidInterface
            {
                return $this->realFactory->fromString($this->expected);
            }

            public function uuid5($ns, string $name): UuidInterface
            {
                return $this->realFactory->fromString($this->expected);
            }

            public function uuid6(?Hexadecimal $node = null, ?int $clockSeq = null): UuidInterface
            {
                return $this->realFactory->fromString($this->expected);
            }
        });
    }

    public function testCanGenerateValidUuid(): void
    {
        $chunk = $this->getChunk('uuid', []);

        $this->assertMatchesRegularExpression('/^[a-f0-9]{8}-([a-f0-9]{4}-){3}[a-f0-9]{12}$/i', $this->getNextValue($chunk));
    }

    public function testCanGenerateUuid(): void
    {
        $this->setUuidMockFactory('7841391a-2b16-4c27-b384-4ed8baa05db6');

        $chunk = $this->getChunk('uuid', []);

        $this->assertEquals('7841391a-2b16-4c27-b384-4ed8baa05db6', $this->getNextValue($chunk));
    }

    public function testCanGenerateUuidV1(): void
    {
        $this->setUuidMockFactory('b99c6428-a65e-11ee-a506-0242ac120002');

        $chunk = $this->getChunk('uuid', [1]);

        $this->assertEquals('b99c6428-a65e-11ee-a506-0242ac120002', $this->getNextValue($chunk));
    }

    public function testCanGenerateUuidV4(): void
    {
        $this->setUuidMockFactory('7841391a-2b16-4c27-b384-4ed8baa05db6');

        $chunk = $this->getChunk('uuid', [4]);

        $this->assertEquals('7841391a-2b16-4c27-b384-4ed8baa05db6', $this->getNextValue($chunk));
    }
}
