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

namespace KaLehmann\WetterObservatoriumWeb\tests\Normalizer;

use KaLehmann\WetterObservatoriumWeb\Normalizer\Normalizer;
use KaLehmann\WetterObservatoriumWeb\Normalizer\QuantityNormalizerInterface;
use PHPUnit\Framework\TestCase;

/**
 * Test cases for the Normalizer
 */
class NormalizerTest extends TestCase
{
    /**
     * Check that the normalizer works with a measured value for which a
     * quantity normalizer is registered.
     */
    public function testNormalizeValueWithSupportedQuantity(): void
    {
        $quantity = 'quantity';
        $value = 42.5;
        $quantityNormalizerMock = $this->createMock(QuantityNormalizerInterface::class);
        $quantityNormalizerMock->expects($this->once())
                               ->method('supportsQuantity')
                               ->with($quantity)
                               ->willReturn(true);
        $quantityNormalizerMock->expects($this->once())
                               ->method('normalizeValue')
                               ->with($value)
                               ->willReturn(42);
        $normalizer = new Normalizer();
        $normalizer->setQuantityNormalizers(
            [
                $quantityNormalizerMock,
            ]
        );

        $this->assertEquals(
            42,
            $normalizer->normalizeValue(
                $quantity,
                $value,
            ),
        );
    }

    /**
     * Check that the normalizer returns the measured value as integer if no
     * matching quantity normalizer is registered.
     */
    public function testNormalizeValueWithUnsupportedQuantity(): void
    {
        $quantityNormalizerMock = $this->createMock(QuantityNormalizerInterface::class);
        $quantityNormalizerMock->expects($this->once())
                               ->method('supportsQuantity')
                               ->willReturn(false);
        $normalizer = new Normalizer();
        $normalizer->setQuantityNormalizers(
            [
                $quantityNormalizerMock,
            ]
        );

        $this->assertEquals(
            (int)101.7,
            $normalizer->normalizeValue(
                'temperature',
                101.7,
            ),
        );
    }

    /**
     * Check that denormalizing works with a value for which a quantity
     * normalizer is registered.
     */
    public function testDenormalizeValueWithSupportedQuantity(): void
    {
        $quantity = 'quantity';
        $value = 425;
        $quantityNormalizerMock = $this->createMock(QuantityNormalizerInterface::class);
        $quantityNormalizerMock->expects($this->once())
                               ->method('supportsQuantity')
                               ->with($quantity)
                               ->willReturn(true);
        $quantityNormalizerMock->expects($this->once())
                               ->method('denormalizeValue')
                               ->with($value)
                               ->willReturnCallback(
                                   fn (int $value): float|int => $value / 10,
                               );
        $normalizer = new Normalizer();
        $normalizer->setQuantityNormalizers(
            [
                $quantityNormalizerMock,
            ]
        );

        $this->assertEquals(
            42.5,
            $normalizer->denormalizeValue(
                $quantity,
                $value,
            ),
        );
    }

    /**
     * Check that denormalizing returns the value as it is if no
     * matching quantity normalizer is registered.
     */
    public function testDenormalizeValueWithUnsupportedQuantity(): void
    {
        $quantityNormalizerMock = $this->createMock(QuantityNormalizerInterface::class);
        $quantityNormalizerMock->expects($this->once())
                               ->method('supportsQuantity')
                               ->willReturn(false);
        $normalizer = new Normalizer();
        $normalizer->setQuantityNormalizers(
            [
                $quantityNormalizerMock,
            ]
        );

        $this->assertEquals(
            42,
            $normalizer->denormalizeValue(
                'temperature',
                42,
            ),
        );
    }
}
