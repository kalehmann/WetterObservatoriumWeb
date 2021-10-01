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
use Narrowspark\HttpEmitter\SapiEmitter;
use Nyholm\Psr7Server\ServerRequestCreator;
use Psr\Log\LoggerInterface;
use Relay\Relay;
use Symfony\Component\Dotenv\Dotenv;

require_once(__DIR__ . '/../vendor/autoload.php');

$dotenv = new Dotenv();
$dotenv->load(__DIR__ . '/../.env');

$builder = new ContainerBuilder();
$builder->addDefinitions(__DIR__ . '/../config.php');
$builder->useAnnotations(false);
$container = $builder->build();
$logger = $container->get(LoggerInterface::class);

set_exception_handler(
    function (Throwable $exception) use ($logger): void {
        $logger->error(
            $exception::class . ' in ' . $exception->getFile() . ':' .
            $exception->getLine() . ' : ' . $exception->getMessage(),
        );
    },
);

$serverRequestCreator = $container->get(ServerRequestCreator::class);
$request = $serverRequestCreator->fromGlobals();

$relay = $container->get(Relay::class);
$response = $relay->handle($request);

$emitter = $container->get(SapiEmitter::class);
$emitter->emit($response);
