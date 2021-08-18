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
class Normalizer implements NormalizerInterface
{
    /**
     * @var array<QuantityNormalizerInterface>
     */
    private array $quantityNormalizers = [];

    /**
     * {@inheritdoc}
     */
    public function setQuantityNormalizers(array $normalizers): void
    {
        $this->quantityNormalizers = $normalizers;
    }

    /**
     * {@inheritdoc}
     */
    public function normalizeValue(string $quantity, float|int $value): int
    {
        foreach ($this->quantityNormalizers as $normalizer) {
            if ($normalizer->supportsQuantity($quantity)) {
                return $normalizer->normalizeValue($value);
            }
        }

        return (int) $value;
    }

    /**
     * {@inheritdoc}
     */
    public function denormalizeValue(string $quantity, int $value): float|int
    {
        foreach ($this->quantityNormalizers as $normalizer) {
            if ($normalizer->supportsQuantity($quantity)) {
                return $normalizer->denormalizeValue($value);
            }
        }

        return $value;
    }
}
