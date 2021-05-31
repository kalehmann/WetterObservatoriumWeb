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

namespace KaLehmann\WetterObservatoriumWeb\Tests\Persistence\Filesystem;

use KaLehmann\WetterObservatoriumWeb\Persistence\Filesystem\DataPacker;
use KaLehmann\WetterObservatoriumWeb\Persistence\Filesystem\InvalidPackFormatException;
use KaLehmann\WetterObservatoriumWeb\Persistence\Filesystem\IOException;
use PHPUnit\Framework\TestCase;

/**
 * Test cases for the DataPacker.
 */
class DataPackerTest extends TestCase
{
    /**
     * Check that valid formats do not throw exceptions.
     */
    public function testCheckFormatWithValidFormat(): void
    {
        $firstValidFormat = 'cCsSnvlLNVqQJPx';
        $secondValidFormat = 'QlSxx';
        $thirdValidFormat = 'cL';

        $this->assertTrue(
            DataPacker::checkFormat($firstValidFormat)
        );
        $this->assertTrue(
            DataPacker::checkFormat($secondValidFormat)
        );
        $this->assertTrue(
            DataPacker::checkFormat($thirdValidFormat)
        );
    }

    /**
     * Check that invalid formats throw an exception.
     */
    public function testCheckInvalidFormat(): void
    {
        $invalidFormat = 'AaBbCcDdEeFfGgHhIiJjKkLlMmNnOoPpQqRrSsTtUuVvWwXxYyZz';

        $this->expectException(InvalidPackFormatException::class);
        $this->expectExceptionMessage(
            'A, a, B, b, D, d, E, e, F, f, G, g, H, h, I, i, j, K, k, M, m, ' .
            'O, o, p, R, r, T, t, U, u, W, w, X, Y, y, Z, z',
        );
        DataPacker::checkFormat($invalidFormat);
    }

    /**
     * Check that the getElementSize method throws an exception for an invalid
     * format.
     */
    public function testGetElementSizeWithInvalidFormat(): void
    {
        $invalidFormat = 'invalid';
        $this->expectException(InvalidPackFormatException::class);
        $this->expectExceptionMessage(
            'i, a, d',
        );

        DataPacker::getElementSize($invalidFormat);
    }

    /**
     * Check that the getElementSize method correctly determines the data size
     * from a pack format.
     */
    public function testGetElementSizeWithValidFormat(): void
    {
        $this->assertEquals(
            12,
            DataPacker::getElementSize('QL'),
        );

        $this->assertEquals(
            10,
            DataPacker::getElementSize('qcc'),
        );
        $this->assertEquals(
            strlen(
                pack('qcxxS', -2**16, 'a', 42),
            ),
            DataPacker::getElementSize('qcxxS'),
        );
    }

     /**
     * Check that the getFormatElementCount method throws an exception for an
     * invalid format.
     */
    public function testGetFormatElementCountWithInvalidFormat(): void
    {
        $invalidFormat = 'invalid';
        $this->expectException(InvalidPackFormatException::class);
        $this->expectExceptionMessage(
            'i, a, d',
        );

        DataPacker::getFormatElementCount($invalidFormat);
    }

    /**
     * Check that the number of Elements in a format is correctly determined.
     */
    public function testGetFormatElementCountWithValidFormat(): void
    {
        $this->assertEquals(
            0,
            DataPacker::getFormatElementCount('xx'),
        );
        $this->assertEquals(
            14,
            DataPacker::getFormatElementCount('cCsSnvlLNVqQJPx'),
        );
    }

    /**
     * Check that the unpack method throws an exception for an invalid format.
     */
    public function testUnpackWithInvalidFormat(): void
    {
        $invalidFormat = 'invalid';
        $this->expectException(InvalidPackFormatException::class);
        $this->expectExceptionMessage(
            'i, a, d',
        );

        DataPacker::unpack($invalidFormat, '');
    }

    /**
     * Check that the unpack methods unpacks valid data into a zero-indexed
     * array.
     */
    public function testUnpackWithValidData(): void
    {
        $format = 'PVvcx';
        $data = hex2bin('04000000000000000300000002000100');
        if (false === $data) {
            throw new \RunTimeException('Could not parse hex string in test.');
        }

        $this->assertEquals(
            [
                0 => 4,
                1 => 3,
                2 => 2,
                3 => 1,
            ],
            DataPacker::unpack($format, $data),
        );
    }

    /**
     * Check that a format with multiple elements of the same type can be
     * unpacked.
     */
    public function testUnpackWithMultipleElementsOfTheSameType(): void
    {
        $format = 'PxxPxx';
        $data = hex2bin('ff00000000000000000001000000000000000000');
        if (false === $data) {
            throw new \RunTimeException('Could not parse hex string in test.');
        }

        $this->assertEquals(
            [
                0 => 255,
                1 => 1,
            ],
            DataPacker::unpack($format, $data),
        );
    }
}
