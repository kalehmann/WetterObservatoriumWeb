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

use Exception;

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
     *
     * @var int<1, max>
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
        if (!file_exists($path)) {
            throw new IOException(
                'Could not open buffer at '. $path.
                '. File does not exist',
            );
        }
        $contents = file_get_contents($path);
        if (false === $contents) {
            throw new IOException(
                'Could not read data from ' . $path,
            );
        }

        try {
            return new self($contents, $format);
        } catch (IOException $e) {
            throw new IOException(
                'Error while opening buffer at ' . $path .
                ' : ' . $e->getMessage(),
            );
        }
    }

    /**
     * Open the buffer from the specified path and lock it for exclusive
     * access.
     *
     * @param string $path the path to the buffer (must exist already).
     * @param string $format the format describing the packed elements.
     *                       See the documentation of `pack` for more details.
     * @param callable $callback a function accepting a
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
                flock($stream, LOCK_UN);
                fclose($stream);
                throw new IOException(
                    'Could not read data from ' . $path,
                );
            }
            try {
                $buffer = new self(
                    $data,
                    $format,
                );
            } catch (IOException $e) {
                throw new IOException(
                    'Error while opening buffer at ' . $path .
                    ' : ' . $e->getMessage(),
                );
            }
            try {
                // First operate on the buffer.
                $callback($buffer);
                $data = (string)$buffer;
            } catch (Exception $e) {
                flock($stream, LOCK_UN);
                fclose($stream);

                throw $e;
            }
            // Then save the buffer.
            ftruncate($stream, 0);
            rewind($stream);
            fwrite($stream, $data);
            fflush($stream);
            flock($stream, LOCK_UN);
            fclose($stream);

            return;
        }

        throw new IOException('Could not aquire lock on ' . $path);
    }

    /**
     * Get the number of elements per entry in the buffer.
     *
     * @return int the number of elements per entry.
     */
    public function elementsPerEntry(): int
    {
        return DataPacker::getFormatElementCount($this->formatSpec);
    }

    /**
     * Get the number of entries in the buffer.
     */
    public function count(): int
    {
        return count($this->data);
    }

    /**
     * Read the data form $content into the buffer.
     *
     * @param string $content the binary contents of the buffer.
     * @param int $elementCount the number of elements in the buffer.
     */
    private function readData(string $content, int $elementCount): void
    {
        $dataSize = $elementCount * $this->elementSize;
        if (!$dataSize) {
            return;
        }
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
     * @param string $content the content of the buffer.
     * @param int $elementCount the expected number of elements.
     *
     * @throws IOException if the size of $contents does not match the packed
     * buffer.
     */
    private function validateBufferSize(string $content, int $elementCount): void
    {
        $expectedSize = self::getHeaderSize() + $elementCount * $this->elementSize;
        $actualSize = strlen($content);

        if ($expectedSize !== $actualSize) {
            throw new IOException(
                'The file size of the buffer does not add up.',
            );
        }
    }
}
