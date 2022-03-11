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

use KaLehmann\WetterObservatoriumWeb\Normalizer\SunIntensityNormalizer;
use PHPUnit\Framework\TestCase;

/**
 * Test cases for the SunIntensityNormalizer
 */
class SunIntensityNormalizerTest extends TestCase
{
    /**
     * Check that the PressureNormalizer only supports the quantity
     * `sun`.
     */
    public function testSupports(): void
    {
        $normalizer = new SunIntensityNormalizer();
        $this->assertTrue(
            $normalizer->supportsQuantity('sun'),
        );
        $this->assertFalse(
            $normalizer->supportsQuantity('pressure'),
        );
    }

    /**
     * Check that a measured pressure value (float) can be converted
     * into an unsigned integer.
     */
    public function testNormalizeValue(): void
    {
        $normalizer = new SunIntensityNormalizer();

        $this->assertEquals(
            644,
            $normalizer->normalizeValue(64.4),
        );
    }

    /**
     * Check that a normalized sun intensity value can be converted back into
     * the measured value.
     */
    public function testDenormalizeValue(): void
    {
        $normalizer = new SunIntensityNormalizer();
        $this->assertEquals(
            42.2,
            $normalizer->denormalizeValue(422),
        );
    }
}
