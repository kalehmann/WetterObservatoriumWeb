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

namespace KaLehmann\WetterObservatoriumWeb\Actions;

use DateTime;
use KaLehmann\WetterObservatoriumWeb\Actions\AddDataAction;
use KaLehmann\WetterObservatoriumWeb\Middleware\HMACAuthorizationMiddleware;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;

/**
 * Test cases for the HMACAuthorizationMiddleware.
 */
class HMACAuthorizationMiddlewareTest extends TestCase
{
    /**
     * Test that a request to a procted ressource without the `Authorization`
     * header results in a 401 response.
     */
    public function testProctectedRessourceWithoutAuthorization(): void
    {
        $request = new ServerRequest(
            'POST',
            '/api/aquarium',
            []
        );
        $request = $request->withAttribute(
            '_action',
            AddDataAction::class
        );

        $handlerMock = $this->createMock(RequestHandlerInterface::class);
        $loggerMock = $this->createMock(LoggerInterface::class);
        $psr17FactoryMock = $this->createMock(Psr17Factory::class);
        $psr17FactoryMock->expects($this->once())
                         ->method('createResponse')
                         ->with(401);

        $hmacAuthorizationMiddleware = new HMACAuthorizationMiddleware(
            '',
            $loggerMock,
            $psr17FactoryMock
        );

        $hmacAuthorizationMiddleware->process(
            $request,
            $handlerMock
        );
    }

    /**
     * Test that a request to a procted ressource with a malformed `Authorization`
     * header results in a 401 response.
     */
    public function testProctectedRessourceWithMalformedAuthorization(): void
    {
        $request = new ServerRequest(
            'POST',
            '/api/aquarium',
            [
                'Authorization' => 'malformed',
            ]
        );
        $request = $request->withAttribute(
            '_action',
            AddDataAction::class
        );

        $handlerMock = $this->createMock(RequestHandlerInterface::class);
        $loggerMock = $this->createMock(LoggerInterface::class);
        $psr17FactoryMock = $this->createMock(Psr17Factory::class);
        $psr17FactoryMock->expects($this->once())
                         ->method('createResponse')
                         ->with(401);

        $hmacAuthorizationMiddleware = new HMACAuthorizationMiddleware(
            '',
            $loggerMock,
            $psr17FactoryMock
        );

        $hmacAuthorizationMiddleware->process(
            $request,
            $handlerMock
        );
    }

    /**
     * Test that a request to a proctected ressource with a valid
     * `Authorization` header results in a 401 responsethe next handler
     * being called.
     */
    public function testProctectedRessourceWithCorrectAuthorization(): void
    {
        $key = '12345';
        $timestamp = (new DateTime())->getTimestamp();
        $body = 'myData';
        $hashAlgo = 'sha512';

        $signature = hash_hmac(
            $hashAlgo,
            'timestamp: ' . $timestamp . PHP_EOL .
            $body,
            $key
        );

        $request = new ServerRequest(
            'POST',
            '/api/aquarium',
            [
                'Timestamp' => (string) $timestamp,
                'Authorization' =>'hmac username="esp8266", ' .
                    'algorithm="' . $hashAlgo . '", ' .
                    'headers="timestamp", ' .
                    'signature="' . $signature . '"',
            ],
            $body
        );
        $request = $request->withAttribute(
            '_action',
            AddDataAction::class
        );

        $handlerMock = $this->createMock(RequestHandlerInterface::class);
        $loggerMock = $this->createMock(LoggerInterface::class);
        $psr17FactoryMock = $this->createMock(Psr17Factory::class);
        $handlerMock->expects($this->once())
                    ->method('handle');

        $hmacAuthorizationMiddleware = new HMACAuthorizationMiddleware(
            $key,
            $loggerMock,
            $psr17FactoryMock
        );

        $hmacAuthorizationMiddleware->process(
            $request,
            $handlerMock
        );
    }

    /**
     * Test that a request to a procted ressource with an invalid hash algorithm
     * in the `Authorization` header results in a 401 response.
     */
    public function testProctectedRessourceWithInvalidAlgorithm(): void
    {
        $request = new ServerRequest(
            'POST',
            '/api/aquarium',
            [
                'Authorization' =>'hmac username="esp8266", ' .
                    'algorithm="myFancyHash", ' .
                    'headers="date", ' .
                    'signature="YWZhc2YzMjRhc2+/Q="',
            ]
        );
        $request = $request->withAttribute(
            '_action',
            AddDataAction::class
        );

        $handlerMock = $this->createMock(RequestHandlerInterface::class);
        $loggerMock = $this->createMock(LoggerInterface::class);
        $psr17FactoryMock = $this->createMock(Psr17Factory::class);
        $psr17FactoryMock->expects($this->once())
                         ->method('createResponse')
                         ->with(401);

        $hmacAuthorizationMiddleware = new HMACAuthorizationMiddleware(
            '',
            $loggerMock,
            $psr17FactoryMock
        );

        $hmacAuthorizationMiddleware->process(
            $request,
            $handlerMock
        );
    }
}
