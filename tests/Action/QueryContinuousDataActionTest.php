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
use KaLehmann\WetterObservatoriumWeb\Normalizer\NormalizerInterface;
use KaLehmann\WetterObservatoriumWeb\Persistence\WeatherRepositoryInterface;
use PHPUnit\Framework\TestCase;

/**
 * Test cases for the QueryContinuousDataAction.
 */
class QueryContinuousDataActionTest extends TestCase
{
    /**
     * Check that the data measured in the last 24 hours for a single quantity at
     * a given location can be queried.
     */
    public function testQueryTheDataOfTheLast24HoursForSingleQuantity(): void
    {
        $normalizerMock = $this->createMock(NormalizerInterface::class);
        $normalizerMock->expects($this->exactly(6))
                       ->method('denormalizeValue')
                       ->withConsecutive(
                           ['temperature', 2],
                           ['temperature', 4],
                           ['temperature', 6],
                           ['temperature', 2],
                           ['temperature', 4],
                           ['temperature', 6],
                       )
                       ->willReturnOnConsecutiveCalls(
                           20,
                           40,
                           60,
                           20,
                           40,
                           60,
                       );
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
            $normalizerMock,
            $weatherRepository,
            'aquarium',
            'json',
            'temperature',
        );

        $this->assertEquals(
            200,
            $response->getStatusCode(),
        );
        $this->assertEquals(
            [
                [
                    'timestamp' => 1,
                    'temperature' => 20,
                ],
                [
                    'timestamp' => 3,
                    'temperature' => 40,
                ],
                [
                    'timestamp' => 5,
                    'temperature' => 60,
                ],
            ],
            json_decode((string)$response->getBody(), true),
        );

        $response = ($action)(
            $normalizerMock,
            $weatherRepository,
            'aquarium',
            'json',
            'temperature',
            '24h',
        );

        $this->assertEquals(
            200,
            $response->getStatusCode(),
        );
        $this->assertEquals(
            [
                [
                    'timestamp' => 1,
                    'temperature' => 20,
                ],
                [
                    'timestamp' => 3,
                    'temperature' => 40,
                ],
                [
                    'timestamp' => 5,
                    'temperature' => 60,
                ],
            ],
            json_decode((string)$response->getBody(), true),
        );
    }

    /**
     * Check that the data measured in the last 24 hours for a all quantities at
     * a given location can be queried.
     */
    public function testQueryTheDataOfTheLast24HoursForAllQuantities(): void
    {
        $normalizerMock = $this->createMock(NormalizerInterface::class);
        $normalizerMock->expects($this->exactly(6))
                       ->method('denormalizeValue')
                       ->withConsecutive(
                           ['humidity', 10],
                           ['humidity', 11],
                           ['humidity', 12],
                           ['temperature', 2],
                           ['temperature', 4],
                           ['temperature', 6],
                       )
                       ->willReturnOnConsecutiveCalls(
                           10,
                           11,
                           12,
                           20,
                           40,
                           60,
                       );
        $weatherRepository = $this->createMock(WeatherRepositoryInterface::class);
        $weatherRepository->expects($this->once())
                          ->method('queryQuantities')
                          ->with('outdoor')
                          ->willReturn(['humidity', 'temperature']);
        $weatherRepository->expects($this->exactly(2))
                          ->method('query24h')
                          ->withConsecutive(
                              ['outdoor', 'humidity'],
                              ['outdoor', 'temperature'],
                          )
                          ->willReturnOnConsecutiveCalls(
                              [
                                  1 => 10,
                                  2 => 11,
                                  3 => 12,
                              ],
                              [
                                  2 => 2,
                                  3 => 4,
                                  4 => 6,
                              ],
                          );

        $action = new QueryContinuousDataAction();
        $response = ($action)(
            $normalizerMock,
            $weatherRepository,
            'outdoor',
            'json',
        );

        $this->assertEquals(
            200,
            $response->getStatusCode(),
        );
        $this->assertEquals(
            [
                [
                    'timestamp' => 1,
                    'humidity' => 10,
                ],
                [
                    'timestamp' => 2,
                    'humidity' => 11,
                    'temperature' => 20,
                ],
                [
                    'timestamp' => 3,
                    'humidity' => 12,
                    'temperature' => 40,
                ],
                [
                    'timestamp' => 4,
                    'temperature' => 60,
                ],
            ],
            json_decode((string)$response->getBody(), true),
        );
    }

    /**
     * Check that the data measured in the last 31 days for a single quantity at
     * a given location can be queried.
     */
    public function testQueryTheDataOfTheLast31DaysForSingleQuantity(): void
    {
        $normalizerMock = $this->createMock(NormalizerInterface::class);
        $normalizerMock->expects($this->exactly(2))
                       ->method('denormalizeValue')
                       ->withConsecutive(
                           ['temperature', 11],
                           ['temperature', 21],
                       )
                       ->willReturnOnConsecutiveCalls(
                           110,
                           210,
                       );
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
            $normalizerMock,
            $weatherRepository,
            'aquarium',
            'json',
            'temperature',
            '31d'
        );

        $this->assertEquals(
            200,
            $response->getStatusCode(),
        );
        $this->assertEquals(
            [
                [
                    'timestamp' => 10,
                    'temperature' => 110,
                ],
                [
                    'timestamp' => 20,
                    'temperature' => 210,
                ]
            ],
            json_decode((string)$response->getBody(), true),
        );
    }

    /**
     * Check that the data measured in the last 31 days for a all quantities at
     * a given location can be queried.
     */
    public function testQueryTheDataOfTheLast31DaysForAllQuantities(): void
    {
        $normalizerMock = $this->createMock(NormalizerInterface::class);
        $normalizerMock->expects($this->exactly(6))
                       ->method('denormalizeValue')
                       ->withConsecutive(
                           ['humidity', 10],
                           ['humidity', 11],
                           ['humidity', 12],
                           ['temperature', 2],
                           ['temperature', 4],
                           ['temperature', 6],
                       )
                       ->willReturnOnConsecutiveCalls(
                           10,
                           11,
                           12,
                           20,
                           40,
                           60,
                       );
        $weatherRepository = $this->createMock(WeatherRepositoryInterface::class);
        $weatherRepository->expects($this->once())
                          ->method('queryQuantities')
                          ->with('outdoor')
                          ->willReturn(['humidity', 'temperature']);
        $weatherRepository->expects($this->exactly(2))
                          ->method('query31d')
                          ->withConsecutive(
                              ['outdoor', 'humidity'],
                              ['outdoor', 'temperature'],
                          )
                          ->willReturnOnConsecutiveCalls(
                              [
                                  1 => 10,
                                  2 => 11,
                                  3 => 12,
                              ],
                              [
                                  2 => 2,
                                  3 => 4,
                                  4 => 6,
                              ],
                          );

        $action = new QueryContinuousDataAction();
        $response = ($action)(
            $normalizerMock,
            $weatherRepository,
            'outdoor',
            'json',
            null,
            '31d',
        );

        $this->assertEquals(
            200,
            $response->getStatusCode(),
        );
        $this->assertEquals(
            [
                [
                    'timestamp' => 1,
                    'humidity' => 10,
                ],
                [
                    'timestamp' => 2,
                    'humidity' => 11,
                    'temperature' => 20,
                ],
                [
                    'timestamp' => 3,
                    'humidity' => 12,
                    'temperature' => 40,
                ],
                [
                    'timestamp' => 4,
                    'temperature' => 60,
                ],
            ],
            json_decode((string)$response->getBody(), true),
        );
    }
}
