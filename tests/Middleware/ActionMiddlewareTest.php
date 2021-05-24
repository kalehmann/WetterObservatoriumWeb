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

namespace KaLehmann\WetterObservatoriumWeb\Tests\Middleware;

use DI\Container;
use KaLehmann\WetterObservatoriumWeb\Middleware\ActionMiddleware;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;

/**
 * Test cases for the ActionMiddleware.
 */
class ActionMiddlewareTest extends TestCase
{
    /**
     * Tests that a request without the `_action` attribute results in an
     * exception.
     */
    public function testCallWithoutAction(): void
    {
        $request = new ServerRequest(
            'POST',
            '/api/aquarium',
            []
        );

        $containerMock = $this->createMock(Container::class);
        $handlerMock = $this->createMock(RequestHandlerInterface::class);
        $actionMiddleware = new ActionMiddleware($containerMock);

        $this->expectException(RuntimeException::class);
        $actionMiddleware->process($request, $handlerMock);
    }

    /**
     * Tests that a request without the `_action` attribute, but without the
     * `_params` attribute results in the action being invoked.
     */
    public function testCallWithoutParams(): void
    {
        $actionClass = 'myFancyAction';
        $actionInstance = (object) $actionClass;
        $request = new ServerRequest(
            'POST',
            '/api/aquarium',
            []
        );
        $request = $request
                 ->withAttribute(
                     '_action',
                     $actionClass
                 );

        $containerMock = $this->createMock(Container::class);
        $containerMock->expects($this->once())
                      ->method('get')
                      ->with($actionClass)
                      ->willReturn($actionInstance);

        $containerMock->expects($this->once())
                      ->method('call')
                      ->with(
                          $actionInstance,
                          [
                              'request' => $request,
                          ],
                      )
                      ->willReturn($this->createMock(ResponseInterface::class));
        $handlerMock = $this->createMock(RequestHandlerInterface::class);
        $actionMiddleware = new ActionMiddleware($containerMock);

        $actionMiddleware->process($request, $handlerMock);
    }

    /**
     * Tests that a request with the `_action` and the `_params` attribute
     * results in the action being invoked.
     */
    public function testCallWithActionAndParams(): void
    {
        $actionClass = 'myFancyAction';
        $actionInstance = (object) $actionClass;
        $params = ['location' => 'aquarium'];
        $request = new ServerRequest(
            'POST',
            '/api/aquarium',
            []
        );
        $request = $request
                 ->withAttribute(
                     '_action',
                     $actionClass
                 )
                 ->withAttribute(
                     '_params',
                     $params
                 );
        $params['request'] = $request;

        $containerMock = $this->createMock(Container::class);
        $containerMock->expects($this->once())
                      ->method('get')
                      ->with($actionClass)
                      ->willReturn($actionInstance);

        $containerMock->expects($this->once())
                      ->method('call')
                      ->with(
                          $actionInstance,
                          $params,
                      )
                      ->willReturn($this->createMock(ResponseInterface::class));
        $handlerMock = $this->createMock(RequestHandlerInterface::class);
        $actionMiddleware = new ActionMiddleware($containerMock);

        $actionMiddleware->process($request, $handlerMock);
    }
}
