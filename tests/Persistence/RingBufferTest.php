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

namespace KaLehmann\WetterObservatoriumWebP\Tests\Persistence;

use KaLehmann\WetterObservatoriumWeb\Persistence\InvalidPackFormatException;
use KaLehmann\WetterObservatoriumWeb\Persistence\IOException;
use KaLehmann\WetterObservatoriumWeb\Persistence\RingBuffer;
use \RunTimeException;
use PHPUnit\Framework\TestCase;

/**
 * Test cases for the RingBuffer.
 */
class RingBufferTest extends TestCase
{
    /**
     * Check that an entry with two elements cannot be added to a ring
     * buffer with one element per entry.
     */
    public function testAddEntryWithInvalidEntry(): void
    {
        $ringBuffer = RingBuffer::createNew(4, 'c');

        $this->expectException(IOException::class);
        $ringBuffer->addEntry([1, 2]);
    }

    /**
     * Check that a valid entry can be added to the ring buffer.
     */
    public function testAddEntryWithValidEntry(): void
    {
        $ringBuffer = RingBuffer::createNew(2, 'l');
        $ringBuffer->addEntry([31]);
        $ringBuffer->addEntry([41]);

        $this->assertEquals(
            [[31], [41]],
            iterator_to_array($ringBuffer),
        );
    }

    /**
     * Check that more entries than the size of the ring buffer can be added.
     */
    public function testAddEntryWithOverflow(): void
    {
        $ringBuffer = RingBuffer::createNew(2, 'llll');
        $ringBuffer->addEntry([1, 2, 3, 4]);
        $ringBuffer->addEntry([5, 6, 7, 8]);
        $ringBuffer->addEntry([9, 10, 11, 12]);

        $this->assertEquals(
            [[5, 6, 7, 8], [9, 10, 11, 12]],
            iterator_to_array($ringBuffer),
        );
    }

    /**
     * Check, that the number of elements in the ring buffer can be determined
     * with `count`.
     */
    public function testCount(): void
    {
        $ringBuffer = RingBuffer::createNew(20, 'N');
        $this->assertEquals(
            20,
            count($ringBuffer),
        );
    }

    /**
     * Check that a new ring buffer can be created.
     */
    public function testCreateNew(): void
    {
        $ringBuffer = RingBuffer::createNew(6, 'qq');
        $this->assertEquals(6, count($ringBuffer));
    }

    /**
     * Check that creating a ring buffer with a invalid format throws an
     * exception.
     */
    public function testCreateNewWithInvalidFormat(): void
    {
        $this->expectException(InvalidPackFormatException::class);
        RingBuffer::createNew(10, 'invalid');
    }

    /**
     * Check that creating a ring buffer with an invalid format throws an
     * exception.
     */
    public function testCreateNewWithFormatWithoutElements(): void
    {
        $this->expectException(IOException::class);
        $this->expectExceptionMessage(
            'The format does not contain a single element',
        );
        RingBuffer::createNew(40, 'xx');
    }

    /**
     * Check that creating a ring buffer with less than one element throws
     * an exception.
     */
    public function testCreateNewWithInvalidNumberOfElements(): void
    {
        $this->expectException(IOException::class);
        $this->expectExceptionMessage('The number of elements is too small');
        RingBuffer::createNew(0, 'qcc');
    }

    /**
     * Check that the number of elements per entry can be obtained from the
     * buffer.
     */
    public function testElementsPerEntry(): void
    {
        $buffer1 = RingBuffer::createNew(10, 'qxxqxx');
        $buffer2 = RingBuffer::createNew(20, 'ccc');

        $this->assertEquals(2, $buffer1->elementsPerEntry());
        $this->assertEquals(3, $buffer2->elementsPerEntry());
    }

    /**
     * Check that the ring buffer can be iterated.
     */
    public function testIteration(): void
    {
        $ringBuffer = RingBuffer::createNew(4, 'q');
        $this->assertEquals(
            [[0], [0], [0], [0]],
            iterator_to_array($ringBuffer),
        );

        $ringBuffer->addEntry([1]);
        $ringBuffer->addEntry([2]);
        $ringBuffer->addEntry([3]);
        $ringBuffer->addEntry([4]);
        $this->assertEquals(
            [[1], [2], [3], [4]],
            iterator_to_array($ringBuffer),
        );
    }

    /**
     * Check that the youngest entry from a ring buffer can be obtained.
     */
    public function testLastEntry(): void
    {
        $ringBuffer = RingBuffer::createNew(2, 'Pv');
        $ringBuffer->addEntry([101, 1]);
        $this->assertEquals([101, 1], $ringBuffer->lastEntry());
        $ringBuffer->addEntry([102, 2]);
        $this->assertEquals([102, 2], $ringBuffer->lastEntry());
        $ringBuffer->addEntry([103, 3]);
        $this->assertEquals([103, 3], $ringBuffer->lastEntry());
    }

    /**
     * Check that an exclusive operation on the ring buffer does not shred the
     * buffer if an exception occurs.
     */
    public function testOperateExclusiveDoesNotShredTheBufferOnException(): void
    {
        $tempFile = tempnam(
            sys_get_temp_dir(),
            'testOperateExclusive',
        );
        if (false === $tempFile) {
            throw new RunTimeException(
                'Could not get a temporary file name for a test',
            );
        }

        $this->expectException(RunTimeException::class);
        $this->expectExceptionMessage('lulz');
        try {
            $ringBuffer = RingBuffer::createNew(4, 'qq');
            file_put_contents($tempFile, (string)$ringBuffer);

            RingBuffer::operateExclusive(
                $tempFile,
                'qq',
                function (RingBuffer $ringBuffer): void {
                    $ringBuffer->addEntry([1, 2]);
                    $ringBuffer->addEntry([3, 4]);
                    $ringBuffer->addEntry([5, 6]);
                    $ringBuffer->addEntry([7, 8]);

                    $this->assertEquals(
                        [[1, 2], [3, 4], [5, 6], [7, 8]],
                        iterator_to_array($ringBuffer),
                    );
                },
            );

            RingBuffer::operateExclusive(
                $tempFile,
                'qq',
                function (RingBuffer $ringBuffer): void {
                    $ringBuffer->addEntry([9, 10]);

                    throw new RunTimeException('lulz');
                },
            );
        } catch (RunTimeException $e) {
            $this->assertEquals(
                [[1, 2], [3, 4], [5, 6], [7, 8]],
                iterator_to_array(
                    RingBuffer::fromFile($tempFile, 'qq'),
                ),
            );

            throw $e;
        } finally {
            unlink($tempFile);
        }
    }

    /**
     * Check that an exclusive operation on the ring buffer is possible.
     */
    public function testOperateExclusive(): void
    {
        $tempFile = tempnam(
            sys_get_temp_dir(),
            'testOperateExclusive',
        );
        if (false === $tempFile) {
            throw new RunTimeException(
                'Could not get a temporary file name for a test',
            );
        }

        try {
            $ringBuffer = RingBuffer::createNew(4, 'qq');
            file_put_contents($tempFile, (string)$ringBuffer);

            RingBuffer::operateExclusive(
                $tempFile,
                'qq',
                function (RingBuffer $ringBuffer): void {
                    $ringBuffer->addEntry([1, 2]);
                    $ringBuffer->addEntry([3, 4]);
                    $ringBuffer->addEntry([5, 6]);
                    $ringBuffer->addEntry([7, 8]);

                    $this->assertEquals(
                        [[1, 2], [3, 4], [5, 6], [7, 8]],
                        iterator_to_array($ringBuffer),
                    );
                },
            );

            RingBuffer::operateExclusive(
                $tempFile,
                'qq',
                function (RingBuffer $ringBuffer): void {
                    $ringBuffer->addEntry([9, 10]);

                    $this->assertEquals(
                        [[3, 4], [5, 6], [7, 8], [9, 10]],
                        iterator_to_array($ringBuffer),
                    );
                },
            );
        } finally {
            unlink($tempFile);
        }
    }

    /**
     * Check that a ring buffer can be converted to a string and then be read
     * again.
     */
    public function testToString(): void
    {
        $ringBuffer = RingBuffer::createNew(4, 'lcxxx');
        $ringBuffer->addEntry([1, 11]);
        $ringBuffer->addEntry([2, 22]);
        $ringBuffer->addEntry([3, 33]);
        $ringBuffer->addEntry([4, 44]);

        $data = (string)$ringBuffer;
        $ringBuffer2 = new RingBuffer($data, 'lcxxx');
        $this->assertEquals(
            [[1, 11], [2, 22], [3, 33], [4, 44]],
            iterator_to_array($ringBuffer2),
        );
    }
}
