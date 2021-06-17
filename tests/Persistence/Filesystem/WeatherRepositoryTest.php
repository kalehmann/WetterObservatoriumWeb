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

use DateInterval;
use DateTimeImmutable;
use KaLehmann\WetterObservatoriumWeb\Persistence\Filesystem\Buffer;
use KaLehmann\WetterObservatoriumWeb\Persistence\Filesystem\BufferCreator;
use KaLehmann\WetterObservatoriumWeb\Persistence\Filesystem\DataLocator;
use KaLehmann\WetterObservatoriumWeb\Persistence\Filesystem\IOException;
use KaLehmann\WetterObservatoriumWeb\Persistence\Filesystem\RingBuffer;
use KaLehmann\WetterObservatoriumWeb\Persistence\Filesystem\WeatherRepository;
use PHPUnit\Framework\TestCase;
use RunTimeException;

/**
 * Test cases for the WeatherRepository.
 */
class WeatherRepositoryTest extends TestCase
{
    private string $dataDirectory;

    private DataLocator $dataLocator;

    private string $location = 'test_location';

    private int $month = 5;

    private string $quantity = 'test_quantity';

    private int $year = 2021;

    /**
     * Setup all the persistent data for the test cases.
     */
    public function setUp(): void
    {
        $tempDir = sys_get_temp_dir();
        $randomDir = $tempDir . '/' . bin2hex(random_bytes(8));
        if (false === mkdir($randomDir)) {
            throw new RunTimeException(
                'Cannot create temporary directory at ' . $randomDir,
            );
        }
        $this->dataDirectory = $randomDir;
        $this->dataLocator = new DataLocator($randomDir);
        $bufferCreator = new BufferCreator($this->dataLocator);
        $bufferCreator->create24hBuffer(
            $this->location,
            $this->quantity,
        );
        $bufferCreator->create31dBuffer(
            $this->location,
            $this->quantity,
        );
        $bufferCreator->createMonthBuffer(
            $this->location,
            $this->quantity,
            $this->year,
            $this->month,
        );
        $bufferCreator->createYearBuffer(
            $this->location,
            $this->quantity,
            $this->year,
        );
    }

    /**
     * Clean up the persistent data for the test cases.
     */
    public function tearDown(): void
    {
        unlink(
            $this->dataLocator->get24hPath(
                $this->location,
                $this->quantity,
            ),
        );
        unlink(
            $this->dataLocator->get31dPath(
                $this->location,
                $this->quantity,
            ),
        );
        unlink(
            $this->dataLocator->getMonthPath(
                $this->location,
                $this->quantity,
                $this->year,
                $this->month,
            ),
        );
        unlink(
            $this->dataLocator->getYearPath(
                $this->location,
                $this->quantity,
                $this->year,
            ),
        );
        rmdir(
            $this->dataDirectory . '/' . $this->location . '/' .
            $this->quantity . '/' . $this->year,
        );
        rmdir(
            $this->dataDirectory . '/' . $this->location . '/' .
            $this->quantity,
        );
        rmdir($this->dataDirectory . '/' . $this->location);
        rmdir($this->dataDirectory);
    }

    /**
     * Check that calling the persist method creates all buffers if they do not
     * exist already.
     */
    public function testPersistCreatesBuffersIfTheyDoNotExist(): void
    {
        $path24h = $this->dataDirectory . '/test_24h.bin';
        $path31d = $this->dataDirectory . '/test_31d.bin';
        $pathMonth = $this->dataDirectory . '/test_month.bin';
        $pathYear = $this->dataDirectory . '/test_year.bin';

        $dataLocator = $this->createMock(DataLocator::class);
        $dataLocator->expects($this->atleastOnce())
                    ->method('get24hPath')
                    ->with($this->location, $this->quantity)
                    ->willReturn($path24h);
        $dataLocator->expects($this->atleastOnce())
                    ->method('get31dPath')
                    ->with($this->location, $this->quantity)
                    ->willReturn($path31d);
        $dataLocator->expects($this->atleastOnce())
                    ->method('getMonthPath')
                    ->with(
                        $this->location,
                        $this->quantity,
                        $this->year,
                        $this->month,
                    )
                    ->willReturn($pathMonth);
        $dataLocator->expects($this->atleastOnce())
                    ->method('getYearPath')
                    ->with($this->location, $this->quantity, $this->year)
                    ->willReturn($pathYear);

        $bufferCreator = new BufferCreator($dataLocator);

        $weatherRepository = new WeatherRepository(
            $bufferCreator,
            $dataLocator,
        );
        $this->assertFileDoesNotExist($path24h);
        $this->assertFileDoesNotExist($path31d);
        $this->assertFileDoesNotExist($pathMonth);
        $this->assertFileDoesNotExist($pathYear);

        $timestamp = (new DateTimeImmutable())
                   ->setDate($this->year, $this->month, 2);
        $value = 42;

        $weatherRepository->persist(
            $this->location,
            $this->quantity,
            $value,
            $timestamp,
        );

        $this->assertFileExists($path24h);
        $this->assertFileExists($path31d);
        $this->assertFileExists($pathMonth);
        $this->assertFileExists($pathYear);

        unlink($path24h);
        unlink($path31d);
        unlink($pathMonth);
        unlink($pathYear);
    }

    /**
     * Check that calling persist for the first time after midnight condensates
     * the data of the previous day into a single value.
     */
    public function testPersistCondensatesTheDataOfThePreviousDay(): void
    {
        $path24h = $this->dataLocator->get24hPath(
            $this->location,
            $this->quantity,
        );
        $pathYear = $this->dataLocator->getYearPath(
            $this->location,
            $this->quantity,
            $this->year,
        );
        $midnight = new DateTimeImmutable('2021-05-23T00:00:00');
        $unixtime = $midnight->getTimestamp();

        RingBuffer::operateExclusive(
            $path24h,
            BufferCreator::BUFFER_FORMAT,
            function (RingBuffer $buffer) use ($unixtime) {
                $buffer->addEntry(
                    [
                        $unixtime - 10800,
                        1,
                    ],
                );
                $buffer->addEntry(
                    [
                        $unixtime - 7200,
                        2,
                    ],
                );
                $buffer->addEntry(
                    [
                        $unixtime - 200,
                        3,
                    ],
                );
            },
        );
        $ringBuffer = RingBuffer::fromFile(
            $path24h,
            BufferCreator::BUFFER_FORMAT,
        );

        $bufferCreator = new BufferCreator($this->dataLocator);
        $weatherRepository = new WeatherRepository(
            $bufferCreator,
            $this->dataLocator,
        );

        $weatherRepository->persist(
            $this->location,
            $this->quantity,
            4,
            $midnight->add(new DateInterval('PT30M')),
        );

        $ringBuffer = RingBuffer::fromFile(
            $path24h,
            BufferCreator::BUFFER_FORMAT,
        );
        $this->assertEquals(
            [
                [$unixtime - 10800, 1],
                [$unixtime - 7200, 2],
                [$unixtime - 200, 3],
                [$unixtime + 1800, 4],

            ],
            array_slice(
                iterator_to_array($ringBuffer),
                -4,
                4,
            ),
        );

        $yearBuffer = Buffer::fromFile(
            $pathYear,
            BufferCreator::BUFFER_FORMAT,
        );
        [$timestamp, $value] = iterator_to_array($yearBuffer)[0];
        $this->assertEquals(2, $value);
        $this->assertEquals(
            '2021-05-22',
            date('Y-m-d', $timestamp),
        );
    }

    /**
     * Check that calling persist for the first time in an hour condensates
     * the data of the previous hour into a single value.
     */
    public function testPersistCondensatesTheDataOfThePreviousHour(): void
    {
        $path24h = $this->dataLocator->get24hPath(
            $this->location,
            $this->quantity,
        );
        $path31d = $this->dataLocator->get31dPath(
            $this->location,
            $this->quantity,
        );
        $pathMonth = $this->dataLocator->getMonthPath(
            $this->location,
            $this->quantity,
            $this->year,
            $this->month,
        );
        $hour = new DateTimeImmutable('2021-05-30T10:00:00');
        $unixtime = $hour->getTimestamp();

        RingBuffer::operateExclusive(
            $path24h,
            BufferCreator::BUFFER_FORMAT,
            function (RingBuffer $buffer) use ($unixtime) {
                $buffer->addEntry(
                    [
                        $unixtime - 1000,
                        31,
                    ],
                );
                $buffer->addEntry(
                    [
                        $unixtime - 800,
                        41,
                    ],
                );
                $buffer->addEntry(
                    [
                        $unixtime - 600,
                        59,
                    ],
                );
                $buffer->addEntry(
                    [
                        $unixtime - 400,
                        26,
                    ],
                );
                $buffer->addEntry(
                    [
                        $unixtime - 200,
                        53,
                    ],
                );
            },
        );
        $ringBuffer = RingBuffer::fromFile(
            $path24h,
            BufferCreator::BUFFER_FORMAT,
        );

        $bufferCreator = new BufferCreator($this->dataLocator);
        $weatherRepository = new WeatherRepository(
            $bufferCreator,
            $this->dataLocator,
        );

        $weatherRepository->persist(
            $this->location,
            $this->quantity,
            123,
            $hour->add(new DateInterval('PT30M')),
        );

        $ringBuffer = RingBuffer::fromFile(
            $path24h,
            BufferCreator::BUFFER_FORMAT,
        );
        $this->assertEquals(
            [
                [$unixtime - 1000, 31],
                [$unixtime - 800, 41],
                [$unixtime - 600, 59],
                [$unixtime - 400, 26],
                [$unixtime - 200, 53],
                [$unixtime + 1800, 123],

            ],
            array_slice(
                iterator_to_array($ringBuffer),
                -6,
                6,
            ),
        );

        $monthBuffer = Buffer::fromFile(
            $pathMonth,
            BufferCreator::BUFFER_FORMAT,
        );
        [$timestamp, $value] = iterator_to_array($monthBuffer)[0];
        $this->assertEquals(42, $value);
        $this->assertEquals(
            '2021-05-30 09',
            date('Y-m-d H', $timestamp),
        );
    }

    /**
     * Check that a second call to persist within 4 minutes does nothing.
     */
    public function testPersistRequiresAMinimalIntervalOf4Minutes(): void
    {
        $path24h = $this->dataLocator->get24hPath(
            $this->location,
            $this->quantity,
        );
        $hour = new DateTimeImmutable('2021-05-18T01:00:00');
        $unixtime = $hour->getTimestamp();

        $bufferCreator = new BufferCreator($this->dataLocator);
        $weatherRepository = new WeatherRepository(
            $bufferCreator,
            $this->dataLocator,
        );

        $weatherRepository->persist(
            $this->location,
            $this->quantity,
            123,
            $hour,
        );
        $weatherRepository->persist(
            $this->location,
            $this->quantity,
            123,
            $hour->add(new DateInterval('PT3M')),
        );
        $this->assertEquals(
            [
                $unixtime => 123,
            ],
            $weatherRepository->query24h(
                $this->location,
                $this->quantity,
            ),
        );
    }

    /**
     * Check that trying to query the data of the last 24 hours without
     * the 24h ring buffer results in an exception.
     */
    public function testQuery24hWithoutBuffer(): void
    {
        $bufferCreator = new BufferCreator($this->dataLocator);
        $weatherRepository = new WeatherRepository(
            $bufferCreator,
            $this->dataLocator,
        );

        $this->expectException(IOException::class);
        $weatherRepository->query24h(
            'unknown_location',
            'unknown_quantity',
        );
    }

    /**
     * Check that the data of the 24h ring buffer for a location and quantity
     * can be queried.
     */
    public function testQuery24h(): void
    {
        $path24h = $this->dataLocator->get24hPath(
            $this->location,
            $this->quantity,
        );
        $now = new DateTimeImmutable('2021-05-10T12:00:00');
        $unixtime = $now->getTimestamp();
        RingBuffer::operateExclusive(
            $path24h,
            BufferCreator::BUFFER_FORMAT,
            function (RingBuffer $buffer) use ($unixtime) {
                $buffer->addEntry(
                    [
                        $unixtime,
                        10,
                    ],
                );
                $buffer->addEntry(
                    [
                        $unixtime + 200,
                        12,
                    ],
                );
                $buffer->addEntry(
                    [
                        $unixtime + 400,
                        11,
                    ],
                );
            },
        );

        $weatherRepository = new WeatherRepository(
            new BufferCreator($this->dataLocator),
            $this->dataLocator,
        );
        $this->assertEquals(
            [
                $unixtime => 10,
                $unixtime + 200 => 12,
                $unixtime + 400 => 11,
            ],
            $weatherRepository->query24h(
                $this->location,
                $this->quantity,
            ),
        );
    }

    /**
     * Check that trying to query the data of the last 31 days without
     * the 31d ring buffer results in an exception.
     */
    public function testQuery31dWithoutBuffer(): void
    {
        $bufferCreator = new BufferCreator($this->dataLocator);
        $weatherRepository = new WeatherRepository(
            $bufferCreator,
            $this->dataLocator,
        );

        $this->expectException(IOException::class);
        $weatherRepository->query31d(
            'unknown_location',
            'unknown_quantity',
        );
    }

    /**
     * Check that the data of the 31d ring buffer for a location and quantity
     * can be queried.
     */
    public function testQuery31d(): void
    {
        $path31d = $this->dataLocator->get31dPath(
            $this->location,
            $this->quantity,
        );
        $now = new DateTimeImmutable('2021-05-10T12:00:00');
        $unixtime = $now->getTimestamp();
        RingBuffer::operateExclusive(
            $path31d,
            BufferCreator::BUFFER_FORMAT,
            function (RingBuffer $buffer) use ($unixtime) {
                $buffer->addEntry(
                    [
                        $unixtime,
                        10,
                    ],
                );
                $buffer->addEntry(
                    [
                        $unixtime + 3600 * 24,
                        12,
                    ],
                );
                $buffer->addEntry(
                    [
                        $unixtime + 3600 * 48,
                        11,
                    ],
                );
            },
        );

        $weatherRepository = new WeatherRepository(
            new BufferCreator($this->dataLocator),
            $this->dataLocator,
        );
        $this->assertEquals(
            [
                $unixtime =>  10,
                $unixtime + 3600 * 24 => 12,
                $unixtime + 3600 * 48 => 11,
            ],
            $weatherRepository->query31d(
                $this->location,
                $this->quantity,
            ),
        );
    }

    /**
     * Check that all locations where data has been previously measured can be
     * queried.
     */
    public function testQueryLocations(): void
    {
        $bufferCreator = new BufferCreator($this->dataLocator);
        $bufferCreator->create24hBuffer(
            'aquarium',
            $this->quantity,
        );
        $bufferCreator->create24hBuffer(
            'home',
            $this->quantity,
        );

        $weatherRepository = new WeatherRepository(
            $bufferCreator,
            $this->dataLocator,
        );
        $this->assertEqualsCanonicalizing(
            ['aquarium', 'home', $this->location],
            $weatherRepository->queryLocations(),
        );

        $pathAquarium24h = $this->dataLocator->get24hPath(
            'aquarium',
            $this->quantity,
        );
        $pathHome24h = $this->dataLocator->get24hPath(
            'home',
            $this->quantity,
        );
        unlink($pathAquarium24h);
        unlink($pathHome24h);
        rmdir($this->dataDirectory . '/aquarium/' . $this->quantity);
        rmdir($this->dataDirectory . '/home/' . $this->quantity);
        rmdir($this->dataDirectory . '/aquarium');
        rmdir($this->dataDirectory . '/home');
    }

    /**
     * Check that trying to query the data of a month without the buffer for
     * the month existing results in an exception.
     */
    public function testQueryMonthWithoutBuffer(): void
    {
        $bufferCreator = new BufferCreator($this->dataLocator);
        $weatherRepository = new WeatherRepository(
            $bufferCreator,
            $this->dataLocator,
        );

        $this->expectException(IOException::class);
        $weatherRepository->queryMonth(
            $this->location,
            $this->quantity,
            $this->year,
            $this->month + 1,
        );
    }

    /**
     * Check that the data of a specific month for a location and quantity can
     * be queried.
     */
    public function testQueryMonth(): void
    {
        $pathMonth = $this->dataLocator->getMonthPath(
            $this->location,
            $this->quantity,
            $this->year,
            $this->month,
        );
        $now = new DateTimeImmutable('2021-05-10T12:00:00');
        $unixtime = $now->getTimestamp();
        Buffer::operateExclusive(
            $pathMonth,
            BufferCreator::BUFFER_FORMAT,
            function (Buffer $buffer) use ($unixtime) {
                $buffer->addEntry(
                    [
                        $unixtime,
                        10,
                    ],
                );
                $buffer->addEntry(
                    [
                        $unixtime + 3600 * 24,
                        12,
                    ],
                );
                $buffer->addEntry(
                    [
                        $unixtime + 3600 * 48,
                        11,
                    ],
                );
            },
        );

        $weatherRepository = new WeatherRepository(
            new BufferCreator($this->dataLocator),
            $this->dataLocator,
        );
        $this->assertEquals(
            [
                $unixtime => 10,
                $unixtime + 3600 * 24 => 12,
                $unixtime + 3600 * 48 => 11,
            ],
            $weatherRepository->queryMonth(
                $this->location,
                $this->quantity,
                $this->year,
                $this->month,
            ),
        );
    }

    /**
     * Check that all locations where data has been previously measured can be
     * queried.
     */
    public function testQueryQuantities(): void
    {
        $bufferCreator = new BufferCreator($this->dataLocator);
        $bufferCreator->create24hBuffer(
            'home',
            'quantity01',
        );
        $bufferCreator->create24hBuffer(
            'home',
            'quantity02',
        );

        $weatherRepository = new WeatherRepository(
            $bufferCreator,
            $this->dataLocator,
        );
        $this->assertEqualsCanonicalizing(
            ['quantity01', 'quantity02'],
            $weatherRepository->queryQuantities('home'),
        );

        $pathQ1_24h = $this->dataLocator->get24hPath(
            'home',
            'quantity01',
        );
        $pathQ2_24h = $this->dataLocator->get24hPath(
            'home',
            'quantity02',
        );
        unlink($pathQ1_24h);
        unlink($pathQ2_24h);
        rmdir($this->dataDirectory . '/home/quantity01');
        rmdir($this->dataDirectory . '/home/quantity02');
        rmdir($this->dataDirectory . '/home');
    }

    /**
     * Check that trying to query the data of a year without the buffer for
     * the year existing results in an exception.
     */
    public function testQueryYearWithoutBuffer(): void
    {
        $bufferCreator = new BufferCreator($this->dataLocator);
        $weatherRepository = new WeatherRepository(
            $bufferCreator,
            $this->dataLocator,
        );

        $this->expectException(IOException::class);
        $weatherRepository->queryYear(
            $this->location,
            $this->quantity,
            $this->year + 1,
        );
    }

    /**
     * Check that the data of a specific year for a location and quantity can
     * be queried.
     */
    public function testQueryYear(): void
    {
        $pathYear = $this->dataLocator->getYearPath(
            $this->location,
            $this->quantity,
            $this->year,
        );
        $now = new DateTimeImmutable('2021-05-10T12:00:00');
        $unixtime = $now->getTimestamp();
        Buffer::operateExclusive(
            $pathYear,
            BufferCreator::BUFFER_FORMAT,
            function (Buffer $buffer) use ($unixtime) {
                $buffer->addEntry(
                    [
                        $unixtime,
                        10,
                    ],
                );
                $buffer->addEntry(
                    [
                        $unixtime + 3600 * 24,
                        12,
                    ],
                );
                $buffer->addEntry(
                    [
                        $unixtime + 3600 * 48,
                        11,
                    ],
                );
            },
        );

        $weatherRepository = new WeatherRepository(
            new BufferCreator($this->dataLocator),
            $this->dataLocator,
        );
        $this->assertEquals(
            [
                $unixtime => 10,
                $unixtime + 3600 * 24 => 12,
                $unixtime + 3600 * 48 => 11,
            ],
            $weatherRepository->queryYear(
                $this->location,
                $this->quantity,
                $this->year,
            ),
        );
    }
}
