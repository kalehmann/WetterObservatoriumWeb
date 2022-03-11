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

use KaLehmann\WetterObservatoriumWeb\Normalizer\RSSINormalizer;
use PHPUnit\Framework\TestCase;

/**
 * Test cases for the RSSINormalizer
 */
class RSSINormalizerTest extends TestCase
{
    /**
     * Check that the RSSINormalizer only supports the quantity
     * `rssi`.
     */
    public function testSupports(): void
    {
        $normalizer = new RSSINormalizer();
        $this->assertTrue(
            $normalizer->supportsQuantity('rssi'),
        );
        $this->assertFalse(
            $normalizer->supportsQuantity('humidity'),
        );
    }

    /**
     * Check that a measured (negative) dbm can be converted
     * into an unsigned integer.
     */
    public function testNormalizeValue(): void
    {
        $normalizer = new RSSINormalizer();

        $this->assertEquals(
            33,
            $normalizer->normalizeValue(-33),
        );
    }

    /**
     * Check that a normalized value can be converted back into the
     * measured value.
     */
    public function testDenormalizeValue(): void
    {
        $normalizer = new RSSINormalizer();
        $this->assertEquals(
            -80,
            $normalizer->denormalizeValue(80),
        );
    }
}
