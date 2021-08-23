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
use KaLehmann\WetterObservatoriumWeb\Normalizer\NormalizerInterface;
use Nyholm\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use RunTimeException;
use Twig\Environment;

/**
 * Action for generating graphs from the measured data.
 */
class GraphAction
{
    /**
     * Loads the data according to the given time period and quantities
     * and plots a nice graph as scalable vector graphic.
     *
     * @param WeatherRepositoryInterface $weatherRepository the repository to
     *                                                      load the data from.
     * @param string $location the location where the data was measured.
     * @param null|string $quantity the measured quantity. If the quantity
     *                              equals null, the data for all quantities
     *                              measured at the location will be returned.
     * @param null|string $timespan the timespan for which the data should be
     *                              returned. Allowed values are '24h' and
     *                              '31d' for the data of the last 24 hours or
     *                              the last 31 days. If the $timespan equals
     *                              null, the parameters $year (and $month) will
     *                              be used to select the data.
     * @param null|int $year the year in which the data was measured. Can be
     *                       further limited by specifying $month. If $year
     *                       equals null, $timespan should be provided.
     * @param null|int $month the optional month of $year in which the data was
     *                        measured.
     * @return ResponseInterface the response with the svg data.
     */
    public function __invoke(
        Environment $twig,
        NormalizerInterface $normalizer,
        WeatherRepositoryInterface $weatherRepository,
        string $location,
        ?string $quantity = null,
        ?string $timespan = null,
        ?int $year = null,
        ?int $month = null,
    ): ResponseInterface {
        $data = $this->getData(
            $weatherRepository,
            $location,
            $quantity,
            $timespan,
            $year,
            $month,
        );
        array_walk(
            $data,
            function (array &$values, string $quantity) use ($normalizer) {
                $values = array_map(
                    fn (int $measuredData) => $normalizer->denormalizeValue(
                        $quantity,
                        $measuredData,
                    ),
                    $values,
                );
            },
        );
        $headers = [
            'Content-Type' => 'image/svg+xml',
        ];

        if ($quantity) {
            $templateName = 'graphs/' . $quantity . '.svg.twig';
            if ($twig->getLoader()->exists($templateName)) {
                return new Response(
                    body: $twig->render(
                        $templateName,
                        [
                            'data' => $data,
                            'location' => $location,
                        ],
                    ),
                    headers: $headers,
                    status: 200,
                );
            }
        }

        return new Response(
            body: '',
            headers: $headers,
            status: 200,
        );
    }

    /**
     * Loads the data according to the given time period and quantities.
     *
     * @param WeatherRepositoryInterface $weatherRepository the repository to
     *                                                      load the data from.
     * @param string $location the location where the data was measured.
     * @param null|string $quantity the measured quantity. If the quantity
     *                              equals null, the data for all quantities
     *                              measured at the location will be returned.
     * @param null|string $timespan the timespan for which the data should be
     *                              returned. Allowed values are '24h' and
     *                              '31d' for the data of the last 24 hours or
     *                              the last 31 days. If the $timespan equals
     *                              null, the parameters $year (and $month) will
     *                              be used to select the data.
     * @param null|int $year the year in which the data was measured. Can be
     *                       further limited by specifying $month. If $year
     *                       equals null, $timespan should be provided.
     * @param null|int $month the optional month of $year in which the data was
     *                        measured.
     * @return array<string, array<int, int>> a hashmap with the quantities as
     *                                        keys. The values are hashmaps
     *                                        themselfes, with the timestamps
     *                                        when the data was measured as keys
     *                                        and the measured data as values.
     */
    private function getData(
        WeatherRepositoryInterface $weatherRepository,
        string $location,
        ?string $quantity = null,
        ?string $timespan = null,
        ?int $year = null,
        ?int $month = null,
    ): array {
        $data = [];
        /** @var array<string> */
        $quantities = [
            $quantity,
        ];
        if (null === $quantity) {
            $quantities = $weatherRepository->queryQuantities($location);
        }

        if ($year) {
            if ($month) {
                foreach ($quantities as $q) {
                    $data[$quantity] = $weatherRepository->queryMonth(
                        $location,
                        $q,
                        $year,
                        $month,
                    );
                }

                return $data;
            }

            foreach ($quantities as $q) {
                $data[$quantity] = $weatherRepository->queryYear(
                    $location,
                    $q,
                    $year,
                );
            }

            return $data;
        }

        if ($timespan === '31d') {
            foreach ($quantities as $q) {
                $data[$quantity] = $weatherRepository->query31d(
                    $location,
                    $q,
                );
            }

            return $data;
        }

        foreach ($quantities as $q) {
            $data[$quantity] = $weatherRepository->query24h(
                $location,
                $q,
            );
        }

        return $data;
    }
}
