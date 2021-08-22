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

namespace KaLehmann\WetterObservatoriumWeb\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * A Twig extension with a filter to map a number from one range to another.
 */
class MapRangeExtension extends AbstractExtension
{
    /**
     * Returns the `mapRange` filter.
     */
    public function getFilters(): array
    {
        return [
            new TwigFilter(
                'mapRange',
                [self::class, 'mapRange'],
            ),
        ];
    }

    /**
     * Maps numbers from one range to another.
     *
     * Shamelessly taken from
     * https://www.arduino.cc/reference/en/language/functions/math/map/
     *
     * @param float $value the value from the source range, that should be
     *                     remapped.
     * @param float $minIn the lower bound of the source range.
     * @param float $maxIn the upper bound of the source range.
     * @param float $minOut the lower bound of the target range.
     * @param float $maxOut the upper bound of the target range.
     * @return float the value mapped from the source range to the target range.
     */
    public static function mapRange(
        float $value,
        float $minIn,
        float $maxIn,
        float $minOut,
        float $maxOut,
    ): float {
        return ($value - $minIn) * ($maxOut - $minOut)
            / ($maxIn - $minIn) + $minOut;
    }
}
