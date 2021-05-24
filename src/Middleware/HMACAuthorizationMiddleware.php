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

namespace KaLehmann\WetterObservatoriumWeb\Middleware;

use KaLehmann\WetterObservatoriumWeb\Attribute\AuthorizationAttribute;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use ReflectionClass;
use RuntimeException;
use function explode;
use function hash_hmac;
use function hash_hmac_algos;
use function in_array;

/**
 * Middleware for HMAC authorization.
 *
 * If the action has the {@see AuthorizationAttribute}, this Middleware
 * validates the HMAC signature of the request.
 */
class HMACAuthorizationMiddleware implements MiddlewareInterface
{
    private string $key;

    private LoggerInterface $logger;

    private Psr17Factory $psr17Factory;

    public function __construct(
        string $key,
        LoggerInterface $logger,
        Psr17Factory $psr17Factory
    ) {
        $this->key = $key;
        $this->logger = $logger;
        $this->psr17Factory = $psr17Factory;
    }

    /**
     * {@inheritdoc}
     */
    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        $actionClass = $request->getAttribute('_action');
        if ($actionClass === null) {
            throw new RuntimeException(
                'Request has no `_action` attribute.'
            );
        }

        $reflector = new ReflectionClass($actionClass);
        $authorizationAttributes = $reflector->getAttributes(
            AuthorizationAttribute::class
        );

        if (count($authorizationAttributes) > 0) {
            if ($this->isAuthorizationValid($request)) {
                return $handler->handle($request);
            }

            return $this->psr17Factory->createResponse(401);
        }

        return $handler->handle($request);
    }


    /**
     * Checks whether the HMAC signature for the request is valid.
     */
    private function isAuthorizationValid(
        ServerRequestInterface $request
    ): bool {
        if (false === $this->checkAuthorizationHeader($request)) {
            return false;
        }
        $attributes = $this->getAuthorizationHeaderAttributes($request);
        if (null === $attributes) {
            return false;
        }

        [
            'algorithm' => $algorithm,
            'headers' => $headers,
            'signature' => $signature,
            'username' => $username,
        ] = $attributes;

        if (false === $this->checkHMACAlgorithm($algorithm)) {
            return false;
        }

        if (false === $this->checkIncludedHeaders($request, $headers)) {
            return false;
        }

        return $this->checkHMACSignature(
            $request,
            $algorithm,
            $headers,
            $signature
        );
    }

    /**
     * Checks whether the request has the header `Authorization`.
     */
    private function checkAuthorizationHeader(
        ServerRequestInterface $request
    ): bool {
        $actionClass = $request->getAttribute('_action');
        if (false === $request->hasHeader('Authorization')) {
            $this->logger->info(
                'Request to ' . $actionClass . ' without `Authorization` header'
            );

            return false;
        }

        return true;
    }

    /**
     * Checks whether the `Authorization` header of the request has a valid
     * format or not.
     *
     * This method checks, that the `Authorization` header of the reques
     * has
     *  - an username
     *  - a hash alogrithm
     *  - a list of headers
     *  - a signature
     * in that order.
     *
     * @return null|array<string, mixed> if the header does not match the
     *                                   format `null` is returned, otherwise
     *                                   an array with the keys `algorithm`,
     *                                   `headers`, `signature` and `username`.
     */
    private function getAuthorizationHeaderAttributes(
        ServerRequestInterface $request
    ): ?array {
        $actionClass = $request->getAttribute('_action');
        $authorizationHeader = $request->getHeader('Authorization')[0];
        $headerIsValid = preg_match(
            '/^hmac username="(?<username>[a-zA-Z0-9]+)", ' .
            'algorithm="(?<algorithm>[a-z0-9-,]+)", ' .
            'headers="(?<headers>[a-z-]+)", ' .
            'signature="(?<signature>[a-zA-Z0-9\+\/=]+)"$/',
            $authorizationHeader,
            $matches
        );
        if (false === $headerIsValid || 0 === $headerIsValid) {
            $this->logger->info(
                'Request to protected ressource ' . $actionClass .
                ' has a malformed `Authorization` header.'
            );

            return null;
        }
        $matches['headers'] = explode(' ', $matches['headers']);

        return $matches;
    }

    /**
     * Checks whether PHP supports the hash alogrithm for HMAC or not.
     */
    private function checkHMACAlgorithm(string $algorithm): bool
    {
        if (false === in_array($algorithm, hash_hmac_algos(), true)) {
            $this->logger->warning(
                'HMAC hash algorithm ' . $algorithm . ' is not supported.'
            );

            return false;
        }

        return true;
    }

    /**
     * Checkes that the request has every header, that should be included in
     * the signed data.
     *
     * @param array<string> $headers
     */
    private function checkIncludedHeaders(
        ServerRequestInterface $request,
        array $headers,
    ): bool {
        foreach ($headers as $headerName) {
            if (false === $request->hasHeader($headerName)) {
                $this->logger->warning(
                    'The header `' . $headerName . '` should be included in ' .
                    'the signature, but the request does not contain this ' .
                    'header.'
                );

                return false;
            }
        }

        return true;
    }

    /**
     * HMAC verification of the signature for the request including the given
     * headers.
     *
     * @param array<string> $headers
     */
    private function checkHMACSignature(
        ServerRequestInterface $request,
        string $algorithm,
        array $headers,
        string $signature
    ): bool {
        $dataToSign = '';
        foreach ($headers as $headerName) {
            $dataToSign .= $headerName . ': ' .
                        $request->getHeader($headerName)[0] .
                        PHP_EOL;
        }

        $dataToSign .= (string) $request->getBody();

        /** @var string|bool $expectedSignature */
        $expectedSignature = hash_hmac(
            $algorithm,
            $dataToSign,
            $this->key,
        );

        if (false === $expectedSignature) {
            $this->logger->warning(
                'Failed to calculate signature for request.'
            );

            return false;
        }

        if ($expectedSignature === $signature) {
            return true;
        }
        $this->logger->warning(
            'The provide signature could not be reproduced.',
        );

        return false;
    }
}
