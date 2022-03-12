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
 * Action for query weather data for a fixed period of time.
 */
class QueryFixedDataAction
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
        int $year,
        ?int $month = null,
        ?string $quantity = null,
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
            $year,
            $month,
        );

        return $this->createResponse(
            $data,
            $format,
        );
    }

    /**
     * Query the weather data of $quantity measured at $location in the $month
     * or $year.
     *
     * @param NormalizerInterface $normalizer the normalizer for the stored data
     * @param WeatherRepositoryInterface $weatherRepository the repository with
     *                                                      the weather data.
     * @param string $location filter by the location where the data was
     *                         measured.
     * @param array<string> $quantities filter by the measured quantities
     * @param int $year filter by the year
     * @param int|null $month filter by the month. If the month is null, the data
     *                        for the whole year will be returned.
     * @return array<array<string, scalar>> an array of arrays with the data
     *                                      measured in the given interval
     *                                      grouped by timestamp
     */
    private function getData(
        NormalizerInterface $normalizer,
        WeatherRepositoryInterface $weatherRepository,
        string $location,
        array $quantities,
        int $year,
        ?int $month,
    ): array {
        $data = [];

        foreach ($quantities as $quantity) {
            $quantityData = [];
            if ($month !== null) {
                $quantityData = $weatherRepository->queryMonth(
                    $location,
                    $quantity,
                    $year,
                    $month,
                );
            } else {
                $quantityData = $weatherRepository->queryYear(
                    $location,
                    $quantity,
                    $year,
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
