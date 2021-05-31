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

namespace KaLehmann\WetterObservatoriumWeb\tests\Action;

use KaLehmann\WetterObservatoriumWeb\Action\QueryContinuousDataAction;
use KaLehmann\WetterObservatoriumWeb\Persistence\WeatherRepositoryInterface;
use PHPUnit\Framework\TestCase;

/**
 * Test cases for the QueryContinuousDataAction.
 */
class QueryContinuousDataActionTest extends TestCase
{
    /**
     * Check that the data measured in the last 24 hours can be queried.
     */
    public function testQueryTheDataOfTheLast24Hours(): void
    {
        $weatherRepository = $this->createMock(WeatherRepositoryInterface::class);
        $weatherRepository->expects($this->exactly(2))
                          ->method('query24h')
                          ->with('aquarium', 'temperature')
                          ->willReturn(
                              [
                                  1 => 2,
                                  3 => 4,
                                  5 => 6,
                              ]
                          );

        $action = new QueryContinuousDataAction();
        $response = ($action)(
            $weatherRepository,
            'aquarium',
            'temperature',
            'json',
        );

        $this->assertEquals(
            200,
            $response->getStatusCode(),
        );
        $this->assertEquals(
            [[1, 2], [3, 4], [5, 6]],
            json_decode((string)$response->getBody()),
        );

        $response = ($action)(
            $weatherRepository,
            'aquarium',
            'temperature',
            'json',
            '24h',
        );

        $this->assertEquals(
            200,
            $response->getStatusCode(),
        );
        $this->assertEquals(
            [[1, 2], [3, 4], [5, 6]],
            json_decode((string)$response->getBody()),
        );
    }

    /**
     * Check that the data measured in the last 31 days can be queried.
     */
    public function testQueryTheDataOfTheLast31Days(): void
    {
        $weatherRepository = $this->createMock(WeatherRepositoryInterface::class);
        $weatherRepository->expects($this->once())
                          ->method('query31d')
                          ->with('aquarium', 'temperature')
                          ->willReturn(
                              [
                                  10 => 11,
                                  20 => 21,
                              ]
                          );

        $action = new QueryContinuousDataAction();
        $response = ($action)(
            $weatherRepository,
            'aquarium',
            'temperature',
            'json',
            '31d'
        );

        $this->assertEquals(
            200,
            $response->getStatusCode(),
        );
        $this->assertEquals(
            [[10, 11], [20, 21]],
            json_decode((string)$response->getBody()),
        );
    }
}
