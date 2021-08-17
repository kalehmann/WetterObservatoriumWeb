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
 * Converts incoming data into unsigned integers and back for safe persisting.
 */
interface NormalizerInterface
{
    /**
     * Sets the normalizers for the different quantities supported by the
     * application.
     *
     * @param array<QuantityNormalizerInterface> $normalizers
     */
    public function setQuantityNormalizers(array $normalizers): void;

    /**
     * Converts a measured value of a quantity into an unsigned integer for safe
     * persisting.
     *
     * @param string $quantity the name of the measured quantity
     * @param float|int $value the measured value
     * @return int the unsigned integer the should be persisted
     */
    public function normalizeValue(string $quantity, float|int $value): int;

    /**
     * Converts a normalized value back into the "real"/measured value for a
     * quantity.
     *
     * @param string $quantity the name of the quantity
     * @param int $value the normalized value (an unsigned integer)
     * @return float|int the real/measured value
     */
    public function denormalizeValue(string $quantity, int $value): float|int;
}
