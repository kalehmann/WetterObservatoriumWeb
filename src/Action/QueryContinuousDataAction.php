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

use KaLehmann\WetterObservatoriumWeb\Persistence\WeatherRepository;
use Psr\Http\Message\ResponseInterface;

/**
 * Action for query weather data stored in ring buffers.
 */
class QueryContinuousDataAction
{
    use FormatTrait;

    /**
     * {@inheritdoc}
     */
    public function __invoke(
        WeatherRepository $weatherRepository,
        string $location,
        string $quantity,
        string $format,
        ?string $timespan = null,
    ): ResponseInterface {
        switch ($timespan) {
            case '31d':
                $data = $weatherRepository->query31d(
                    $location,
                    $quantity,
                );
            default:
                $data = $weatherRepository->query24h(
                    $location,
                    $quantity,
                );
        }
        return $this->createResponse($data, $format);
    }
}
