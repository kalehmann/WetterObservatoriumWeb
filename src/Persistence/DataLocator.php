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

use \RunTimeException;
use function is_dir;
use function realpath;

/**
 * Locate the files where the data for arbitrary quantities on specific
 * locations will be stored.
 */
class DataLocator
{
    /**
     * The directory where all data is stored.
     */
    private string $dataDirectory;

    public function __construct(
        string $dataDirectory,
    ) {
        $path = realpath($dataDirectory);
        if (false === $path) {
            throw new RunTimeException(
                'Could not determine the canonccalized path of "' .
                $dataDirectory . '". Does the path exist and has the script ' .
                'executable permissions on all directories in the hierarchy?'
            );
        }
        if (false === is_dir($path)) {
            throw new RunTimeException(
                $dataDir , ' is not a directory'
            );
        }
        $this->dataDirectory = $path;
    }

    /**
     * Returns the path to the file with the ring buffer for the data of the
     * last 24 hours for a quantity on a specific location.
     */
    public function get24hPath(
        string $location,
        string $quantity,
    ): string {
        return join(
            DIRECTORY_SEPARATOR,
            [
                $this->getBasePath($location, $quantity),
                '24h.dat',
            ],
        );
    }

    /**
     * Returns the path to the file with the ring buffer for the data of the
     * last 31 days for a quantity on a specific location.
     */
    public function get31dPath(
        string $location,
        string $quantity,
    ): string {
        return join(
            DIRECTORY_SEPARATOR,
            [
                $this->getBasePath($location, $quantity),
                '31d.dat',
            ],
        );
    }

    /**
     * Returns the path to the file with the data for the given year for a
     * quantity on a specific location.
     */
    public function getYearPath(
        string $location,
        string $quantity,
        int $year,
    ): string {
        return join(
            DIRECTORY_SEPARATOR,
            [
                $this->getBasePath($location, $quantity),
                strval($year) . '.dat',
            ],
        );
    }

    /**
     * Returns the path to the file with the data for the given year and month
     * for a quantity on a specific location.
     */
    public function getMonthPath(
        string $location,
        string $quantity,
        int $year,
        int $month,
    ): string {
        return join(
            DIRECTORY_SEPARATOR,
            [
                $this->getBasePath($location, $quantity),
                strval($year),
                strval($month) . '.dat',
            ],
        );
    }

    /**
     * Returns the path to the directory where all data for a quantity on a
     * specific location is stored.
     */
    private function getBasePath(
        string $location,
        string $quantity,
    ): string {
        return join(
            DIRECTORY_SEPARATOR,
            [
                $this->dataDirectory,
                $location,
                $quantity,
            ],
        );
    }
}
