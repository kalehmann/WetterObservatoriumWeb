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

namespace KaLehmann\WetterObservatoriumWeb\Action;

use Nyholm\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use RunTimeException;

/**
 * Helper functions for creating responses in various formats.
 */
trait FormatTrait
{
    /**
     * Returns a response with the payload encoded in the given format.
     * If the format is not supported, a 404 response is returned.
     *
     * @param array<int|string|array> $payload to payload for the response body
     * @param string $format the format of the response
     * @param int $status the status code of the response
     *
     * @return ResponseInterface the response with the payload encoded in the
     *                           given format.
     */
    private function createResponse(
        array $payload,
        string $format,
        int $status = 200,
    ): ResponseInterface {
        if (!in_array($format, self::FORMATS())) {
            return new Response(
                body: 'Format ' . $format . ' is not supported. ' .
                'All supported formats are ' . join(', ', self::FORMATS()),
                status: 404,
            );
        }

        $body = '';
        $headers = [];
        switch ($format) {
            case 'json':
            default:
                $body = json_encode($payload);
                $headers['Content-Type'] = 'application/json';
        }

        if (false === $body) {
            throw new RunTimeException(
                'Failed to encode payload',
            );
        }

        return new Response(
            body: $body,
            headers: $headers,
            status: $status,
        );
    }

    /**
     * Returns the formats supported by this trait.
     *
     * @return array<int, string>
     */
    private static function FORMATS(): array
    {
        return ['json'];
    }
}
