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

use FastRoute\RouteCollector;
use KaLehmann\WetterObservatoriumWeb\Middleware\RoutingMiddleware;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Test cases for the RoutingMiddleware.
 */
class RoutingMiddlewareTest extends TestCase
{
    /**
     * Check that a 404 response is returned for an unknown route.
     */
    public function testRoutingWithUnknownRoute(): void
    {
        $routeDefinitionCallback = function (RouteCollector $routeCollector) {
            $routeCollector->addRoute(
                'POST',
                '/api/{location:[a-z]*}',
                'dummyHandler',
            );
        };
        $handlerMock = $this->createMock(RequestHandlerInterface::class);
        $psr17FactoryMock = $this->createMock(Psr17Factory::class);
        $psr17FactoryMock->expects($this->once())
                         ->method('createResponse')
                         ->with(404);
        $request = new ServerRequest(
            'POST',
            '/unknown',
            []
        );

        $routingMiddleware = new RoutingMiddleware(
            $psr17FactoryMock,
            $routeDefinitionCallback,
        );

        $routingMiddleware->process(
            $request,
            $handlerMock,
        );
    }

    /**
     * Check that a 405 response is returned for a known route with the wrong
     * method.
     */
    public function testRoutingWithWrongMethod(): void
    {
        $routeDefinitionCallback = function (RouteCollector $routeCollector) {
            $routeCollector->addRoute(
                'GET',
                '/api/{location:[a-z]*}/classes',
                'dummyHandler',
            );
        };
        $handlerMock = $this->createMock(RequestHandlerInterface::class);
        $psr17FactoryMock = $this->createMock(Psr17Factory::class);
        $psr17FactoryMock->expects($this->once())
                         ->method('createResponse')
                         ->with(405);
        $request = new ServerRequest(
            'POST',
            '/api/outdoor/classes',
            []
        );

        $routingMiddleware = new RoutingMiddleware(
            $psr17FactoryMock,
            $routeDefinitionCallback,
        );

        $routingMiddleware->process(
            $request,
            $handlerMock,
        );
    }

    /**
     * Check that routing works with known routes.
     */
    public function testRoutingWithKnowRouteAndCorrectMethod(): void
    {
        $routeDefinitionCallback = function (RouteCollector $routeCollector) {
            $routeCollector->addRoute(
                'POST',
                '/api/{location:[a-z]*}',
                'dummyHandler',
            );
        };

        $requestCallback = $this->callback(
            function (ServerRequestInterface $request) {
                return $request->getAttribute('_action') === 'dummyHandler'
                    && $request->getAttribute('_params') === [
                        'location' => 'aquarium',
                    ];
            },
        );
        $handlerMock = $this->createMock(RequestHandlerInterface::class);
        $handlerMock->expects($this->once())
                    ->method('handle')
                    ->with(
                        $requestCallback,
                    );
        $psr17FactoryMock = $this->createMock(Psr17Factory::class);
        $request = new ServerRequest(
            'POST',
            '/api/aquarium',
            []
        );

        $routingMiddleware = new RoutingMiddleware(
            $psr17FactoryMock,
            $routeDefinitionCallback,
        );

        $routingMiddleware->process(
            $request,
            $handlerMock,
        );
    }
}
