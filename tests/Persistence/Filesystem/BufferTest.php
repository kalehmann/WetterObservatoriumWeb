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

use KaLehmann\WetterObservatoriumWeb\Persistence\Filesystem\InvalidPackFormatException;
use KaLehmann\WetterObservatoriumWeb\Persistence\Filesystem\IOException;
use KaLehmann\WetterObservatoriumWeb\Persistence\Filesystem\Buffer;
use PHPUnit\Framework\TestCase;
use \RunTimeException;

/**
 * Test cases for the Buffer.
 */
class BufferTest extends TestCase
{
    /**
     * Check that an entry with two elements cannot be added to a buffer with
     * one element per entry.
     */
    public function testAddEntryWithInvalidEntry(): void
    {
        $buffer = Buffer::createNew('c');

        $this->expectException(IOException::class);
        $buffer->addEntry([1, 2]);
    }

    /**
     * Check that a valid entry can be added to the buffer.
     */
    public function testAddEntryWithValidEntry(): void
    {
        $buffer = Buffer::createNew('l');
        $buffer->addEntry([31]);
        $buffer->addEntry([41]);

        $this->assertEquals(
            [[31], [41]],
            iterator_to_array($buffer),
        );
    }

    /**
     * Check, that the number of elements in the buffer can be determined
     * with `count`.
     */
    public function testCount(): void
    {
        $buffer = Buffer::createNew('N');
        for ($i = 0; $i < 20; $i++) {
            $buffer->addEntry([$i]);
        }
        $this->assertEquals(
            20,
            count($buffer),
        );
    }

    /**
     * Check that a new buffer can be created.
     */
    public function testCreateNew(): void
    {
        $buffer = Buffer::createNew('qq');
        $this->assertEquals(0, count($buffer));
    }

    /**
     * Check that creating a buffer with a invalid format throws an exception.
     */
    public function testCreateNewWithInvalidFormat(): void
    {
        $this->expectException(InvalidPackFormatException::class);
        Buffer::createNew('invalid');
    }

    /**
     * Check that creating a buffer with an invalid format throws an exception.
     */
    public function testCreateNewWithFormatWithoutElements(): void
    {
        $this->expectException(IOException::class);
        $this->expectExceptionMessage(
            'The format does not contain a single element',
        );
        Buffer::createNew('xx');
    }

    /**
     * Check that the number of elements per entry can be obtained from the
     * buffer.
     */
    public function testElementsPerEntry(): void
    {
        $buffer1 = Buffer::createNew('qxxqxx');
        $buffer2 = Buffer::createNew('ccc');

        $this->assertEquals(2, $buffer1->elementsPerEntry());
        $this->assertEquals(3, $buffer2->elementsPerEntry());
    }

    /**
     * Check that a buffer can be loaded from a file.
     */
    public function testFromFile(): void
    {
        $tempFile = tempnam(
            sys_get_temp_dir(),
            'testFromFile',
        );
        try {
            $buffer = Buffer::createNew('q');
            $buffer->addEntry([1]);
            $buffer->addEntry([2]);
            $buffer->addEntry([3]);
            $buffer->addEntry([4]);
            file_put_contents($tempFile, (string)$buffer);

            $buffer = Buffer::fromFile($tempFile, 'q');
            $this->assertEquals(
                4,
                count($buffer),
            );
            $this->assertEquals(
                [[1], [2], [3], [4]],
                iterator_to_array($buffer),
            );
        } finally {
            unlink($tempFile);
        }
    }

    /**
     * Check that the buffer can be iterated.
     */
    public function testIteration(): void
    {
        $buffer = Buffer::createNew('q');
        $buffer->addEntry([1]);
        $buffer->addEntry([2]);
        $buffer->addEntry([3]);
        $buffer->addEntry([4]);
        $this->assertEquals(
            [[1], [2], [3], [4]],
            iterator_to_array($buffer),
        );
    }

    /**
     * Check that an exclusive operation on the buffer is possible.
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
            $buffer = Buffer::createNew('qq');
            file_put_contents($tempFile, (string)$buffer);

            Buffer::operateExclusive(
                $tempFile,
                'qq',
                function (Buffer $buffer): void {
                    $buffer->addEntry([1, 2]);
                    $buffer->addEntry([3, 4]);
                    $buffer->addEntry([5, 6]);
                    $buffer->addEntry([7, 8]);

                    $this->assertEquals(
                        [[1, 2], [3, 4], [5, 6], [7, 8]],
                        iterator_to_array($buffer),
                    );
                },
            );

            Buffer::operateExclusive(
                $tempFile,
                'qq',
                function (Buffer $buffer): void {
                    $buffer->addEntry([9, 10]);

                    $this->assertEquals(
                        [[1, 2], [3, 4], [5, 6], [7, 8], [9, 10]],
                        iterator_to_array($buffer),
                    );
                },
            );
        } finally {
            unlink($tempFile);
        }
    }

    /**
     * Check that a buffer can be converted to a string and then be read again.
     */
    public function testToString(): void
    {
        $buffer = Buffer::createNew('lcxxx');
        $buffer->addEntry([1, 11]);
        $buffer->addEntry([2, 22]);
        $buffer->addEntry([3, 33]);
        $buffer->addEntry([4, 44]);

        $data = (string)$buffer;
        $buffer2 = new Buffer($data, 'lcxxx');
        $this->assertEquals(
            [[1, 11], [2, 22], [3, 33], [4, 44]],
            iterator_to_array($buffer2),
        );
    }
}
