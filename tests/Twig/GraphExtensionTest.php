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

    /**
     * Check that the yLowerLimit method returns a value between the minimum of
     * the provided data and the minimum minus the range of the data.
     */
    public function testYLowerLimit(): void
    {
        $upper = 10;
        $lower = 5;
        $data = range($lower, $upper);
        $range = $upper - $lower;
        $lowerLimit = GraphExtension::yLowerLimit($data);
        $this->assertLessThanOrEqual(
            $lower,
            $lowerLimit,
        );
        $this->assertGreaterThanOrEqual(
            $lower - $range,
            $lowerLimit,
        );
    }

    /**
     * Check that the yLowerLimit method returns null if called with empty data.
     */
    public function testYLowerLimitWithEmptyData(): void
    {
        $this->assertNull(
            GraphExtension::yLowerLimit([]),
        );
    }

    /**
     * Check that the yUpperLimit method returns a value between the maximum of
     * the provided data and the maximum plus the range of the data.
     */
    public function testYUpperLimit(): void
    {
        $upper = 10;
        $lower = 5;
        $data = range($lower, $upper);
        $range = $upper - $lower;
        $upperLimit = GraphExtension::yUpperLimit($data);
        $this->assertGreaterThanOrEqual(
            $upper,
            $upperLimit,
        );
        $this->assertLessThanOrEqual(
            $upper + $range,
            $upperLimit,
        );
    }

    /**
     * Check that the yUpperLimit method returns null if called with empty data.
     */
    public function testYUpperLimitWithEmptyData(): void
    {
        $this->assertNull(
            GraphExtension::yUpperLimit([]),
        );
    }

    /**
     * Check the the yTicks method returns the lower/upper bound for an empty
     * range.
     */
    public function testYTicksWithEmptyRange(): void
    {
        $lower = $upper = 5;
        $this->assertEquals(
            [$lower],
            GraphExtension::yTicks($lower, $upper),
        );
    }

    /**
     * Check that the yTicks method uses a resolution of one for an input range
     * below the tick limit.
     */
    public function testYTicksWithRangeBelowTickLimit(): void
    {
        $lower = 20;
        $upper = $lower + GraphExtension::TICK_LIMIT - 1;

        $this->assertEquals(
            range(
                $lower,
                $upper,
                1,
            ),
            GraphExtension::yTicks($lower, $upper),
        );
    }

    /**
     * Check that the yTicks method uses an appropriate resolution for a large
     * input range.
     */
    public function testYTicksWithLargeRange(): void
    {
        $lower = 100;
        $upper = $lower + (GraphExtension::TICK_LIMIT - 1) * 50;

        $this->assertEquals(
            range(
                $lower,
                $upper,
                50,
            ),
            GraphExtension::yTicks($lower, $upper),
        );
    }
}
