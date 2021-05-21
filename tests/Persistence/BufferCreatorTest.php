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

namespace KaLehmann\WetterObservatoriumWebP\Tests\Persistence;

use KaLehmann\WetterObservatoriumWeb\Persistence\Buffer;
use KaLehmann\WetterObservatoriumWeb\Persistence\BufferCreator;
use KaLehmann\WetterObservatoriumWeb\Persistence\DataLocator;
use KaLehmann\WetterObservatoriumWeb\Persistence\IOException;
use KaLehmann\WetterObservatoriumWeb\Persistence\RingBuffer;
use PHPUnit\Framework\TestCase;
use Exception;
use RunTimeException;

/**
 * Test cases for the BufferCreator.
 */
class BufferCreatorTest extends TestCase
{
    /**
     * Check that an exception is thrown when attempting to create a buffer for
     * the data of the last 24 hours, that already exists.
     */
    public function testCreate24hBufferWithExistingFile(): void
    {
        $location = 'test_location';
        $quantity = 'test_quantity';
        $bufferPath = sys_get_temp_dir() . '/' . bin2hex(random_bytes(8)) .
                    '.bin';

        $dataLocator = $this->createMock(DataLocator::class);
        $dataLocator->expects($this->once())
                    ->method('get24hPath')
                    ->with($location, $quantity)
                    ->willReturn($bufferPath);
        if (false === touch($bufferPath)) {
            throw new RunTimeException(
                'Could not create temporary file ' . $bufferPath . ' for test!',
            );
        }

        $this->expectException(IOException::class);
        $this->expectExceptionMessage('file exists');

        try {
            $bufferCreator = new BufferCreator($dataLocator);
            $bufferCreator->create24hBuffer($location, $quantity);
        } catch (Exception $e) {
            unlink($bufferPath);

            throw $e;
        }
    }

    /**
     * Check that a buffer for the weather data of the last 24 hours can be
     * created.
     */
    public function testCreate24hBuffer(): void
    {
        $location = 'test_location';
        $quantity = 'test_quantity';
        $dataDir = sys_get_temp_dir() . '/' . bin2hex(random_bytes(8));
        $bufferPath =  join(
            '/',
            [
                $dataDir,
                $location,
                $quantity,
                '24h.bin',
            ],
        );

        $dataLocator = $this->createMock(DataLocator::class);
        $dataLocator->expects($this->once())
                    ->method('get24hPath')
                    ->with($location, $quantity)
                    ->willReturn($bufferPath);

        $bufferCreator = new BufferCreator($dataLocator);
        $bufferCreator->create24hBuffer($location, $quantity);

        $ringBuffer = RingBuffer::fromFile($bufferPath, BufferCreator::BUFFER_FORMAT);
        $this->assertEquals(BufferCreator::BUFFER_SIZE_24H, count($ringBuffer));
        unlink($bufferPath);
        rmdir($dataDir . '/' . $location . '/' . $quantity);
        rmdir($dataDir . '/' . $location);
        rmdir($dataDir);
    }

    /**
     * Check that an exception is thrown when attempting to create a buffer for
     * the data of the last 31 days, that already exists.
     */
    public function testCreate31dBufferWithExistingFile(): void
    {
        $location = 'test_location';
        $quantity = 'test_quantity';
        $bufferPath = sys_get_temp_dir() . '/' . bin2hex(random_bytes(8)) .
                    '.bin';

        $dataLocator = $this->createMock(DataLocator::class);
        $dataLocator->expects($this->once())
                    ->method('get31dPath')
                    ->with($location, $quantity)
                    ->willReturn($bufferPath);
        if (false === touch($bufferPath)) {
            throw new RunTimeException(
                'Could not create temporary file ' . $bufferPath . ' for test!',
            );
        }

        $this->expectException(IOException::class);
        $this->expectExceptionMessage('file exists');

        try {
            $bufferCreator = new BufferCreator($dataLocator);
            $bufferCreator->create31dBuffer($location, $quantity);
        } catch (Exception $e) {
            unlink($bufferPath);

            throw $e;
        }
    }

    /**
     * Check that a buffer for the weather data of the last 31 days can be
     * created.
     */
    public function testCreate31dBuffer(): void
    {
        $location = 'test_location';
        $quantity = 'test_quantity';
        $dataDir = sys_get_temp_dir() . '/' . bin2hex(random_bytes(8));
        $bufferPath =  join(
            '/',
            [
                $dataDir,
                $location,
                $quantity,
                '31d.bin',
            ],
        );

        $dataLocator = $this->createMock(DataLocator::class);
        $dataLocator->expects($this->once())
                    ->method('get31dPath')
                    ->with($location, $quantity)
                    ->willReturn($bufferPath);

        $bufferCreator = new BufferCreator($dataLocator);
        $bufferCreator->create31dBuffer($location, $quantity);

        $ringBuffer = RingBuffer::fromFile($bufferPath, BufferCreator::BUFFER_FORMAT);
        $this->assertEquals(BufferCreator::BUFFER_SIZE_31D, count($ringBuffer));
        unlink($bufferPath);
        rmdir($dataDir . '/' . $location . '/' . $quantity);
        rmdir($dataDir . '/' . $location);
        rmdir($dataDir);
    }

    /**
     * Check that an exception is thrown when attempting to create a buffer for
     * the data of a month, that already exists.
     */
    public function testCreateMonthBufferWithExistingFile(): void
    {
        $location = 'test_location';
        $quantity = 'test_quantity';
        $month = 5;
        $year = 2021;
        $bufferPath = sys_get_temp_dir() . '/' . bin2hex(random_bytes(8)) .
                    '.bin';

        $dataLocator = $this->createMock(DataLocator::class);
        $dataLocator->expects($this->once())
                    ->method('getMonthPath')
                    ->with($location, $quantity, $year, $month)
                    ->willReturn($bufferPath);
        if (false === touch($bufferPath)) {
            throw new RunTimeException(
                'Could not create temporary file ' . $bufferPath . ' for test!',
            );
        }

        $this->expectException(IOException::class);
        $this->expectExceptionMessage('file exists');

        try {
            $bufferCreator = new BufferCreator($dataLocator);
            $bufferCreator->createMonthBuffer($location, $quantity, $year, $month);
        } catch (Exception $e) {
            unlink($bufferPath);

            throw $e;
        }
    }

    /**
     * Check that a buffer for the weather data of a month can be
     * created.
     */
    public function testCreateMonthBuffer(): void
    {
        $location = 'test_location';
        $quantity = 'test_quantity';
        $month = 5;
        $year = 2021;
        $dataDir = sys_get_temp_dir() . '/' . bin2hex(random_bytes(8));
        $bufferPath =  join(
            '/',
            [
                $dataDir,
                $location,
                $quantity,
                $year,
                $month . '.bin',
            ],
        );

        $dataLocator = $this->createMock(DataLocator::class);
        $dataLocator->expects($this->once())
                    ->method('getMonthPath')
                    ->with($location, $quantity, $year, $month)
                    ->willReturn($bufferPath);

        $bufferCreator = new BufferCreator($dataLocator);
        $bufferCreator->createMonthBuffer($location, $quantity, $year, $month);

        $buffer = Buffer::fromFile($bufferPath, BufferCreator::BUFFER_FORMAT);
        $this->assertEquals(0, count($buffer));
        unlink($bufferPath);
        rmdir($dataDir . '/' . $location . '/' . $quantity . '/' . $year);
        rmdir($dataDir . '/' . $location . '/' . $quantity);
        rmdir($dataDir . '/' . $location);
        rmdir($dataDir);
    }

    /**
     * Check that an exception is thrown when attempting to create a buffer for
     * the data of a year, that already exists.
     */
    public function testCreateYearBufferWithExistingFile(): void
    {
        $location = 'test_location';
        $quantity = 'test_quantity';
        $year = 2021;
        $bufferPath = sys_get_temp_dir() . '/' . bin2hex(random_bytes(8)) .
                    '.bin';

        $dataLocator = $this->createMock(DataLocator::class);
        $dataLocator->expects($this->once())
                    ->method('getYearPath')
                    ->with($location, $quantity, $year)
                    ->willReturn($bufferPath);
        if (false === touch($bufferPath)) {
            throw new RunTimeException(
                'Could not create temporary file ' . $bufferPath . ' for test!',
            );
        }

        $this->expectException(IOException::class);
        $this->expectExceptionMessage('file exists');

        try {
            $bufferCreator = new BufferCreator($dataLocator);
            $bufferCreator->createYearBuffer($location, $quantity, $year);
        } catch (Exception $e) {
            unlink($bufferPath);

            throw $e;
        }
    }

    /**
     * Check that a buffer for the weather data of a year can be
     * created.
     */
    public function testCreateYearBuffer(): void
    {
        $location = 'test_location';
        $quantity = 'test_quantity';
        $year = 2021;
        $dataDir = sys_get_temp_dir() . '/' . bin2hex(random_bytes(8));
        $bufferPath =  join(
            '/',
            [
                $dataDir,
                $location,
                $quantity,
                $year . '.bin',
            ],
        );

        $dataLocator = $this->createMock(DataLocator::class);
        $dataLocator->expects($this->once())
                    ->method('getYearPath')
                    ->with($location, $quantity, $year)
                    ->willReturn($bufferPath);

        $bufferCreator = new BufferCreator($dataLocator);
        $bufferCreator->createYearBuffer($location, $quantity, $year);

        $buffer = Buffer::fromFile($bufferPath, BufferCreator::BUFFER_FORMAT);
        $this->assertEquals(0, count($buffer));
        unlink($bufferPath);
        rmdir($dataDir . '/' . $location . '/' . $quantity);
        rmdir($dataDir . '/' . $location);
        rmdir($dataDir);
    }
}
