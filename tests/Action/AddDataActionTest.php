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

use KaLehmann\WetterObservatoriumWeb\Action\AddDataAction;
use KaLehmann\WetterObservatoriumWeb\Normalizer\Normalizer;
use KaLehmann\WetterObservatoriumWeb\Normalizer\NormalizerInterface;
use KaLehmann\WetterObservatoriumWeb\Persistence\WeatherRepositoryInterface;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Response;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RunTimeException;

/**
 * Test cases for the AddDataAction.
 */
class AddDataActionTest extends TestCase
{
    /**
     * Check that a request with a json body that does not contain an object
     * results in a 400 response.
     */
    public function testWithInvalidRequest(): void
    {
        $loggerMock = $this->createMock(LoggerInterface::class);
        $normalizer = new Normalizer();
        $psr17Factory = $this->createMock(Psr17Factory::class);
        $psr17Factory->expects($this->once())
                     ->method('createResponse')
                     ->with(400)
                     ->willReturn(new Response(400));
        $weatherRepository = $this->createMock(WeatherRepositoryInterface::class);
        $request = new ServerRequest(
            'POST',
            '/api/home',
            [],
            'true',
        );

        $action = new AddDataAction();
        $response = ($action)(
            $loggerMock,
            $normalizer,
            $psr17Factory,
            $request,
            $weatherRepository,
            'home',
        );
        $this->assertEquals(
            400,
            $response->getStatusCode(),
        );
    }

     /**
     * Check that a valid request results in calls to the persistence layer.
     */
    public function testWithValidRequest(): void
    {
        $loggerMock = $this->createMock(LoggerInterface::class);
        $normalizerMock = $this->createMock(NormalizerInterface::class);
        $normalizerMock->expects($this->exactly(2))
                       ->method('normalizeValue')
                       ->withConsecutive(
                           ['humidity', 123],
                           ['temperature', 456],
                       )
                       ->willReturnOnConsecutiveCalls(
                           1230,
                           4560,
                       );
        $psr17Factory = $this->createMock(Psr17Factory::class);
        $psr17Factory->expects($this->once())
                     ->method('createResponse')
                     ->with(200)
                     ->willReturn(new Response(200));
        $weatherRepository = $this->createMock(WeatherRepositoryInterface::class);
        $weatherRepository->expects($this->exactly(2))
                          ->method('persist')
                          ->withConsecutive(
                              [
                                  'home',
                                  'humidity',
                                  1230,
                                  $this->anything(),
                              ],
                              [
                                  'home',
                                  'temperature',
                                  4560,
                                  $this->anything(),
                              ],
                          );
        $payload = json_encode(
            [
                'humidity' => 123,
                'temperature' => 456,
            ],
        );
        if (false === $payload) {
            throw new RunTimeException(
                'Could not encode array to json for test.',
            );
        }
        $request = new ServerRequest(
            'POST',
            '/api/home',
            [],
            $payload,
        );

        $action = new AddDataAction();
        $response = ($action)(
            $loggerMock,
            $normalizerMock,
            $psr17Factory,
            $request,
            $weatherRepository,
            'home',
        );
        $this->assertEquals(
            200,
            $response->getStatusCode(),
        );
    }
}
