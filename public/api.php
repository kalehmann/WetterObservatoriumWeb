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

use DI\ContainerBuilder;
use KaLehmann\WetterObservatoriumWeb\Middleware\ActionMiddleware;
use KaLehmann\WetterObservatoriumWeb\Middleware\HMACAuthorizationMiddleware;
use KaLehmann\WetterObservatoriumWeb\Middleware\RoutingMiddleware;
use Narrowspark\HttpEmitter\SapiEmitter;
use Nyholm\Psr7Server\ServerRequestCreator;
use Relay\Relay;
use Symfony\Component\Dotenv\Dotenv;

require_once(__DIR__ . '/../vendor/autoload.php');

$dotenv = new Dotenv();
$dotenv->load(__DIR__ . '/../.env');

$builder = new ContainerBuilder();
$builder->addDefinitions(__DIR__ . '/../config.php');
$builder->useAnnotations(false);
$container = $builder->build();

$serverRequestCreator = $container->get(ServerRequestCreator::class);
$request = $serverRequestCreator->fromGlobals();

$queue[] = $container->get(RoutingMiddleware::class);
$queue[] = $container->get(HMACAuthorizationMiddleware::class);
$queue[] = $container->get(ActionMiddleware::class);
$relay = new Relay($queue);
$response = $relay->handle($request);

$emitter = $container->get(SapiEmitter::class);
$emitter->emit($response);
