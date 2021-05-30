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

namespace KaLehmann\WetterObservatoriumWeb\Action;

use KaLehmann\WetterObservatoriumWeb\Persistence\WeatherRepositoryInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Action for querying the weather data stored continuously in a period.
 */
class QueryContinuousDataAction
{
    use FormatTrait;

    /**
     * {@inheritdoc}
     */
    public function __invoke(
        WeatherRepositoryInterface $weatherRepository,
        string $location,
        string $quantity,
        string $format,
        ?string $timespan = null,
    ): ResponseInterface {
        $data = $this->getData(
            $weatherRepository,
            $location,
            $quantity,
            $timespan,
        );

        // Map the associative array to a list of tuples with the timestamp and
        // the value.
        array_walk(
            $data,
            fn(int &$value, int $timestamp) => $value = [$timestamp, $value],
        );

        return $this->createResponse(
            array_values($data),
            $format,
        );
    }

    /**
     * Query the weather data of $quantity measured at $location in $timespan.
     *
     * @param WeatherRepositoryInterface $weatherRepository the repository with
     *                                                      the weather data.
     * @param string $location filter by the location where the data was
     *                         measured.
     * @param string $quantity filter by the measured quantity.
     * @param string|null $timespan the timespan that should be queried.
     *                              If no value is given, the data of the last
     *                              24 hours will be returned.
     * @return array<int, int> an array with the timestamps as key and the
     *                         data measured in the $timespan as values.
     */
    private function getData(
        WeatherRepositoryInterface $weatherRepository,
        string $location,
        string $quantity,
        ?string $timespan = null,
    ): array {
        switch ($timespan) {
            case '31d':
                return $weatherRepository->query31d(
                    $location,
                    $quantity,
                );
            default:
                return $weatherRepository->query24h(
                    $location,
                    $quantity,
                );
        }
    }
}
