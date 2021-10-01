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

use Countable;
use IteratorAggregate;
use Stringable;

/**
 * @extends IteratorAggregate<int, array<int, int>>
 */
interface BufferInterface extends Countable, IteratorAggregate, Stringable
{
    /**
     * Load a buffer from a existing file.
     *
     * @param string $path the path to the file with the buffer.
     * @param string $format the format describing the packed elements.
     *                       See the documentation of `pack` for more details.
     * @return self the buffer loaded from the file.
     */
    public static function fromFile(string $path, string $format): self;

    /**
     * Get the pack format describing the header of the buffer.
     *
     * @return string the format of the packed header.
     */
    public static function getHeaderFormat(): string;

    /**
     * Get the size of the packed header in bytes.
     *
     * @return int the size of the packed header in bytes.
     */
    public static function getHeaderSize(): int;

    /**
     * Open the buffer from the specified path and lock it for exclusive  access.
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
    ): void;

    /**
     * Add a new entry to the buffer.
     *
     * @param array<int> $entry the data for the new entry. The number of
     *                          elements must match the format.
     *
     * @throws IOException on failure.
     */
    public function addEntry(array $entry): void;

    /**
     * Get the number of elements per entry in the buffer.
     *
     * @return int the number of elements per entry.
     */
    public function elementsPerEntry(): int;
}
