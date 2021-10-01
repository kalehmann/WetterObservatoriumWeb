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

namespace KaLehmann\WetterObservatoriumWeb\tests\Action;

use KaLehmann\WetterObservatoriumWeb\Action\FormatTrait;
use PHPUnit\Framework\TestCase;

/**
 * Test cases for the FormatTrait.
 */
class FormatTraitTest extends TestCase
{
    use FormatTrait;

    /**
     * Check that a 404 response is returned for an unsupported format.
     */
    public function testWithUnsupportedFormat(): void
    {
        $response = $this->createResponse(
            [],
            'lulz',
        );
        $this->assertEquals(
            404,
            $response->getStatusCode(),
        );
        $this->assertStringContainsString(
            'All supported formats are',
            (string)$response->getBody(),
        );
    }

    /**
     * Check that data can be formated as csv.
     */
    public function testWithCsvFormat(): void
    {
        $response = $this->createResponse(
            [
                1 => 2,
                3 => 4,
            ],
            'csv',
        );
        $this->assertEquals(
            200,
            $response->getStatusCode(),
        );
        $this->assertEquals(
            [
                ['timestamp', 'value'],
                [1, 2],
                [3, 4],
            ],
            array_map(
                'str_getcsv',
                array_filter(
                    explode(
                        "\n",
                        (string)$response->getBody(),
                    ),
                ),
            ),
        );
    }

    /**
     * Check that data can be formated as json.
     */
    public function testWithJsonFormat(): void
    {
        $response = $this->createResponse(
            [
                1 => 2,
                3 => 4,
            ],
            'json',
        );
        $this->assertEquals(
            200,
            $response->getStatusCode(),
        );
        $this->assertEquals(
            [
                1 => 2,
                3 => 4,
            ],
            json_decode((string)$response->getBody(), true),
        );
    }
}
