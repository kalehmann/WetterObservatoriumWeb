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

use KaLehmann\WetterObservatoriumWeb\Normalizer\NormalizerInterface;
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
        NormalizerInterface $normalizer,
        WeatherRepositoryInterface $weatherRepository,
        string $location,
        string $format,
        ?string $quantity = null,
        ?string $timespan = null,
    ): ResponseInterface {
        if ($quantity) {
            $quantities = [$quantity];
        } else {
            $quantities = $weatherRepository->queryQuantities($location);
        }
        $data = $this->getData(
            $normalizer,
            $weatherRepository,
            $location,
            $quantities,
            $timespan,
        );

        return $this->createResponse(
            $data,
            $format,
        );
    }

    /**
     * Query the weather data of $quantity measured at $location in $timespan.
     *
     * @param NormalizerInterface $normalizer the normalizer for the stored data
     * @param WeatherRepositoryInterface $weatherRepository the repository with
     *                                                      the weather data.
     * @param string $location filter by the location where the data was
     *                         measured.
     * @param array<string> $quantities filter by the measured quantities
     * @param string|null $timespan the timespan that should be queried.
     *                              If no value is given, the data of the last
     *                              24 hours will be returned.
     * @return array<array<string, scalar>> an array of arrays with the data
     *                                      measured in $timespan grouped by
     *                                      timestamp
     */
    private function getData(
        NormalizerInterface $normalizer,
        WeatherRepositoryInterface $weatherRepository,
        string $location,
        array $quantities,
        ?string $timespan = null,
    ): array {
        $data = [];

        foreach ($quantities as $quantity) {
            $quantityData = [];
            switch ($timespan) {
                case '31d':
                    $quantityData = $weatherRepository->query31d(
                        $location,
                        $quantity,
                    );
                    break;
                default:
                    $quantityData = $weatherRepository->query24h(
                        $location,
                        $quantity,
                    );
            }
            foreach ($quantityData as $timestamp => $value) {
                if (!($data[$timestamp] ?? null)) {
                    $data[$timestamp] = [
                        'timestamp' => $timestamp,
                    ];
                }
                $data[$timestamp][$quantity] = $normalizer->denormalizeValue(
                    $quantity,
                    $value,
                );
            }
        }

        return array_values($data);
    }
}
