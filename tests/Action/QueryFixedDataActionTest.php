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

use KaLehmann\WetterObservatoriumWeb\Action\QueryFixedDataAction;
use KaLehmann\WetterObservatoriumWeb\Normalizer\NormalizerInterface;
use KaLehmann\WetterObservatoriumWeb\Persistence\WeatherRepositoryInterface;
use PHPUnit\Framework\TestCase;

/**
 * Test cases for the QueryFixedDataAction.
 */
class QueryFixedDataActionTest extends TestCase
{
    /**
     * Check that the data measured for a single quantity at a given location in
     * a given year and month can be queried.
     */
    public function testQueryTheDataOfAMonthForSingleQuantity(): void
    {
        $normalizerMock = $this->createMock(NormalizerInterface::class);
        $normalizerMock->expects($this->exactly(2))
                       ->method('denormalizeValue')
                       ->withConsecutive(
                           ['temperature', 1],
                           ['temperature', 2],
                       )
                       ->willReturnOnConsecutiveCalls(
                           10,
                           20,
                       );
        $weatherRepository = $this->createMock(WeatherRepositoryInterface::class);
        $weatherRepository->expects($this->once())
                          ->method('queryMonth')
                          ->with('aquarium', 'temperature', 2021, 05)
                          ->willReturn(
                              [
                                  1 => 1,
                                  2 => 2,
                              ],
                          );

        $action = new QueryFixedDataAction();
        $response = ($action)(
            $normalizerMock,
            $weatherRepository,
            'aquarium',
            'json',
            2021,
            05,
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
                    'temperature' => 10,
                ],
                [
                    'timestamp' => 2,
                    'temperature' => 20,
                ],
            ],
            json_decode((string)$response->getBody(), true),
        );
    }

    /**
     * Check that the data measured for all quantities at a given location in
     * a given year and month can be queried.
     */
    public function testQueryTheDataOfAMonthForAllQuantities(): void
    {
        $normalizerMock = $this->createMock(NormalizerInterface::class);
        $normalizerMock->expects($this->exactly(4))
                       ->method('denormalizeValue')
                       ->withConsecutive(
                           ['humidity', 1],
                           ['humidity', 2],
                           ['temperature', 2],
                           ['temperature', 3],
                       )
                       ->willReturnOnConsecutiveCalls(
                           1,
                           2,
                           20,
                           30,
                       );
        $weatherRepository = $this->createMock(WeatherRepositoryInterface::class);
        $weatherRepository->expects($this->once())
                          ->method('queryQuantities')
                          ->with('outdoor')
                          ->willReturn(['humidity', 'temperature']);
        $weatherRepository->expects($this->exactly(2))
                          ->method('queryMonth')
                          ->withConsecutive(
                              ['outdoor', 'humidity', 2021, 05],
                              ['outdoor', 'temperature', 2021, 05],
                          )
                          ->willReturnOnConsecutiveCalls(
                              [
                                  1 => 1,
                                  2 => 2,
                              ],
                              [
                                  2 => 2,
                                  3 => 3,
                              ],
                          );

        $action = new QueryFixedDataAction();
        $response = ($action)(
            $normalizerMock,
            $weatherRepository,
            'outdoor',
            'json',
            2021,
            05,
        );
        $this->assertEquals(
            200,
            $response->getStatusCode(),
        );
        $this->assertEquals(
            [
                [
                    'timestamp' => 1,
                    'humidity' => 1,
                ],
                [
                    'timestamp' => 2,
                    'humidity' => 2,
                    'temperature' => 20,
                ],
                [
                    'timestamp' => 3,
                    'temperature' => 30,
                ],
            ],
            json_decode((string)$response->getBody(), true),
        );
    }

    /**
     * Check that the data measured for a single quantity at a given location in
     * a given year can be queried.
     */
    public function testQueryTheDataOfAYearForSingleQuantity(): void
    {
        $normalizerMock = $this->createMock(NormalizerInterface::class);
        $normalizerMock->expects($this->exactly(2))
                       ->method('denormalizeValue')
                       ->withConsecutive(
                           ['temperature', 2],
                           ['temperature', 4],
                       )
                       ->willReturnOnConsecutiveCalls(
                           20,
                           40,
                       );
        $weatherRepository = $this->createMock(WeatherRepositoryInterface::class);
        $weatherRepository->expects($this->once())
                          ->method('queryYear')
                          ->with('aquarium', 'temperature', 2021)
                          ->willReturn(
                              [
                                  1 => 2,
                                  3 => 4,
                              ],
                          );

        $action = new QueryFixedDataAction();
        $response = ($action)(
            $normalizerMock,
            $weatherRepository,
            'aquarium',
            'json',
            2021,
            null,
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
            ],
            json_decode((string)$response->getBody(), true),
        );
    }

    /**
     * Check that the data measured for all quantities at a given location in
     * a given year can be queried.
     */
    public function testQueryTheDataOfAYearForAllQuantities(): void
    {
        $normalizerMock = $this->createMock(NormalizerInterface::class);
        $normalizerMock->expects($this->exactly(4))
                       ->method('denormalizeValue')
                       ->withConsecutive(
                           ['humidity', 1],
                           ['humidity', 2],
                           ['temperature', 2],
                           ['temperature', 3],
                       )
                       ->willReturnOnConsecutiveCalls(
                           1,
                           2,
                           20,
                           30,
                       );
        $weatherRepository = $this->createMock(WeatherRepositoryInterface::class);
        $weatherRepository->expects($this->once())
                          ->method('queryQuantities')
                          ->with('outdoor')
                          ->willReturn(['humidity', 'temperature']);
        $weatherRepository->expects($this->exactly(2))
                          ->method('queryYear')
                          ->withConsecutive(
                              ['outdoor', 'humidity', 2021],
                              ['outdoor', 'temperature', 2021],
                          )
                          ->willReturnOnConsecutiveCalls(
                              [
                                  1 => 1,
                                  2 => 2,
                              ],
                              [
                                  2 => 2,
                                  3 => 3,
                              ],
                          );

        $action = new QueryFixedDataAction();
        $response = ($action)(
            $normalizerMock,
            $weatherRepository,
            'outdoor',
            'json',
            2021,
        );
        $this->assertEquals(
            200,
            $response->getStatusCode(),
        );
        $this->assertEquals(
            [
                [
                    'timestamp' => 1,
                    'humidity' => 1,
                ],
                [
                    'timestamp' => 2,
                    'humidity' => 2,
                    'temperature' => 20,
                ],
                [
                    'timestamp' => 3,
                    'temperature' => 30,
                ],
            ],
            json_decode((string)$response->getBody(), true),
        );
    }
}
