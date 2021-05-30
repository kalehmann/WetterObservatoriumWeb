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

use \Traversable;

/**
 * Helper functions for condensating data on given intervals (60 minutes,
 * 24 hours) into single values.
 */
class WeatherCondensator
{
    public const SECONDS_PER_HOUR = 60 * 60;

    public const SECONDS_PER_DAY = self::SECONDS_PER_HOUR * 24;

    /**
     * Condensates the weather data for the hour ending with $endTimestamp.
     *
     * @param Traversable<int, int> $data the iterable containing the data of the
     *                                    last 60 minutes with the timestamps as
     *                                    keys and the measured data as values.
     * @param int $endTimestamp the end of the hour that should be condensated.
     *
     * @return int the average value for the hour.
     */
    public static function condensateHour(
        Traversable $data,
        int $endTimestamp,
    ): int {
        $startTimestamp = $endTimestamp - self::SECONDS_PER_HOUR;

        return self::condensateData(
            $data,
            $startTimestamp,
            $endTimestamp,
        );
    }

    /**
     * Condensates the weather data for the 24 hours ending with $endTimestamp.
     *
     * @param Traversable<int, int> $data the iterable containing the data of the
     *                                    last 24 hours with the timestamps as
     *                                    keys and the measured data as values.
     * @param int $endTimestamp the end of the 24 hours that should be
     *                          condensated.
     *
     * @return int the average value for the 24 hours.
     */
    public static function condensateDay(
        Traversable $data,
        int $endTimestamp,
    ): int {
        $startTimestamp = $endTimestamp - self::SECONDS_PER_DAY;

        return self::condensateData(
            $data,
            $startTimestamp,
            $endTimestamp,
        );
    }

    /**
     * Condensates the weather data in the iterator on a given interval.
     *
     * @param Traversable<int, int> $data the iterable containing the data of the
     *                                    interval that should be condensated
     *                                    with the timestamps as keys and the
     *                                    measured data as values.
     * @param int $startTimestamp the start of the interval that should be
     *                            condensated.
     * @param int $endTimestamp the end of the interval that should be
     *                          condensated.
     *
     * @return int the average value for the interval.
     */
    private static function condensateData(
        Traversable $data,
        int $startTimestamp,
        int $endTimestamp,
    ): int {
        $interval = array_filter(
            iterator_to_array($data),
            fn (int $value, int $timestamp) => $startTimestamp < $timestamp
                                               && $endTimestamp > $timestamp,
            ARRAY_FILTER_USE_BOTH,
        );
        if (count($interval) === 0) {
            throw new CondensationException(
                'Cannot condensate the data between ' .
                date('Y-m-d H:i:s', $startTimestamp) . ' and '.
                date('Y-m-d H:i:s', $endTimestamp) . '. No data recorded in ' .
                'this interval.',
            );
        }

        $avg = array_sum($interval) / count($interval);

        return (int)round($avg);
    }
}
