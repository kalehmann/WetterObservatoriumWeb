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

use KaLehmann\WetterObservatoriumWeb\Middleware\HMACAuthorizationMiddleware;
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
    ServerRequestFactoryInterface::class => create(Psr17Factory::class),
    StreamFactoryInterface::class => create(Psr17Factory::class),
    UploadedFileFactoryInterface::class => create(Psr17Factory::class),
    UriFactoryInterface::class => create(Psr17Factory::class),
];
