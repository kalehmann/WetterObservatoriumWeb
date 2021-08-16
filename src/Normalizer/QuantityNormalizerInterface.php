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

namespace KaLehmann\WetterObservatoriumWeb\Normalizer;

/**
 * Converts incoming data into unsigned integers and back for a specific
 * quantity.
 */
interface QuantityNormalizerInterface
{
    /**
     * Whether the normalizer supports the given quantity or not.
     */
    public function supportsQuantity(string $quantity): bool;

    /**
     * Converts the measured value into an unsigned integer for persisting.
     *
     * @param float|int $value the measured value.
     * @return int the unsigned integer that could be persisted.
     */
    public function normalizeValue(float|int $value): int;

    /**
     * Converts the normalized value back into the "real"/measured value.
     *
     * @param int $value the unsigned integer of the value stored in the
     *                   database
     * @return float|int the "real"/measured value
     */
    public function denormalizeValue(int $value): float|int;
}
