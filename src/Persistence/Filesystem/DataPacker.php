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
 * Helper class for the `pack` and `unpack` methods.
 *
 * This class only supports formats with a known length on all platforms.
 */
class DataPacker
{
    /**
     * Signed charater type (1 byte).
     */
    public const SIGNED_CHAR = 'c';

    /**
     * Unsigned character type (1 byte).
     */
    public const UNSIGNED_CHAR = 'C';

    /**
     * Signed short type (2 byte, machine byte order)
     */
    public const SIGNED_SHORT = 's';

    /**
     * Unsigned short type (16 bit, machine byte order)
     */
    public const UNSIGNED_SHORT = 'S';

    /**
     * Unsigned short type (16 bit, big endian)
     */
    public const UNSIGNED_SHORT_BE = 'n';

    /**
     * Unsigned short type (16 bit, little endian)
     */
    public const UNSIGNED_SHORT_LE = 'v';

    /**
     * Signed long type (32 bit, machine byte order)
     */
    public const SIGNED_LONG = 'l';

    /**
     * Unsigned long type (32 bit, machine byte order)
     */
    public const UNSIGNED_LONG = 'L';

    /**
     * Unsigned long type (32 bit, big endian)
     */
    public const UNSIGNED_LONG_BE = 'N';

    /**
     * Unsigned long type (32 bit, little endian)
     */
    public const UNSIGNED_LONG_LE = 'V';

    /**
     * Singned long long type (64 bit, machine byte order)
     */
    public const SIGNED_LONG_LONG = 'q';

    /**
     * Unsigned long long type (64 bit, machine byte order)
     */
    public const UNSIGNED_LONG_LONG = 'Q';

    /**
     * Unsigned long long type (64 bit, big endian)
     */
    public const UNSIGNED_LONG_LONG_BE = 'J';

    /**
     * Unsigned long long type (64 bit, little endian)
     */
    public const UNSIGNED_LONG_LONG_LE = 'P';

    /**
     * NULL byte.
     */
    public const NULL_BYTE = 'x';

    /**
     * Packed sizes of the formats.
     */
    public const FORMAT_SIZES = [
        self::SIGNED_CHAR => 1,
        self::UNSIGNED_CHAR => 1,
        self::SIGNED_SHORT => 2,
        self::UNSIGNED_SHORT => 2,
        self::UNSIGNED_SHORT_BE => 2,
        self::UNSIGNED_SHORT_LE => 2,
        self::SIGNED_LONG => 4,
        self::UNSIGNED_LONG => 4,
        self::UNSIGNED_LONG_BE => 4,
        self::UNSIGNED_LONG_LE => 4,
        self::SIGNED_LONG_LONG => 8,
        self::UNSIGNED_LONG_LONG => 8,
        self::UNSIGNED_LONG_LONG_BE => 8,
        self::UNSIGNED_LONG_LONG_LE => 8,
        self::NULL_BYTE => 1,
    ];

    /**
     * Check that a format string contains only formats with a known size on all
     * platforms.
     *
     * @return bool The method returns `true` on success.
     * @throws InvalidPackFormatException for unknown format codes.
     */
    public static function checkFormat(string $format): bool
    {
        $formatCodes = str_split($format, 1);
        $invalidCodes = array_filter(
            $formatCodes,
            fn (string $code) => false === array_key_exists(
                $code,
                self::FORMAT_SIZES,
            ),
        );

        if (0 !== count($invalidCodes)) {
            throw new InvalidPackFormatException(...$invalidCodes);
        }

        return true;
    }

    /**
     * Get the packed size for a format code.
     *
     * @return int<0, max> the packed size in bytes.
     * @throws InvalidPackFormatException for unknown format codes.
     */
    public static function getElementSize(string $format): int
    {
        self::checkFormat($format);
        $formatCodes = str_split($format, 1);

        return array_sum(
            array_map(
                fn(string $code): int => self::FORMAT_SIZES[$code],
                $formatCodes,
            ),
        );
    }

    /**
     * Returns the number of elements described by a format code.
     *
     * @return int the number of elements described by the format code.
     * @throws InvalidPackFormatException for unknown format codes.
     */
    public static function getFormatElementCount(string $format): int
    {
        self::checkFormat($format);

        $count = 0;
        $formatCodes = str_split($format, 1);
        foreach ($formatCodes as $char) {
            if ($char === self::NULL_BYTE) {
                continue;
            }
            $count += 1;
        }

        return $count;
    }

    /**
     * Slightly modified version of the built in function `unpack`.
     *
     * Same as `unpack` except that it deos not need named elements and returns
     * a zero indexed array with all unpacked elemets.
     *
     * @return array<int, int>
     */
    public static function unpack(string $format, string $packedData): array
    {
        self::checkFormat($format);

        $namedFormat = '';
        $elementIndex = 0;
        for ($i = 0; $i < strlen($format); $i++) {
            if ($format[$i] === 'x') {
                $namedFormat .= 'x_/';

                continue;
            }
            $namedFormat .= $format[$i] . '_' . $elementIndex . '/';
            $elementIndex += 1;
        }

        $unpackedData = unpack($namedFormat, $packedData);
        if (false === $unpackedData) {
            throw new IOException(
                'Could not unpack "' . bin2hex($packedData) .
                '" with format "' . $format . '"',
            );
        }

        return array_values(
            array_filter(
                $unpackedData,
                fn ($value, string $key) => $key[0] !== 'd',
                ARRAY_FILTER_USE_BOTH,
            )
        );
    }
}
