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

namespace KaLehmann\WetterObservatoriumWebP\Tests\Persistence;

use ArrayIterator;
use KaLehmann\WetterObservatoriumWeb\Persistence\CondensationException;
use KaLehmann\WetterObservatoriumWeb\Persistence\WeatherCondensator;
use PHPUnit\Framework\TestCase;

/**
 * Test cases for the WeatherCondensator.
 */
class WeatherCondensatorTest extends TestCase
{
    /**
     * Check that a call to the condensateHour method with an iterator without
     * any data for the last hour fails with an exception.
     */
    public function testCondensateHourWithoutDataInTheLastHour(): void
    {
        $timestamp = time();
        $data = new ArrayIterator(
            [
                // Add two hour old data
                $timestamp - 60 * 60 * 2 => 12,
            ],
        );

        $this->expectException(CondensationException::class);
        $this->expectExceptionMessage(
            'No data recorded in this interval.',
        );

        WeatherCondensator::condensateHour($data, $timestamp);
    }

    /**
     * Check that data of the last 60 minutes can be condensated into a single
     * value.
     */
    public function testCondensateHour(): void
    {
        $timestamp = time();
        $data = new ArrayIterator(
            [
                // The following two entries are older than a hour
                $timestamp - 60 * 70 => 12,
                $timestamp - 60 * 80 => 99,
                // The next 3 entries are within the last hour
                $timestamp - 3000 => 1,
                $timestamp - 2000 => 4,
                $timestamp - 1000 => 1,
                // The next entry is in the future
                $timestamp + 1000 => 33,
            ],
        );

        $this->assertEquals(
            2,
            WeatherCondensator::condensateHour($data, $timestamp),
        );
    }

    /**
     * Check that the value returned by condensateHour
     */
    public function testCondensationAlwaysReturnsAnInteger(): void
    {
        $timestamp = time();
        $data = new ArrayIterator(
            [
                $timestamp - 2000 => 2,
                $timestamp - 1000 => 1,
            ],
        );
        // (1 + 2) / 2 = 1.5
        $this->assertEquals(
            2,
            WeatherCondensator::condensateHour($data, $timestamp),
        );
    }

    /**
     * Check that a call to the condensateDay method with an iterator without any
     * data for the last dday fails with an exception.
     */
    public function testCondensateDayWithoutDataInTheLastDay(): void
    {
        $timestamp = time();
        $data = new ArrayIterator(
            [
                // Add 30 hour old data
                $timestamp - 60 * 60 * 30 => 12,
            ],
        );

        $this->expectException(CondensationException::class);
        $this->expectExceptionMessage(
            'No data recorded in this interval.',
        );

        WeatherCondensator::condensateDay($data, $timestamp);
    }

    /**
     * Check that data of the last 24 hours can be condensated into a single
     * value.
     */
    public function testCondensateDay(): void
    {
        $timestamp = time();
        $data = new ArrayIterator(
            [
                // The following two entries are older than a day
                $timestamp - 60 * 60 * 36 => 12,
                $timestamp - 60 * 60 * 30 => 99,
                // The next 3 entries are within the last day
                $timestamp - 60 * 60 * 20 => 10,
                $timestamp - 60 * 60 * 16 => 20,
                $timestamp - 1000 => 30,
                // The next entry is in the future
                $timestamp + 1000 => 33,
            ],
        );

        $this->assertEquals(
            20,
            WeatherCondensator::condensateDay($data, $timestamp),
        );
    }
}
