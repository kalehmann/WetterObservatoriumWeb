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

/**
 * Helper methods for implementing buffers.
 */
trait BufferTrait
{
    /**
     * The number of elements in the buffer.
     */
    private int $count;

    /**
     * @var array<array<int, int>>
     */
    private array $data;

    /**
     * The packed size of each element in bytes.
     */
    private int $elementSize;

    /**
     * The pack format of each element.
     */
    private string $formatSpec;

    /**
     * Load a buffer from a existing file.
     *
     * @param string $path the path to the file with the buffer.
     * @param string $format the format describing the packed elements.
     *                See the documentation of `pack` for more details.
     * @return self the buffer loaded from the file.
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
     * Open the buffer from the specified path and lock it for exclusive
     * access.
     *
     * @param string $path the path to the buffer (must exist already).
     * @param string $format the format describing the packed elements.
     *                       See the documentation of `pack` for more details.
     * @param callable $callback a function accpting a
     *                           {@see Bufferinterface::class} as single
     *                           parameter. All actions on the buffer are
     *                           exclusive without concurrent access
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
            $buffer = new self(
                $data,
                $format,
            );
            ftruncate($stream, 0);
            rewind($stream);
            $callback($buffer);
            fwrite($stream, (string)$buffer);
            fflush($stream);
            flock($stream, LOCK_UN);
            fclose($stream);

            return;
        }

        throw new IOException('Could not aquire lock on ' . $path);
    }

    /**
     * Get the number of entries in the ring buffer.
     */
    public function count(): int
    {
        return $this->count;
    }

    /**
     * Read the data form $content into the buffer.
     */
    private function readData(string $content): void
    {
        $dataSize = $this->count * $this->elementSize;
        $data = substr($content, self::getHeaderSize(), $dataSize);
        $elements = str_split($data, $this->elementSize);
        foreach ($elements as $index => $packedEntry) {
            $unpackedData = DataPacker::unpack($this->formatSpec, $packedEntry);
            $this->addEntry($unpackedData);
        }
    }

    /**
     * Checks that $content matches the packed size of the buffer.
     *
     * @throws IOException if the size of $contents does not match the packed
     * buffer.
     */
    private function validateBufferSize(string $content): void
    {
        $expectedSize = self::getHeaderSize() + $this->count * $this->elementSize;
        $actualSize = strlen($content);

        if ($expectedSize !== $actualSize) {
            throw new IOException(
                'The file size of the ring buffer at does not add up.',
            );
        }
    }
}
