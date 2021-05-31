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

namespace KaLehmann\WetterObservatoriumWeb\Persistence\Filesystem;

/**
 * Creates buffers for storing weather data.
 */
class BufferCreator
{
    public const BUFFER_FORMAT = DataPacker::UNSIGNED_LONG_LONG_LE .
                               DataPacker::UNSIGNED_SHORT_LE;

    public const BUFFER_SIZE_24H = 360;

    public const BUFFER_SIZE_31D = 930;

    private DataLocator $dataLocator;

    public function __construct(
        DataLocator $dataLocator,
    ) {
        $this->dataLocator = $dataLocator;
    }

    /**
     * Create a ring buffer for the storage of the data of the last 24 hours.
     *
     * This method creates a new ring buffer at the path for the location and
     * quantity with 360 entries (one per every 4 minutes).
     */
    public function create24hBuffer(
        string $location,
        string $quantity,
    ): void {
        $bufferPath = $this->dataLocator->get24hPath($location, $quantity);
        if (file_exists($bufferPath)) {
            throw new IOException(
                'Can not create buffer at ' . $bufferPath .
                ' -  the file exists.',
            );
        }

        $ringBuffer = RingBuffer::createNew(
            self::BUFFER_SIZE_24H,
            self::BUFFER_FORMAT,
        );

        $dir = dirname($bufferPath);
        if (!is_dir($dir) && false === mkdir(directory: $dir, recursive: true)) {
            throw new IOException('Could not create directory ' . $dir);
        }
        if (false === file_put_contents($bufferPath, (string)$ringBuffer)) {
            throw new IOException('Could not save buffer at ' . $bufferPath);
        }
    }

    /**
     * Create a ring buffer for the storage of the data of the last 31 days.
     *
     * This method creates a new ring buffer at the path for the location and
     * quantity with 930 entries (one per every 48 minutes).
     */
    public function create31dBuffer(
        string $location,
        string $quantity,
    ): void {
        $bufferPath = $this->dataLocator->get31dPath($location, $quantity);
        if (file_exists($bufferPath)) {
            throw new IOException(
                'Can not create buffer at ' . $bufferPath .
                ' -  the file exists.',
            );
        }

        $ringBuffer = RingBuffer::createNew(
            self::BUFFER_SIZE_31D,
            self::BUFFER_FORMAT,
        );
        $dir = dirname($bufferPath);
        if (!is_dir($dir) && false === mkdir(directory: $dir, recursive: true)) {
            throw new IOException('Could not create directory ' . $dir);
        }
        if (false === file_put_contents($bufferPath, (string)$ringBuffer)) {
            throw new IOException('Could not save buffer at ' . $bufferPath);
        }
    }

    /**
     * Create a buffer for the storage of the data of a specific month.
     */
    public function createMonthBuffer(
        string $location,
        string $quantity,
        int $year,
        int $month,
    ): void {
        $bufferPath = $this->dataLocator->getMonthPath(
            $location,
            $quantity,
            $year,
            $month,
        );
        if (file_exists($bufferPath)) {
            throw new IOException(
                'Can not create buffer at ' . $bufferPath .
                ' -  the file exists.',
            );
        };

        $buffer = Buffer::createNew(self::BUFFER_FORMAT);
        $dir = dirname($bufferPath);
        if (!is_dir($dir) && false === mkdir(directory: $dir, recursive: true)) {
            throw new IOException('Could not create directory ' . $dir);
        }
        if (false === file_put_contents($bufferPath, (string)$buffer)) {
            throw new IOException('Could not save buffer at ' . $bufferPath);
        }
    }

    /**
     * Create a buffer for the storage of the data of a specific month.
     */
    public function createYearBuffer(
        string $location,
        string $quantity,
        int $year,
    ): void {
        $bufferPath = $this->dataLocator->getYearPath(
            $location,
            $quantity,
            $year,
        );
        if (file_exists($bufferPath)) {
            throw new IOException(
                'Can not create buffer at ' . $bufferPath .
                ' -  the file exists.',
            );
        }

        $buffer = Buffer::createNew(self::BUFFER_FORMAT);
        $dir = dirname($bufferPath);
        if (!is_dir($dir) && false === mkdir(directory: $dir, recursive: true)) {
            throw new IOException('Could not create directory ' . $dir);
        }
        if (false === file_put_contents($bufferPath, (string)$buffer)) {
            throw new IOException('Could not save buffer at ' . $bufferPath);
        }
    }
}
