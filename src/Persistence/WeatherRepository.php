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

namespace KaLehmann\WetterObservatoriumWeb\Persistence;

use \DateTimeImmutable;

/**
 * Repository for all weather related data.
 */
class WeatherRepository
{
    private const BUFFER_FORMAT
        = DataPacker::UNSIGNED_LONG_LONG_LE . DataPacker::UNSIGNED_SHORT_LE;

    /**
     * @var array<string, Buffer>
     */
    private array $buffers;

    private BufferCreator $bufferCreator;

    private DataLocator $dataLocator;

    /**
     * @var array<string, RingBuffer>
     */
    private array $ringBuffers;

    public function __construct(
        BufferCreator $bufferCreator,
        DataLocator $dataLocator,
    ) {
        $this->bufferCreator = $bufferCreator;
        $this->dataLocator = $dataLocator;
    }

    /**
     * Persist data of a specific quantity collected on a specific location in
     * the file system.
     *
     * @param string $location the location where the data should be queried
     *                         for.
     * @param string $quantity the collected quantity.
     * @param int $value the measured value.
     * @param DateTimeImmutable $timestamp the time when the value was measured.
     */
    public function persist(
        string $location,
        string $quantity,
        int $value,
        DateTimeImmutable $timestamp,
    ): void {
        $day = (int)$timestamp->format('d');
        $month = (int)$timestamp->format('m');
        $year = (int)$timestamp->format('Y');
        $path24h = $this->dataLocator->get24hPath($location, $quantity);
        $path31d = $this->dataLocator->get31dPath($location, $quantity);
        $monthPath = $this->dataLocator->getMonthPath(
            $location,
            $quantity,
            $year,
            $month,
        );
        $yearPath = $this->dataLocator->getYearPath(
            $location,
            $quantity,
            $year,
        );

        if (false === file_exists($path24h)) {
            $this->bufferCreator->create24hBuffer(
                $location,
                $quantity,
            );
        }

        if (false === file_exists($path31d)) {
            $this->bufferCreator->create31dBuffer(
                $location,
                $quantity,
            );
        }

        if (false === file_exists($monthPath)) {
            $this->bufferCreator->createMonthBuffer(
                $location,
                $quantity,
                $year,
                $month,
            );
        }

        if (false === file_exists($yearPath)) {
            $this->bufferCreator->createYearBuffer(
                $location,
                $quantity,
                $year,
            );
        }

        $unixTime = $timestamp->getTimestamp();
        $lastHour = $unixTime -
                  $unixTime % WeatherCondensator::SECONDS_PER_HOUR;
        $lastMidnight = $unixTime -
                      $unixTime % WeatherCondensator::SECONDS_PER_DAY;

        $this->operateExclusiveOnRingBuffer(
            $path24h,
            self::BUFFER_FORMAT,
            function(RingBuffer $ringBuffer) use (
                $lastHour,
                $lastMidnight,
                $monthPath,
                $timestamp,
                $value,
                $yearPath,
            ) {
                [0 => $lastEntryTime, 1 => $_ ] = $ringBuffer->lastEntry();
                // First check if last entry time is not zero, to avoid
                // working on a fresh buffer.
                if ($lastEntryTime !== 0 && $lastEntryTime < $lastMidnight) {
                    $this->operateExclusiveOnBuffer(
                        $yearPath,
                        self::BUFFER_FORMAT,
                        function (Buffer $yearBuffer) use (
                            $lastEntryTime,
                            $lastMidnight,
                            $ringBuffer,
                        ) {
                            $yearBuffer->addEntry(
                                [
                                    $lastEntryTime,
                                    WeatherCondensator::condensateDay(
                                        $ringBuffer,
                                        $lastMidnight,
                                    ),
                                ],
                            );
                        },
                    );
                }
                // First check if last entry time is not zero, to avoid
                // working on a fresh buffer.
                if ($lastEntryTime !== 0 && $lastEntryTime < $lastHour) {
                    $this->operateExclusiveOnBuffer(
                        $monthPath,
                        self::BUFFER_FORMAT,
                        function (Buffer $monthBuffer) use (
                            $lastEntryTime,
                            $lastHour,
                            $ringBuffer,
                        ) {
                            $monthBuffer->addEntry(
                                [
                                    $lastEntryTime,
                                    WeatherCondensator::condensateHour(
                                        $ringBuffer,
                                        $lastHour,
                                    ),
                                ],
                            );
                        },
                    );
                }
                $ringBuffer->addEntry([
                    $timestamp->getTimestamp(),
                    $value,
                ]);
            },
        );
    }

    /**
     * Returns the all the data collected for a quantity on a specific location
     * in the given month of the last 24 hours.
     *
     * @param string $location the location where the data should be queried
     *                         for.
     * @param string $quantity the collected quantity.
     * @return array<int, array<int, int>> the data collected in the given year.
     */
    public function query24h(
        string $location,
        string $quantity,
    ): array {
        $path24h = $this->dataLocator->get24hPath($location, $quantity);
        $buffer24h = $this->openRingBuffer($path24h, self::BUFFER_FORMAT);

        return iterator_to_array($buffer24h);
    }

    /**
     * Returns the all the data collected for a quantity on a specific location
     * in the given month of the last 31 days.
     *
     * @param string $location the location where the data should be queried
     *                         for.
     * @param string $quantity the collected quantity.
     * @return array<int, array<int, int>> the data collected in the given 31
     *                                     days.
     */
    public function query31d(
        string $location,
        string $quantity,
    ): array {
        $path31d = $this->dataLocator->get31dPath($location, $quantity);
        $buffer31d = $this->openRingBuffer($path31d, self::BUFFER_FORMAT);

        return iterator_to_array($buffer31d);
    }

    /**
     * Returns the all the data collected for a quantity on a specific location
     * in the given month of the given year.
     *
     * @param string $location the location where the data should be queried
     *                         for.
     * @param string $quantity the collected quantity.
     * @param int $year the year the data was collected in.
     * @param int $month the month the data was collected in.
     * @return array<int, array<int, int>> the data collected in the given month.
     */
    public function queryMonth(
        string $location,
        string $quantity,
        int $year,
        int $month,
    ): array {
        $monthPath = $this->dataLocator->getMonthPath(
            $location,
            $quantity,
            $year,
            $month,
        );
        $buffer = $this->openBuffer($monthPath, self::BUFFER_FORMAT);

        return iterator_to_array($buffer);
    }

    /**
     * Returns the all the data collected for a quantity on a specific location
     * in the given year.
     *
     * @param string $location the location where the data should be queried
     *                         for.
     * @param string $quantity the collected quantity.
     * @param int $year the year the data was collected in.
     * @return array<int, arary<int, int>> the data collected in the given year.
     */
    public function queryYear(
        string $location,
        string $quantity,
        int $year
    ): array {
        $yearPath = $this->dataLocator->getYearPath(
            $location,
            $quantity,
            $year,
        );
        $buffer = $this->openBuffer($yearPath, self::BUFFER_FORMAT);

        return iterator_to_array($buffer);
    }

    /**
     * Opens a buffer. If called multiple times with the same path, the same
     * object is returned.
     *
     * @param string $path the path to the buffer in the filesystem.
     * @param string $format the format of the buffer.
     *
     * @return Buffer the buffer.
     */
    private function openBuffer(string $path, string $format): Buffer
    {
        $buffer = $this->buffers[$path] ?? null;
        if ($buffer) {
            return $buffer;
        }

        $buffer = Buffer::fromFile($path, $format);
        $this->buffers[$path] = $buffer;

        return $buffer;
    }

    /**
     * Opens a ring buffer. If called multiple times with the same path, the
     * same object is returned.
     *
     * @param string $path the path to the ring buffer in the filesystem.
     * @param string $format the format of the ring buffer.
     *
     * @return RingBuffer the ring buffer.
     */
    private function openRingBuffer(string $path, string $format): RingBuffer
    {
        $ringBuffer = $this->ringBuffers[$path] ?? null;
        if ($ringBuffer) {
            return $ringBuffer;
        }

        $ringBuffer = RingBuffer::fromFile($path, $format);
        $this->ringBuffers[$path] = $ringBuffer;

        return $ringBuffer;
    }

    /**
     * Allows exclusive operation on a buffer.
     *
     * @param string $path the path to the buffer in the filesystem.
     * @param string $format the format of the buffer.
     * @param callable $callback a function accepting a
     *                           {@see Bufferinterface::class} as single
     *                           parameter. All actions on the buffer are
     *                           exclusive without concurrent access
     *                           from paralell calls to this method.
     */
    private function operateExclusiveOnBuffer(
        string $path,
        string $format,
        callable $callback,
    ): void {
        Buffer::operateExclusive(
            $path,
            $format,
            function (Buffer $buffer) use ($callback, $path) {
                ($callback)($buffer);
                $this->buffers[$path] = $buffer;
            },
        );
    }

    /**
     * Allows exclusive operation on a ring buffer.
     *
     * @param string $path the path to the ring buffer in the filesystem.
     * @param string $format the format of the ring buffer.
     * @param callable $callback a function accepting a
     *                           {@see Bufferinterface::class} as single
     *                           parameter. All actions on the buffer are
     *                           exclusive without concurrent access
     *                           from paralell calls to this method.
     */
    private function operateExclusiveOnRingBuffer(
        string $path,
        string $format,
        callable $callback,
    ): void {
        RingBuffer::operateExclusive(
            $path,
            $format,
            function (RingBuffer $buffer) use ($callback, $path) {
                ($callback)($buffer);
                $this->ringBuffers[$path] = $buffer;
            },
        );
    }
}
