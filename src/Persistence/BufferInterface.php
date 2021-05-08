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
use \IteratorAggregate;
use \Stringable;

/**
 * @extends IteratorAggregate<int, array<int, int>>
 */
interface BufferInterface extends Countable, IteratorAggregate, Stringable
{
    /**
     * Add a new entry to the buffer.
     *
     * @param array<int> $entry the data for the new entry. The number of
     *                          elements must match the format.
     *
     * @throws IOException on failure.
     */
    public function addEntry(array $entry): void;
}
