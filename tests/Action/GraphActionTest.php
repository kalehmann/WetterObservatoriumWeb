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

use KaLehmann\WetterObservatoriumWeb\Action\GraphAction;
use KaLehmann\WetterObservatoriumWeb\Normalizer\NormalizerInterface;
use KaLehmann\WetterObservatoriumWeb\Persistence\WeatherRepositoryInterface;
use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Loader\LoaderInterface;

/**
 * Test cases for the GraphAction.
 */
class GraphActionTest extends TestCase
{
    /**
     * Check that the response has the correct headers for a svg file.
     */
    public function testHeaders(): void
    {
        $location = 'testlocation';
        $quantity = 'testquantity';
        $loaderMock = $this->createMock(LoaderInterface::class);
        $loaderMock->expects($this->once())
                   ->method('exists')
                   ->willReturn(false);
        $normalizerMock = $this->createMock(NormalizerInterface::class);
        $normalizerMock->expects($this->exactly(2))
                       ->method('denormalizeValue')
                       ->withConsecutive(
                           [$quantity, 1],
                           [$quantity, 2],
                       )
                       ->willReturnOnConsecutiveCalls(
                           1,
                           2,
                       );
        $twigMock = $this->createMock(Environment::class);
        $twigMock->expects($this->once())
                 ->method('getLoader')
                 ->willReturn($loaderMock);
        $weatherRepositoryMock
            = $this->createMock(WeatherRepositoryInterface::class);
        $weatherRepositoryMock->expects($this->once())
                              ->method('queryLocations')
                              ->willReturn([$location]);
        $weatherRepositoryMock->expects($this->once())
                              ->method('queryQuantities')
                              ->with($location)
                              ->willReturn([$quantity]);
        $weatherRepositoryMock->expects($this->once())
                          ->method('query24h')
                          ->with($location, $quantity)
                          ->willReturn(
                              [
                                  1 => 1,
                                  2 => 2,
                              ],
                          );

        $action = new GraphAction();
        $response = ($action)(
            $twigMock,
            $normalizerMock,
            $weatherRepositoryMock,
            $location,
            $quantity,
        );
        $this->assertEquals(
            200,
            $response->getStatusCode(),
        );
        $contentType = $response->getHeader('Content-Type');
        $this->assertContains(
            'image/svg+xml',
            $contentType,
        );
    }

    /**
     * Check that a 404 response is returned for an unknown location.
     */
    public function testWithUnknownLocation(): void
    {
        $location = 'testlocation';
        $quantity = 'testquantity';
        $normalizerMock = $this->createMock(NormalizerInterface::class);
        $twigMock = $this->createMock(Environment::class);
        $weatherRepositoryMock
            = $this->createMock(WeatherRepositoryInterface::class);
        $weatherRepositoryMock->expects($this->once())
                              ->method('queryLocations')
                              ->willReturn([]);

        $action = new GraphAction();
        $response = ($action)(
            $twigMock,
            $normalizerMock,
            $weatherRepositoryMock,
            $location,
            $quantity,
        );
        $this->assertEquals(
            404,
            $response->getStatusCode(),
        );
    }

    /**
     * Check that a 404 response is returned for an unknown quantity.
     */
    public function testWithUnknownQuantity(): void
    {
        $location = 'testlocation';
        $quantity = 'testquantity';
        $normalizerMock = $this->createMock(NormalizerInterface::class);
        $twigMock = $this->createMock(Environment::class);
        $weatherRepositoryMock
            = $this->createMock(WeatherRepositoryInterface::class);
        $weatherRepositoryMock->expects($this->once())
                              ->method('queryLocations')
                              ->willReturn([$location]);
        $weatherRepositoryMock->expects($this->once())
                              ->method('queryQuantities')
                              ->with($location)
                              ->willReturn([]);

        $action = new GraphAction();
        $response = ($action)(
            $twigMock,
            $normalizerMock,
            $weatherRepositoryMock,
            $location,
            $quantity,
        );
        $this->assertEquals(
            404,
            $response->getStatusCode(),
        );
    }
}
