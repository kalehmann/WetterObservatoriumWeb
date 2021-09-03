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

use KaLehmann\WetterObservatoriumWeb\Action\ListQuantitiesAction;
use KaLehmann\WetterObservatoriumWeb\Persistence\WeatherRepositoryInterface;
use PHPUnit\Framework\TestCase;

/**
 * Test cases for the ListQuantitiesAction.
 */
class ListQuantitiesActionTest extends TestCase
{
    /**
     * Check that all measured quantities for a location can be listed.
     */
    public function testListQuantities(): void
    {
        $weatherRepository = $this->createMock(WeatherRepositoryInterface::class);
        $weatherRepository->expects($this->once())
                          ->method('queryQuantities')
                          ->with('aquarium')
                          ->willReturn(['ph', 'temperature']);

        $action = new ListQuantitiesAction();
        $response = ($action)(
            $weatherRepository,
            'aquarium',
            'json',
        );

        $this->assertEquals(
            200,
            $response->getStatusCode(),
        );
        $this->assertEqualsCanonicalizing(
            ['ph', 'temperature'],
            json_decode((string)$response->getBody()),
        );
    }

    /**
     * Check that an empty list of quantities is returned for an unknown
     * location.
     */
    public function testListQuantitiesWithUnknownLocation(): void
    {
        $weatherRepository = $this->createMock(WeatherRepositoryInterface::class);
        $weatherRepository->expects($this->once())
                          ->method('queryQuantities')
                          ->with('aquarium')
                          ->willReturn([]);

        $action = new ListQuantitiesAction();
        $response = ($action)(
            $weatherRepository,
            'aquarium',
            'json',
        );

        $this->assertEquals(
            200,
            $response->getStatusCode(),
        );
        $this->assertEqualsCanonicalizing(
            [],
            json_decode((string)$response->getBody()),
        );
    }
}
