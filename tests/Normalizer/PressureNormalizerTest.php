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

namespace KaLehmann\WetterObservatoriumWeb\tests\Normalizer;

use KaLehmann\WetterObservatoriumWeb\Normalizer\PressureNormalizer;
use PHPUnit\Framework\TestCase;

/**
 * Test cases for the PressureNormalizer
 */
class PressureNormalizerTest extends TestCase
{
    /**
     * Check that the PressureNormalizer only supports the quantity
     * `pressure`.
     */
    public function testSupports(): void
    {
        $normalizer = new PressureNormalizer();
        $this->assertTrue(
            $normalizer->supportsQuantity('pressure'),
        );
        $this->assertFalse(
            $normalizer->supportsQuantity('humidity'),
        );
    }

    /**
     * Check that a measured pressure value (float) can be converted
     * into an unsigned integer.
     */
    public function testNormalizeValue(): void
    {
        $normalizer = new PressureNormalizer();

        // (0 + 273.15) * 10
        $this->assertEquals(
            1013,
            $normalizer->normalizeValue(1013.25),
        );

        // (22.5 + 273.15) * 10)
        $this->assertEquals(
            1000,
            $normalizer->normalizeValue(999.99),
        );
    }

    /**
     * Check that a normalized pressure value can be converted back into the
     * measured value.
     */
    public function testDenormalizeValue(): void
    {
        $normalizer = new PressureNormalizer();
        $this->assertEquals(
            42,
            $normalizer->denormalizeValue(42),
        );
    }
}
