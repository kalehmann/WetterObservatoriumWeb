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

use KaLehmann\WetterObservatoriumWeb\Normalizer\TemperatureNormalizer;
use PHPUnit\Framework\TestCase;

/**
 * Test cases for the TemperatureNormalizer
 */
class TemperatureNormalizerTest extends TestCase
{
    /**
     * Check that the TemperatureNormalizer only supports the quantity
     * `temperature`.
     */
    public function testSupports(): void
    {
        $normalizer = new TemperatureNormalizer();
        $this->assertTrue(
            $normalizer->supportsQuantity('temperature'),
        );
        $this->assertFalse(
            $normalizer->supportsQuantity('humidity'),
        );
    }

    /**
     * Check that a measured temperature value (float, Celsius) can be converted
     * into an unsigned integer.
     */
    public function testNormalizeValue(): void
    {
        $normalizer = new TemperatureNormalizer();

        // (0 + 273.15) * 10
        $this->assertEquals(
            2732,
            $normalizer->normalizeValue(0),
        );

        // (22.5 + 273.15) * 10)
        $this->assertEquals(
            2957,
            $normalizer->normalizeValue(22.5),
        );
    }

    /**
     * Check that a normalized temperature value can be converted back into the
     * measured value.
     */
    public function testDenormalizeValue(): void
    {
        $normalizer = new TemperatureNormalizer();
        $this->assertEqualsWithDelta(
            0,
            $normalizer->denormalizeValue(2732),
            0.1,
        );
        $this->assertEqualsWithDelta(
            22.5,
            $normalizer->denormalizeValue(2957),
            0.15,
        );
    }
}
