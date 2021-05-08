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

use \Countable;
use \Generator;
use \IteratorAggregate;
use \Stringable;
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
 *
 * @implements IteratorAggregate<int, array<int, int>>
 */
class RingBuffer implements Countable, IteratorAggregate, Stringable
{
    public const HEADER_FORMAT
        = DataPacker::UNSIGNED_LONG_LE . DataPacker::UNSIGNED_LONG_LE .
        DataPacker::NULL_BYTE . DataPacker::NULL_BYTE;

    public const HEADER_SIZE
        = DataPacker::FORMAT_SIZES[DataPacker::UNSIGNED_LONG_LE] +
        DataPacker::FORMAT_SIZES[DataPacker::UNSIGNED_LONG_LE] +
        DataPacker::FORMAT_SIZES[DataPacker::NULL_BYTE] +
        DataPacker::FORMAT_SIZES[DataPacker::NULL_BYTE];

    private int $count;

    /**
     * @var array<array<int, int>>
     */
    private array $data;

    private int $elementSize;

    private string $formatSpec;

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
     * @return self the new RingBuffer.
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
            self::HEADER_FORMAT,
            $numberOfElements,
            0,
        ) . str_repeat(
            chr(0),
            $numberOfElements * DataPacker::getElementSize($format),
        );

        return new self($contents, $format);
    }

    /**
     * Load a ring buffer from a existing file.
     *
     * @param string $path the path to the file with the ring buffer.
     * @param string $format the format describing the packed elements.
     *                See the documentation of `pack` for more details.
     * @return self the ring buffer loaded from the file.
     */
    public static function fromFile(string $path, string $format): self
    {
        $contents = file_get_contents($path);
        if (false === $contents) {
            throw new IOException(
                'Could not read data from ' . $path,
            );
        }

        return new self($contents, $format);
    }

    /**
     * Open the ring buffer from the specified path and lock it for exclusive
     * access.
     *
     * @param string $path the path to the ring buffer (must exist already).
     * @param string $format the format describing the packed elements.
     *                       See the documentation of `pack` for more details.
     * @param callable $callback a function accpting a {@see RingBuffer::class}
     *                           as single parameter. All actions on the ring
     *                           buffer are exclusive without concurrent access
     *                           from paralell calls to this method.
     */
    public static function operateExclusive(
        string $path,
        string $format,
        callable $callback
    ): void {
        $stream = fopen($path, 'r+');
        if (false === $stream) {
            throw new IOException(
                'Could not open ' . $path,
            );
        }

        if (flock($stream, LOCK_EX)) {
            $data = stream_get_contents($stream);
            if (false === $data) {
                throw new IOException(
                    'Could not read data from ' . $path,
                );
            }
            $ringBuffer = new self(
                $data,
                $format,
            );
            ftruncate($stream, 0);
            rewind($stream);
            $callback($ringBuffer);
            fwrite($stream, (string)$ringBuffer);
            fflush($stream);
            flock($stream, LOCK_UN);
            fclose($stream);

            return;
        }

        throw new IOException('Could not aquire lock on ' . $path);
    }

    /**
     * Add a new entry to the ring buffer.
     *
     * This method overwrites the oldest existing entry.
     *
     * @param array<int> $entry the data for the new entry. The number of
     *                          elements must match the format.
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

        $this->data[$this->index] = array_map(
            fn ($value): int => (int)$value,
            $entry,
        );
        $this->index = ($this->index + 1) % $this->count;
    }

    /**
     * Get the number of entries in the ring buffer.
     */
    public function count(): int
    {
        return $this->count;
    }

    /**
     * Returns an iterator over all elements form the ring buffer in the order
     * of insertion (the oldest element is the first).
     *
     * @return Generator<int, array<int, int>>
     */
    public function getIterator(): Generator
    {
        for ($i = 0; $i < $this->count; $i++) {
            yield $this->data[($this->index + $i) % $this->count];
        }
    }

    /**
     * Converts the ring buffer to a binary string.
     */
    public function __toString(): string
    {
        return pack(
            self::HEADER_FORMAT,
            $this->count,
            $this->index,
        ) . join(
            '',
            array_map(
                fn(array $element) => pack($this->formatSpec, ...$element),
                $this->data
            ),
        );
    }

    /**
     * Read the data form $content into the ring buffer.
     */
    private function readData(string $content): void
    {
        $dataSize = $this->count * $this->elementSize;
        $data = substr($content, self::HEADER_SIZE, $dataSize);
        $elements = str_split($data, $this->elementSize);
        foreach ($elements as $index => $packedEntry) {
            $unpackedData = $this->unpack($this->formatSpec, $packedEntry);
            $this->addEntry($unpackedData);
        }
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
        $header = substr($content, 0, self::HEADER_SIZE);

        $unpackedHeader = $this->unpack(self::HEADER_FORMAT, $header);
        [$count, $current] = $unpackedHeader;

        return [$count, $current];
    }

    /**
     * Slightly modified version of the built in function `unpack`.
     *
     * Same as `unpack` except that it deos not need named elements and returns
     * a zero indexed array with all unpacked elemets.
     *
     * @return array<int, int>
     */
    private function unpack(string $format, string $packedData): array
    {
        $namedFormat = '';
        for ($i = 0; $i < strlen($format); $i++) {
            if ($format[$i] === 'x') {
                $namedFormat .= 'x';

                continue;
            }
            $namedFormat .= $format[$i] . '_' . $i . '/';
        }

        $unpackedData = unpack($namedFormat, $packedData);
        if (false === $unpackedData) {
            throw new IOException(
                'Could not unpack "' . bin2hex($packedData) .
                '" with format "' . $format . '"',
            );
        }

        return array_values(
            array_filter(
                $unpackedData,
                fn ($value, string $key) => $key[0] !== 'd',
                ARRAY_FILTER_USE_BOTH,
            )
        );
    }

    /**
     * Checks that $content matches the packed size of the ring buffer.
     *
     * @throws IOException if the size of $contents does not match the packed
     * ring buffer.
     */
    private function validateBufferSize(string $content): void
    {
        $expectedSize = self::HEADER_SIZE + $this->count * $this->elementSize;
        $actualSize = strlen($content);

        if ($expectedSize !== $actualSize) {
            throw new IOException(
                'The file size of the ring buffer at does not add up.',
            );
        }
    }
}
