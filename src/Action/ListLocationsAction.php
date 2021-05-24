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
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Action for storing weather date.
 *
 * This action receives weatehr data from a client (usually an ESP8266),
 * verifies that the client is allowed to store data and persists it.
 */
class ListLocationsAction
{
    use FormatTrait;

    /**
     * Adds data for the specified locatiion.
     */
    public function __invoke(
        Psr17Factory $psr17Factory,
        RequestInterface $request,
        WeatherRepository $weatherRepository,
        string $format
    ): ResponseInterface {
        return $this->createResponse(
            $weatherRepository->queryLocations(),
            $format,
        );
    }
}
