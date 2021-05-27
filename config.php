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

use FastRoute\RouteCollector;
use KaLehmann\WetterObservatoriumWeb\Action\AddDataAction;
use KaLehmann\WetterObservatoriumWeb\Action\ListLocationsAction;
use KaLehmann\WetterObservatoriumWeb\Action\ListQuantitiesAction;
use KaLehmann\WetterObservatoriumWeb\Action\QueryContinuousDataAction;
use KaLehmann\WetterObservatoriumWeb\Middleware\HMACAuthorizationMiddleware;
use KaLehmann\WetterObservatoriumWeb\Middleware\RoutingMiddleware;
use KaLehmann\WetterObservatoriumWeb\Persistence\DataLocator;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UploadedFileFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;

use function DI\create;
use function DI\env;
use function DI\get;

return [
    DataLocator::class => create()
        ->constructor(
            env('DATA_DIR'),
        ),
    HMACAuthorizationMiddleware::class => create()
        ->constructor(
            env('HMAC_SECRET'),
            get(LoggerInterface::class),
            get(Psr17Factory::class)
        ),
    LoggerInterface::class => create(Logger::class)
        ->constructor('WetterObservatoriumWeb')
        ->method(
            'pushHandler',
            new StreamHandler('php://stdout', Logger::DEBUG)
        ),
    RoutingMiddleware::class => create()
        ->constructor(
            get(Psr17Factory::class),
            fn () => function (RouteCollector $routeCollector) {
                $routeCollector->addRoute(
                    'POST',
                    '/api/{location:[a-z]*}',
                    AddDataAction::class,
                );
                $routeCollector->addRoute(
                    'GET',
                    '/api/locations.{format}',
                    ListLocationsAction::class,
                );
                $routeCollector->addRoute(
                    'GET',
                    '/api/{location}/quantities.{format}',
                    ListQuantitiesAction::class,
                );
                $routeCollector->addGroup(
                    '/api/{location:[a-z]*}/{quantity:[a-z]*}',
                    function (RouteCollector $routeCollector) {
                        $routeCollector->addRoute(
                            'GET',
                            '.{format}',
                            QueryContinuousDataAction::class,
                        );
                        $routeCollector->addRoute(
                            'GET',
                            '/{timespan:24h|31d}.{format}',
                            QueryContinuousDataAction::class,
                        );
                    }
                );
            },
        ),
    ServerRequestFactoryInterface::class => create(Psr17Factory::class),
    StreamFactoryInterface::class => create(Psr17Factory::class),
    UploadedFileFactoryInterface::class => create(Psr17Factory::class),
    UriFactoryInterface::class => create(Psr17Factory::class),
];
