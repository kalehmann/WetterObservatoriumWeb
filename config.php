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
use KaLehmann\WetterObservatoriumWeb\Action\GraphAction;
use KaLehmann\WetterObservatoriumWeb\Action\ListLocationsAction;
use KaLehmann\WetterObservatoriumWeb\Action\ListQuantitiesAction;
use KaLehmann\WetterObservatoriumWeb\Action\QueryContinuousDataAction;
use KaLehmann\WetterObservatoriumWeb\Action\QueryFixedDataAction;
use KaLehmann\WetterObservatoriumWeb\Middleware\ActionMiddleware;
use KaLehmann\WetterObservatoriumWeb\Middleware\HMACAuthorizationMiddleware;
use KaLehmann\WetterObservatoriumWeb\Middleware\RoutingMiddleware;
use KaLehmann\WetterObservatoriumWeb\Normalizer\Normalizer;
use KaLehmann\WetterObservatoriumWeb\Normalizer\NormalizerInterface;
use KaLehmann\WetterObservatoriumWeb\Normalizer\HumidityNormalizer;
use KaLehmann\WetterObservatoriumWeb\Normalizer\PressureNormalizer;
use KaLehmann\WetterObservatoriumWeb\Normalizer\RSSINormalizer;
use KaLehmann\WetterObservatoriumWeb\Normalizer\SunIntensityNormalizer;
use KaLehmann\WetterObservatoriumWeb\Normalizer\TemperatureNormalizer;
use KaLehmann\WetterObservatoriumWeb\Persistence\Filesystem\DataLocator;
use KaLehmann\WetterObservatoriumWeb\Persistence\Filesystem\WeatherRepository;
use KaLehmann\WetterObservatoriumWeb\Persistence\WeatherRepositoryInterface;
use KaLehmann\WetterObservatoriumWeb\Twig\GraphExtension;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7Server\ServerRequestCreator;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UploadedFileFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;
use Relay\Relay;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

use function DI\create;
use function DI\env;
use function DI\factory;
use function DI\get;
use function KaLehmann\WetterObservatoriumWeb\env_var;

return [
    DataLocator::class => create()
        ->constructor(
            env('DATA_DIR'),
        ),
    Environment::class => create()
        ->constructor(
            create(FilesystemLoader::class)
                ->constructor(
                    __DIR__ . '/templates',
                ),
            [
                'cache' => factory(
                    function (ContainerInterface $container): false|string {
                        $cachePath = env_var('TWIG_CACHE');
                        if (null === $cachePath) {
                            return false;
                        }

                        if (
                            strlen($cachePath) &&
                            $cachePath[0] === DIRECTORY_SEPARATOR
                        ) {
                            return $cachePath;
                        }

                        return __DIR__ . DIRECTORY_SEPARATOR . $cachePath;
                    },
                ),
            ],
        )
        ->method(
            'addExtension',
            fn (ContainerInterface $container) => $container->get(GraphExtension::class),
        ),
    HMACAuthorizationMiddleware::class => create()
        ->constructor(
            env('HMAC_SECRET'),
            get(LoggerInterface::class),
            get(Psr17Factory::class)
        ),
    LoggerInterface::class => factory(
        function (ContainerInterface $container): LoggerInterface {
            $logger = new Logger('WetterObservatoriumWeb');
            $logger->pushHandler(
                new StreamHandler('php://stdout', Logger::DEBUG),
            );
            $logFile = env_var('LOG_FILE');
            if (null === $logFile) {
                return $logger;
            }

            if (
                strlen($logFile) &&
                $logFile[0] === DIRECTORY_SEPARATOR
            ) {
                $logger->pushHandler(
                    new StreamHandler($logFile, Logger::DEBUG),
                );

                return $logger;
            }

            $logger->pushHandler(
                new StreamHandler(
                    __DIR__ . DIRECTORY_SEPARATOR . $logFile,
                    Logger::DEBUG,
                ),
            );

            return $logger;
        }
    ),
    NormalizerInterface::class => create(Normalizer::class)
        ->method(
            'setQuantityNormalizers',
            [
                create(HumidityNormalizer::class),
                create(PressureNormalizer::class),
                create(RSSINormalizer::class),
                create(SunIntensityNormalizer::class),
                create(TemperatureNormalizer::class),
            ],
        ),
    Relay::class => create()
        ->constructor(
            [
                get(RoutingMiddleware::class),
                get(HMACAuthorizationMiddleware::class),
                get(ActionMiddleware::class),
            ],
        ),
    RoutingMiddleware::class => create()
        ->constructor(
            get(LoggerInterface::class),
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
                    '/api/{location:[a-z]*}',
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
                        $routeCollector->addRoute(
                            'GET',
                            '/{year:\d{4}}.{format}',
                            QueryFixedDataAction::class,
                        );
                        $routeCollector->addRoute(
                            'GET',
                            '/{year:\d{4}}/{month:\d{2}}.{format}',
                            QueryFixedDataAction::class,
                        );
                        $routeCollector->addGroup(
                            '/{quantity:[a-z]*}',
                            function (RouteCollector $routeCollector) {
                                $routeCollector->addRoute(
                                    'GET',
                                    '.svg',
                                    GraphAction::class,
                                );
                                $routeCollector->addRoute(
                                    'GET',
                                    '/{timespan:24h|31d}.svg',
                                    GraphAction::class,
                                );
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
                                $routeCollector->addGroup(
                                    '/{year:\d{4}}',
                                    function (RouteCollector $routeCollector) {
                                        $routeCollector->addRoute(
                                            'GET',
                                            '.svg',
                                            GraphAction::class,
                                        );
                                        $routeCollector->addRoute(
                                            'GET',
                                            '/{month:\d{2}}.svg',
                                            GraphAction::class,
                                        );
                                        $routeCollector->addRoute(
                                            'GET',
                                            '.{format}',
                                            QueryFixedDataAction::class,
                                        );
                                        $routeCollector->addRoute(
                                            'GET',
                                            '/{month:\d{2}}.{format}',
                                            QueryFixedDataAction::class,
                                        );
                                    }
                                );
                            }
                        );
                    }
                );
            },
        ),
    ServerRequestInterface::class => factory(
        fn(ContainerInterface $c) => $c
            ->get(ServerRequestCreator::class)
            ->fromGlobals(),
    ),
    ServerRequestFactoryInterface::class => create(Psr17Factory::class),
    StreamFactoryInterface::class => create(Psr17Factory::class),
    UploadedFileFactoryInterface::class => create(Psr17Factory::class),
    UriFactoryInterface::class => create(Psr17Factory::class),
    WeatherRepositoryInterface::class => get(WeatherRepository::class),
];
