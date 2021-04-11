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

namespace KaLehmann\WetterObservatoriumWeb\MiddleWare;

use FastRoute\Dispatcher;
use FastRoute\RouteCollector;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

use function FastRoute\simpleDispatcher;

/**
 * Middleware for adding route data to requests.
 *
 * This middleware add two attributes to each request:
 *  - _action: The class of the action, that should generate the response
 *  - _params: The params for the action
 *
 * In case no action for a request can be found, a 404 response is returned.
 * If the uri matches an action, but the method does not fit the action, a
 * 405 resonse is returned.
 */
class RoutingMiddleware implements MiddlewareInterface
{
    private Psr17Factory $psr17Factory;

    public function __construct(Psr17Factory $psr17Factory)
    {
        $this->psr17Factory = $psr17Factory;
    }

    /**
     * {@inheritdoc}
     */
    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        $dispatcher = simpleDispatcher(function (RouteCollector $routeCollector) {
            $routeCollector->addRoute(
                'POST',
                '/api/{location:[a-z]*}',
                AddDataAction::class
            );
        });

        $routeInfo = $dispatcher->dispatch(
            $request->getMethod(),
            $request->getUri()->getPath(),
        );

        switch($routeInfo[0]) {
            case Dispatcher::NOT_FOUND:
                $response = $this->psr17Factory->createResponse(404);

                return $response;
            case Dispatcher::METHOD_NOT_ALLOWED:
                $response = $this->psr17Factory->createResponse(405);

                return $response;
            case Dispatcher::FOUND:
                [
                    1 => $action,
                    2 => $params
                ] = $routeInfo;

                return $handler->handle(
                    $request
                        ->withAttribute('_action', $action)
                        ->withAttribute('_params', $params)
                );
        }
    }
}
