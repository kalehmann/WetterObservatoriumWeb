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

namespace KaLehmann\WetterObservatoriumWeb\Persistence\Filesystem;

use \Generator;

/**
 * Implementation of a buffer with fixed size elements.
 */
class Buffer implements BufferInterface
{
    use BufferTrait;

    /**
     * Creates a buffer from existing data.
     * {@see Buffer::createNew} for creating a entirely new buffer.
     *
     * @param string $contents the packed data of the new buffer including the
     *                         headers.
     * @param string $format the format describing the packed elements.
     */
    public function __construct(string $contents, string $format)
    {
        $this->elementSize = DataPacker::getElementSize($format);
        $this->formatSpec = $format;

        $elementCount = $this->readHeader($contents);
        $this->data = [];
        $this->validateBufferSize($contents, $elementCount);
        $this->readData($contents, $elementCount);
    }

    /**
     * Create a new buffer with $numberOfElements elements.
     *
     * @param string $format the format describing the packed elements.
     *                       See the documentation of `pack` for more details.
     * @return self the new buffer.
     */
    public static function createNew(
        string $format,
    ): self {
        if (DataPacker::getFormatElementCount($format) < 1) {
            throw new IOException(
                'The format does not contain a single element',
            );
        }

        $numberOfElements = 0;
        $contents = pack(
            self::getHeaderFormat(),
            $numberOfElements,
        );

        return new self($contents, $format);
    }

    /**
     * Get the pack format describing the header of the buffer.
     *
     * @return string the format of the packed header.
     */
    public static function getHeaderFormat(): string
    {
        return DataPacker::UNSIGNED_LONG_LE . DataPacker::NULL_BYTE .
        DataPacker::NULL_BYTE . DataPacker::NULL_BYTE . DataPacker::NULL_BYTE .
        DataPacker::NULL_BYTE . DataPacker::NULL_BYTE;
    }

    /**
     * Get the size of the packed header in bytes.
     *
     * @return int the size of the packed header in bytes.
     */
    public static function getHeaderSize(): int
    {
        return  DataPacker::FORMAT_SIZES[DataPacker::UNSIGNED_LONG_LE] +
        DataPacker::FORMAT_SIZES[DataPacker::NULL_BYTE] * 6;
    }

    /**
     * Add a new entry to the buffer.
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

        $this->data[] = $entry;
    }

    /**
     * Returns a iterator over all elements in the buffer.
     *
     * @return Generator<int, array<int, int>>
     */
    public function getIterator(): Generator
    {
        foreach ($this->data as $entry) {
            yield $entry;
        }
    }

    /**
     * Convert the buffer to a binary string.
     */
    public function __toString(): string
    {
        return pack(
            self::getHeaderFormat(),
            $this->count(),
        ) . join(
            '',
            array_map(
                fn(array $element) => pack($this->formatSpec, ...$element),
                $this->data
            ),
        );
    }

    /**
     * Extracts the header containing the total number of elements and the
     * index of current element from the data.
     *
     * @param string $content is the data of the ring buffer as binary string
     *
     * @return int the number of elements in the buffer.
     */
    private function readHeader(string $content): int
    {
        $header = substr($content, 0, self::getHeaderSize());

        $unpackedHeader = DataPacker::unpack(self::getHeaderFormat(), $header);
        [$count] = $unpackedHeader;

        return $count;
    }
}
