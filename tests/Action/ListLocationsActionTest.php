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

use KaLehmann\WetterObservatoriumWeb\Action\ListLocationsAction;
use KaLehmann\WetterObservatoriumWeb\Persistence\WeatherRepository;
use PHPUnit\Framework\TestCase;

/**
 * Test cases for the ListLocationsAction.
 */
class ListLocationsActionTest extends TestCase
{
    /**
     * Check that all locations where data was saved can be listed.
     */
    public function testListLocations(): void
    {
        $weatherRepository = $this->createMock(WeatherRepository::class);
        $weatherRepository->expects($this->once())
                          ->method('queryLocations')
                          ->willReturn(['home', 'outdoor']);

        $action = new ListLocationsAction();
        $response = ($action)(
            $weatherRepository,
            'json',
        );

        $this->assertEquals(
            200,
            $response->getStatusCode(),
        );
        $this->assertEqualsCanonicalizing(
            ['home', 'outdoor'],
            json_decode((string)$response->getBody()),
        );
    }
}
