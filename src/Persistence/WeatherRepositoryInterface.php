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

use DateTimeImmutable;

/**
 * Repository for all weather related data.
 */
interface WeatherRepositoryInterface
{
    /**
     * Persist a value for a specific quantity measured on a specific location at
     * a specific time.
     *
     * @param string $location the location where the data was measured.
     * @param string $quantity the measured quantity (e.g. `temperature`
     *                         or `humidity`).
     * @param int $value the measured value as integer. If for example the
     *                   temperature is measured with one decimal point, the
     *                   value should be multiplied by 100 before it is persisted
     *                   to get an integer.
     * @param DateTimeImmutable $timestamp the time when the value was measured.
     */
    public function persist(
        string $location,
        string $quantity,
        int $value,
        DateTimeImmutable $timestamp,
    ): void;

    /**
     * Returns the all the data collected for a quantity on a specific location
     * in the last 24 hours.
     *
     * @param string $location the location where the data should be queried
     *                         for.
     * @param string $quantity the collected quantity.
     * @return array<int, int> the data collected in the last 24 hours on the
     *                         location as array with the timestamps as keys and
     *                         the measured data as values.
     */
    public function query24h(
        string $location,
        string $quantity,
    ): array;

    /**
     * Returns the all the data collected for a quantity on a specific location
     * in the last 31 days.
     *
     * @param string $location the location where the data should be queried
     *                         for.
     * @param string $quantity the collected quantity.
     * @return array<int, int> the data collected in the last 31 days on the
     *                         location as array with the timestamps as keys and
     *                         the measured data as values.
     */
    public function query31d(
        string $location,
        string $quantity,
    ): array;

    /**
     * Returns all locations where data was previously measured.
     *
     * @return array<int, string> the array of location names where data was
     *                            measured.
     */
    public function queryLocations(): array;

    /**
     * Returns the all the data collected for a quantity on a specific location
     * in the given month of the given year.
     *
     * @param string $location the location where the data should be queried
     *                         for.
     * @param string $quantity the collected quantity.
     * @param int $year the year the data was collected in.
     * @param int $month the month the data was collected in.
     * @return array<int, int> the data collected in the given month on the
     *                         location as array with the timestamps as keys and
     *                         the measured data as values.
     */
    public function queryMonth(
        string $location,
        string $quantity,
        int $year,
        int $month,
    ): array;

    /**
     * Queries all quantities ever measured at a location.
     *
     * @param string $location the location to query quantities for.
     * @return array<int, string> the array with all quantities measured at
     *                            the location.
     */
    public function queryQuantities(string $location): array;

    /**
     * Returns the all the data collected for a quantity on a specific location
     * in the given year.
     *
     * @param string $location the location where the data should be queried
     *                         for.
     * @param string $quantity the collected quantity.
     * @param int $year the year the data was collected in.
     * @return array<int, int> the data collected in the given year on the
     *                         location as array with the timestamps as keys and
     *                         the measured data as values.
     */
    public function queryYear(
        string $location,
        string $quantity,
        int $year
    ): array;
}
