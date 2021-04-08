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

namespace KaLehmann\WetterObservatoriumWeb\Actions;

use KaLehmann\WetterObservatoriumWeb\Actions\AddDataAction;
use Nyholm\Psr7\Request;
use PHPUnit\Framework\TestCase;

/**
 * Test cases for the AddDataAction.
 */
class AddDataActionTest extends TestCase
{
    /**
     * Test that the action indeed matches a POST request to the endpoints for adding data.
     */
    public function testMatchesValidRequest(): void
    {
        $method = 'POST';
        $path1 = '/api/aquarium';
        $path2 = '/api/indoor/';

        $this->assertTrue(
            AddDataAction::matchesRequest(
                new Request($method, $path1),
            ),
            'Failed asserting that AddDataAction matches a ' .
            $method .
            ' request to ' .
            $path1,
        );
        $this->assertTrue(
            AddDataAction::matchesRequest(
                new Request($method, $path2),
            ),
            'Failed asserting that AddDataAction matches a ' .
            $method .
            ' request to ' .
            $path2,
        );
    }

    /**
     * Test that requests with a method other than POST are rejected.
     */
    public function testRejectsNonPostRequest(): void
    {
        $deleteRequest = new Request('DELETE', '/api/aquarium');
        $getRequest = new Request('GET', '/api/indoor/');
        $putRequest = new Request('PUT', '/api/outdoor');

        $this->assertFalse(
            AddDataAction::matchesRequest($deleteRequest),
            'Failed asserting that AddDataAction rejects a DELETE request',
        );
        $this->assertFalse(
            AddDataAction::matchesRequest($getRequest),
            'Failed asserting that AddDataAction rejects a GET request',
        );
        $this->assertFalse(
            AddDataAction::matchesRequest($putRequest),
            'Failed asserting that AddDataAction rejects a PUT request',
        );
    }

    /**
     * Test that requests to other endpoints are rejected.
     */
    public function testRejectesRequestToWrongPath(): void
    {
        $method = 'POST';
        $pathToClass = '/api/aquarium/temperature';
        $pathWithFormat = '/api/outdoor.csv';
        $pathToMonth = '/api/indoor/2020/03.csv';
        $pathWithMalformedLocation = '/api/aquarium_3/';

        $this->assertFalse(
            AddDataAction::matchesRequest(
                new Request($method, $pathToClass),
            ),
            'Failed asserting that AddDataAction rejects a ' .
            $method .
            ' request to ' .
            $pathToClass,
        );
        $this->assertFalse(
            AddDataAction::matchesRequest(
                new Request($method, $pathWithFormat),
            ),
            'Failed asserting that AddDataAction rejects a ' .
            $method .
            ' request to ' .
            $pathWithFormat,
        );
        $this->assertFalse(
            AddDataAction::matchesRequest(
                new Request($method, $pathToMonth),
            ),
            'Failed asserting that AddDataAction rejects a ' .
            $method .
            ' request to ' .
            $pathToMonth,
        );
        $this->assertFalse(
            AddDataAction::matchesRequest(
                new Request($method, $pathWithMalformedLocation),
            ),
            'Failed asserting that AddDataAction rejects a ' .
            $method .
            ' request to ' .
            $pathWithMalformedLocation,
        );
    }
}
