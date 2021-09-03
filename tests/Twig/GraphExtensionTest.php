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

namespace KaLehmann\WetterObservatoriumWeb\tests\Twig;

use KaLehmann\WetterObservatoriumWeb\Twig\GraphExtension;
use PHPUnit\Framework\TestCase;

/**
 * Test cases for the GraphExtension.
 */
class GraphExtensionTest extends TestCase
{
    /**
     * Check that a number can be mapped from one range to another.
     */
    public function testMapRange(): void
    {
        $this->assertEquals(
            50,
            GraphExtension::mapRange(5, 0, 10, 0, 100),
        );
        $this->assertEquals(
            20,
            GraphExtension::mapRange(10, 5, 30, 10, 60),
        );
    }

    /**
     * Check that mapping works with a zero width input range.
     */
    public function testMapRangeWithZeroWidthInputRange(): void
    {
        $this->assertEquals(
            40,
            GraphExtension::mapRange(1, 1, 1, 0, 80),
        );
    }

    /**
     * Check that the maxTime method returns the largest timestamp from the
     * data.
     */
    public function testMaxTime(): void
    {
        $data = [
            1 => 6,
            2 => 7,
            3 => 8,
        ];
        $this->assertEquals(
            3,
            GraphExtension::maxTime($data),
        );
    }

    /**
     * Check that the maxTime function returns the current timestamp if called
     * with an empty dataset.
     */
    public function tesMaxTimeWithEmptyDataSet(): void
    {
        $timeBefore = time();
        $timeMax = GraphExtension::maxTime([]);
        $timeAfter = time();

        $this->assertTrue(
            $timeBefore <= $timeMax && $timeMax <= $timeAfter,
        );
    }

    /**
     * Check that the minTime method returns the largest timestamp from the
     * data.
     */
    public function testMinTime(): void
    {
        $data = [
            1 => 6,
            2 => 7,
            3 => 8,
        ];
        $this->assertEquals(
            1,
            GraphExtension::minTime($data),
        );
    }

    /**
     * Check that the minTime function returns the current timestamp minus one
     * day if called with an empty dataset.
     */
    public function testMinTimeWithEmptyDataSet(): void
    {
        $timeBefore = time() - 3600 * 24;
        $timeMin = GraphExtension::minTime([]);
        $timeAfter = time() - 3600 * 24;

        $this->assertTrue(
            $timeBefore <= $timeMin && $timeMin <= $timeAfter,
        );
    }
}
