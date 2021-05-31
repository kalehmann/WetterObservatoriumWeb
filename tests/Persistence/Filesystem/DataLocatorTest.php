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

use KaLehmann\WetterObservatoriumWeb\Persistence\Filesystem\DataLocator;
use PHPUnit\Framework\TestCase;
use function sys_get_temp_dir;

/**
 * Test cases for the DataLocator.
 */
class DataLocatorTest extends TestCase
{
    /**
     * Tests that the ring buffer with the data of the last 24 hours of a
     * quantity on a specific location is located.
     */
    public function testGet24hPath(): void
    {
        $dataDir = sys_get_temp_dir();
        $dataLocator = new DataLocator($dataDir);
        $location = 'aquarium';
        $quantity = 'temperature';

        $expectedPath = $dataDir . DIRECTORY_SEPARATOR .
                      $location . DIRECTORY_SEPARATOR .
                      $quantity . DIRECTORY_SEPARATOR .
                      '24h.dat';
        $this->assertEquals(
            $expectedPath,
            $dataLocator->get24hPath(
                $location,
                $quantity,
            ),
        );
    }

    /**
     * Tests that the ring buffer with the data of the last 31 days of a
     * quantity on a specific location is located.
     */
    public function testGet31dPath(): void
    {
        $dataDir = sys_get_temp_dir();
        $dataLocator = new DataLocator($dataDir);
        $location = 'outdoor';
        $quantity = 'humidity';

        $expectedPath = $dataDir . DIRECTORY_SEPARATOR .
                      $location . DIRECTORY_SEPARATOR .
                      $quantity . DIRECTORY_SEPARATOR .
                      '31d.dat';
        $this->assertEquals(
            $expectedPath,
            $dataLocator->get31dPath(
                $location,
                $quantity,
            ),
        );
    }

    /**
     * Tests that the data directory can be obtained from the data locator.
     */
    public function testGetDataDirectory(): void
    {
        $dataDir = sys_get_temp_dir();
        $dataLocator = new DataLocator($dataDir);

        $this->assertEquals(
            $dataDir,
            $dataLocator->getDataDirectory(),
        );
    }

    /**
     * Tests that the file with the data of a specific year of a quantity on a
     * specific location is located.
     */
    public function testGetYearPath(): void
    {
        $dataDir = sys_get_temp_dir();
        $dataLocator = new DataLocator($dataDir);
        $location = 'outdoor';
        $quantity = 'sunshine';
        $year = 2021;

        $expectedPath = $dataDir . DIRECTORY_SEPARATOR .
                      $location . DIRECTORY_SEPARATOR .
                      $quantity . DIRECTORY_SEPARATOR .
                      $year . '.dat';
        $this->assertEquals(
            $expectedPath,
            $dataLocator->getYearPath(
                $location,
                $quantity,
                $year,
            ),
        );
    }

     /**
     * Tests that the file with the data of a specific month in a year of a
     * quantity on a specific location is located.
     */
    public function testGetMonthPath(): void
    {
        $dataDir = sys_get_temp_dir();
        $dataLocator = new DataLocator($dataDir);
        $location = 'outdoor';
        $quantity = 'particulates';
        $year = 2021;
        $month = 05;

        $expectedPath = $dataDir . DIRECTORY_SEPARATOR .
                      $location . DIRECTORY_SEPARATOR .
                      $quantity . DIRECTORY_SEPARATOR .
                      $year . DIRECTORY_SEPARATOR .
                      $month . '.dat';
        $this->assertEquals(
            $expectedPath,
            $dataLocator->getMonthPath(
                $location,
                $quantity,
                $year,
                $month,
            ),
        );
    }
}
