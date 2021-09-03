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
use Twig\TwigFunction;

/**
 * A Twig extension with a filter to map a number from one range to another.
 */
class GraphExtension extends AbstractExtension
{
    /**
     * Maximum number of label for the y axis of the plot.
     */
    public const TICK_LIMIT = 20;

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
            new TwigFilter(
                'maxTime',
                [self::class, 'maxTime'],
            ),
            new TwigFilter(
                'minTime',
                [self::class, 'minTime'],
            ),
            new TwigFilter(
                'yLowerLimit',
                [self::class, 'yLowerLimit'],
            ),
            new TwigFilter(
                'yUpperLimit',
                [self::class, 'yUpperLimit'],
            ),
        ];
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction(
                'yTicks',
                [self::class, 'yTicks'],
            ),
        ];
    }

    /**
     * Returns the youngest timestamp contained in $data.
     *
     * @param array<int, float|int> $data
     *
     * @return int
     */
    public static function maxTime(array $data): int
    {
        $timestamps = array_keys($data);
        if (0 === count($timestamps)) {
            return time();
        }

        return max($timestamps);
    }

    /**
     * Returns the oldest timestamp contained in $data.
     *
     * @param array<int, float|int> $data
     *
     * @return int
     */
    public static function minTime(array $data): int
    {
        $timestamps = array_keys($data);
        if (0 === count($timestamps)) {
            return time() - 3600 * 24;
        }

        return min($timestamps);
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
     *
     * @return float the value mapped from the source range to the target range.
     */
    public static function mapRange(
        float $value,
        float $minIn,
        float $maxIn,
        float $minOut,
        float $maxOut,
    ): float {
        if ($maxIn === $minIn) {
            // Avoid division by zero
            return ($minOut + $maxOut) / 2;
        }

        return ($value - $minIn) * ($maxOut - $minOut)
            / ($maxIn - $minIn) + $minOut;
    }

    /**
     * Returns the lower value for the plot of the measured data.
     *
     * @param array<int, float|int> $data is the measured data.
     *
     * @return int|null the lower limit or null on empty data.
     */
    public static function yLowerLimit(array $data): ?int
    {
        if (0 === count($data)) {
            return null;
        }

        $max = max($data);
        $min = min($data);
        $range = $max - $min;

        return (int)($min - $range * 0.2);
    }

    /**
     * Returns the upper value for the plot of the measured data.
     *
     * @param array<int, float|int> $data is the measured data.
     *
     * @return int|null the upper limit or null on empty data.
     */
    public static function yUpperLimit(array $data): ?int
    {
        if (0 === count($data)) {
            return null;
        }

        $max = max($data);
        $min = min($data);
        $range = $max - $min;

        return (int)($max + $range * 0.2);
    }

    /**
     * Returns the ticks for the y axis of the plot for the measured data.
     *
     * This method returns a reasonable set of values from the range of the
     * measured data.
     * The number of values should be lower but close to 20 and each value is
     * divisible by either 5, 2 or at least 1.
     *
     * @param int $lower the lower limit of the measured data.
     * @param int $upper the upper limit of the measured data.
     *
     * @return array<int> the values that should be displayed on the y axis of
     *                    the plot.
     */
    public static function yTicks(int $lower, int $upper): array
    {
        $baseResolutions = [1, 2, 5];
        $multiplier = 1;
        $range = $upper - $lower;

        while (true) {
            foreach ($baseResolutions as $baseRes) {
                $res = $baseRes * $multiplier;
                if (($range / $res) < self::TICK_LIMIT) {
                    return array_map(
                        fn (float|int $val): int => (int)$val,
                        range(
                            ceil($lower / $res) * $res,
                            floor($upper / $res) * $res,
                            $res,
                        ),
                    );
                }
            }
            $multiplier = $multiplier * 10;
        }
    }
}
