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

namespace KaLehmann\WetterObservatoriumWeb;

if (!function_exists('KaLehmann\WetterObservatoriumWeb\env_var')) {
    /**
     * Loads the value of an environment variable.
     * The following sources are used to find the value (in the listed order):
     *  - $_ENV
     *  - $_SERVER
     *  - getenv
     *
     * @param string $name the name of the environment variable.
     * @param null|string $default the value to return if the variable cannot
     *                             be found.
     * @return null|string the value of the variable or $default if the variable
     *                     could not be found.
     */
    function env_var(
        string $name,
        ?string $default = null,
    ): ?string {
        $var = $_ENV[$name] ?? $_SERVER[$name] ?? null;
        if ($var) {
            return $var;
        }

        $var = getenv($name);
        if ($var) {
            return $var;
        }

        return $default;
    }
}
