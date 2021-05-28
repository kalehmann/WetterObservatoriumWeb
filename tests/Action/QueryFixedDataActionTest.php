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
use KaLehmann\WetterObservatoriumWeb\Persistence\WeatherRepository;
use PHPUnit\Framework\TestCase;

/**
 * Test cases for the QueryFixedDataAction.
 */
class QueryFixedDataActionTest extends TestCase
{
    /**
     * Check that all measured quantities for a location can be listed.
     */
    public function testQueryTheDataOfAMonth(): void
    {
        $weatherRepository = $this->createMock(WeatherRepository::class);
        $weatherRepository->expects($this->once())
                          ->method('queryMonth')
                          ->with('aquarium', 'temperature', 2021, 05)
                          ->willReturn([[1, 1], [2, 2]]);

        $action = new QueryFixedDataAction();
        $response = ($action)(
            $weatherRepository,
            'aquarium',
            'temperature',
            2021,
            'json',
            05,
        );

        $this->assertEquals(
            200,
            $response->getStatusCode(),
        );
        $this->assertEqualsCanonicalizing(
            [[1, 1], [2, 2]],
            json_decode((string)$response->getBody()),
        );
    }

    /**
     * Check that all measured quantities for a location can be listed.
     */
    public function testQueryTheDataOfAYear(): void
    {
        $weatherRepository = $this->createMock(WeatherRepository::class);
        $weatherRepository->expects($this->once())
                          ->method('queryYear')
                          ->with('aquarium', 'temperature', 2021)
                          ->willReturn([[1, 2], [3, 4]]);

        $action = new QueryFixedDataAction();
        $response = ($action)(
            $weatherRepository,
            'aquarium',
            'temperature',
            2021,
            'json',
        );

        $this->assertEquals(
            200,
            $response->getStatusCode(),
        );
        $this->assertEqualsCanonicalizing(
            [[1, 2], [3, 4]],
            json_decode((string)$response->getBody()),
        );
    }
}
