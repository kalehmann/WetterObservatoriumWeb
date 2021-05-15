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

namespace KaLehmann\WetterObservatoriumWeb\Persistence;

use \DateTimeImmutable;
use \DateTimeInterface;

/**
 * Helper functions for condensating data on given intervals (60 minutes,
 * 24 hours) into single values.
 */
class WeatherCondensator
{
    public const SECONDS_PER_HOUR = 60 * 60;

    public const SECONDS_PER_DAY = self::SECONDS_PER_HOUR * 24;

    /**
     * Condensates the weather data in the buffer for the hour ending with
     * $endTimestamp.
     *
     * @param RingBuffer $buffer the buffer containing the data of the last
     *                           60 minutes.
     * @param int $endTimestamp the end of the hour that should be condensated.
     *
     * @return int the average value for the hour.
     */
    public static function condensateHour(
        RingBuffer $buffer,
        int $endTimestamp,
    ): int {
        $startTimestamp = $endTimestamp - self::SECONDS_PER_HOUR;

        return self::condensateData(
            $buffer,
            $startTimestamp,
            $endTimestamp,
        );
    }

    /**
     * Condensates the weather data in the buffer for the 24 hours ending with
     * $endTimestamp.
     *
     * @param RingBuffer $buffer the buffer containing the data of the last
     *                           24 hours.
     * @param int $endTimestamp the end of the 24 hours that should be
     *                          condensated.
     *
     * @return int the average value for the 24 hours.
     */
    public static function condensateDay(
        RingBuffer $buffer,
        int $endTimestamp,
    ): int {
        $startTimestamp = $endTimestamp - self::SECONDS_PER_DAY;

        return self::condensateData(
            $buffer,
            $startTimestamp,
            $endTimestamp,
        );
    }

    /**
     * Condensates the weather data in the buffer on a given interval.
     *
     * @param RingBuffer $buffer the buffer containing the data for the interval
     *                           that should be condensated.
     * @param int $startTimestamp the start of the interval that should be
     *                            condensated.
     * @param int $endTimestamp the end of the interval that should be
     *                          condensated.
     *
     * @return int the average value for the interval.
     */
    private static function condensateData(
        RingBuffer $buffer,
        int $startTimestamp,
        int $endTimestamp,
    ): int {
        if ($buffer->elementsPerEntry() !== 2) {
            throw new CondensationException(
                'Condensation is only supported for a buffer with two ' .
                'per entry. The first element should be a unix timestamp ' .
                'and the second entry the measured datum.',
            );
        }
        $interval = array_filter(
            iterator_to_array($buffer),
            fn (array $elements) => $startTimestamp < $elements[0]
                                    && $endTimestamp > $elements[0],
        );
        if (count($interval) === 0) {
            throw new CondensationException(
                'Cannot condensate the data between ' .
                date('Y-m-d H:i:s', $startTimestamp) . ' and '.
                date('Y-m-d H:i:s', $endTimestamp) . '. No data recorded in ' .
                'this interval.',
            );
        }

        return array_sum(
            array_map(
                fn (array $elements) => $elements[1],
                $interval,
            ),
        ) / count($interval);
    }
}
