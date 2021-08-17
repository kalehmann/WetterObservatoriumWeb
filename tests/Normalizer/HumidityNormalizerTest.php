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

use KaLehmann\WetterObservatoriumWeb\Normalizer\HumidityNormalizer;
use PHPUnit\Framework\TestCase;

/**
 * Test cases for the HumidityNormalizer
 */
class HumidityNormalizerTest extends TestCase
{
    /**
     * Check that the HumidityNormalizer only supports the quantity
     * `humidity`.
     */
    public function testSupports(): void
    {
        $normalizer = new HumidityNormalizer();
        $this->assertTrue(
            $normalizer->supportsQuantity('humidity'),
        );
        $this->assertFalse(
            $normalizer->supportsQuantity('temperature'),
        );
    }

    /**
     * Check that a measured humidity value (float) can be converted into an
     * unsigned integer.
     */
    public function testNormalizeValue(): void
    {
        $normalizer = new HumidityNormalizer();

        $this->assertEquals(
            0,
            $normalizer->normalizeValue(0),
        );

        $this->assertEquals(
            816,
            $normalizer->normalizeValue(81.56),
        );
    }

    /**
     * Check that a normalized humidity value can be converted back into the
     * measured value.
     */
    public function testDenormalizeValue(): void
    {
        $normalizer = new HumidityNormalizer();
        $this->assertEqualsWithDelta(
            0,
            $normalizer->denormalizeValue(0),
            0.1,
        );
        $this->assertEqualsWithDelta(
            99.7,
            $normalizer->denormalizeValue(997),
            0.1,
        );
    }
}
