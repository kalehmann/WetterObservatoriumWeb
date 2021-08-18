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

use DateTimeImmutable;
use KaLehmann\WetterObservatoriumWeb\Attribute\AuthorizationAttribute;
use KaLehmann\WetterObservatoriumWeb\Normalizer\NormalizerInterface;
use KaLehmann\WetterObservatoriumWeb\Persistence\WeatherRepositoryInterface;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Action for storing weather data.
 *
 * This action receives weather data from a client (usually an ESP8266),
 * verifies that the client is allowed to store data and persists it.
 */
#[AuthorizationAttribute]
class AddDataAction
{
    /**
     * Adds data for the specified location.
     */
    public function __invoke(
        NormalizerInterface $normalizer,
        Psr17Factory $psr17Factory,
        RequestInterface $request,
        WeatherRepositoryInterface $weatherRepository,
        string $location
    ): ResponseInterface {
        $payload = json_decode((string)$request->getBody(), true);
        if (!is_array($payload)) {
            return $psr17Factory->createResponse(400);
        }

        $now = new DateTimeImmutable();
        foreach ($payload as $quantity => $value) {
            $weatherRepository->persist(
                $location,
                $quantity,
                $normalizer->normalizeValue($quantity, $value),
                $now,
            );
        }

        return $psr17Factory->createResponse(200);
    }
}
