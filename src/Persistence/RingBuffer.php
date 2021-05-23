<?php

/**
 *  Copyright (C) 2021 Karsten Lehmann <mail@kalehmann.de>
 *
 *  This file is part of WetterObservatoriumWeb.
 *
 *  WetterObservatoriumWeb is free software: you can redistribute it and/or
 *  modify it under the terms of the GNU Affero General Public License as
 *  published by the Free Software Foundation, version 3 of the License.
 *
 *  WetterObservatoriumWeb is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU Affero General Public License for more details.
 *
 *  You should have received a copy of the GNU Affero General Public License
 *  along with WetterObservatoriumWeb. If not, see
 *  <https://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace KaLehmann\WetterObservatoriumWeb\Persistence;

use \Generator;
use \IteratorAggregate;
use function array_filter;
use function array_keys;
use function array_key_exists;
use function array_map;
use function array_sum;
use function chr;
use function count;
use function explode;
use function file_get_contents;
use function join;
use function pack;
use function substr;
use function strlen;
use function str_repeat;
use function str_split;

/**
 * Implementation of a ring buffer with fixed size elements.
 */
class RingBuffer implements BufferInterface
{
    use BufferTrait;

    /**
     * The index of the newest element in the ring buffer.
     */
    private int $index;

    /**
     * Creates a ring buffer from existing data.
     * {@see RingBuffer::createNew} for creating a entirely new ring buffer.
     *
     * @param string $contents the packed data of the new ring buffer
     *                         including the headers.
     * @param string $format the format describing the packed elements.
     */
    public function __construct(string $contents, string $format)
    {
        $this->elementSize = DataPacker::getElementSize($format);
        $this->formatSpec = $format;

        [$this->count, $this->index] = $this->readHeader($contents);
        $this->validateBufferSize($contents);
        $this->readData($contents);
    }

    /**
     * Create a new ring buffer with $numberOfElements elements.
     *
     * @param int $numberOfElements the number of elements in the ring buffer.
     * @param string $format the format describing the packed elements.
     *                       See the documentation of `pack` for more details.
     * @return self the new ring buffer.
     */
    public static function createNew(int $numberOfElements, string $format): self
    {
        if ($numberOfElements < 1) {
            throw new IOException(
                'The number of elements is too small',
            );
        }

        if (DataPacker::getFormatElementCount($format) < 1) {
            throw new IOException(
                'The format does not contain a single element',
            );
        }

        $contents = pack(
            self::getHeaderFormat(),
            $numberOfElements,
            0,
        ) . str_repeat(
            chr(0),
            $numberOfElements * DataPacker::getElementSize($format),
        );

        return new self($contents, $format);
    }

    /**
     * {@inheritdoc}
     */
    public static function getHeaderFormat(): string
    {
        return DataPacker::UNSIGNED_LONG_LE . DataPacker::UNSIGNED_LONG_LE .
        DataPacker::NULL_BYTE . DataPacker::NULL_BYTE;
    }

    /**
     * {@inheritdoc}
     */
    public static function getHeaderSize(): int
    {
        return DataPacker::FORMAT_SIZES[DataPacker::UNSIGNED_LONG_LE] +
        DataPacker::FORMAT_SIZES[DataPacker::UNSIGNED_LONG_LE] +
        DataPacker::FORMAT_SIZES[DataPacker::NULL_BYTE] +
        DataPacker::FORMAT_SIZES[DataPacker::NULL_BYTE];
    }

    /**
     * Add a new entry to the ring buffer.
     *
     * This method overwrites the oldest existing entry.
     *
     * @param array<int> $entry the data for the new entry. The number of
     *                          elements must match the format.
     *
     * @throws IOException on failure.
     */
    public function addEntry(array $entry): void
    {
        $elementCount = DataPacker::getFormatElementCount($this->formatSpec);
        $entrySize = count($entry);
        if ($elementCount !== count($entry)) {
            throw new IOException(
                'Cannot add an entry with ' . $entrySize . ' elements to the ' .
                'ring buffer where each entry has a fixed size of ' .
                $elementCount . '.',
            );
        }

        $this->index = ($this->index + 1) % $this->count;
        $this->data[$this->index] = array_map(
            fn ($value): int => (int)$value,
            $entry,
        );
    }

    /**
     * Returns an iterator over all elements form the ring buffer in the order
     * of insertion (the oldest element is the first).
     *
     * @return Generator<int, array<int, int>>
     */
    public function getIterator(): Generator
    {
        for ($i = 1; $i <= $this->count; $i++) {
            yield $this->data[($this->index + $i) % $this->count];
        }
    }

    /**
     * Get the youngest entry from the ring buffer.
     *
     * @return array<int, int> the youngest entry in the ring buffer.
     */
    public function lastEntry(): array
    {
        return $this->data[$this->index];
    }

    /**
     * Convert the ring buffer to a binary string.
     */
    public function __toString(): string
    {
        return pack(
            self::getHeaderFormat(),
            $this->count,
            $this->index,
        ) . join(
            '',
            array_map(
                fn(array $element) => pack($this->formatSpec, ...$element),
                iterator_to_array($this),
            ),
        );
    }

    /**
     * Extracts the header containing the total number of elements and the
     * index of current element from the data.
     *
     * @param string $content is the data of the ring buffer as binary string
     *
     * @return array<int> an array of two integers, the first one being the
     *                    total number of elements in the ring buffer and the
     *                    second one being the index of the current element.
     */
    private function readHeader(string $content): array
    {
        $header = substr($content, 0, self::getHeaderSize());

        $unpackedHeader = DataPacker::unpack(self::getHeaderFormat(), $header);
        [$count, $current] = $unpackedHeader;

        return [$count, $current];
    }
}
