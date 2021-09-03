#! /usr/bin/env python3

#  Copyright (C) 2021 Karsten Lehmann <mail@kalehmann.de>
#
#  This file is part of WetterObservatoriumWeb.
#
#  WetterObservatoriumWeb is free software: you can redistribute it and/or
#  modify it under the terms of the GNU Affero General Public License as
#  published by the Free Software Foundation, version 3 of the License.
#
#  WetterObservatoriumWeb is distributed in the hope that it will be useful,
#  but WITHOUT ANY WARRANTY; without even the implied warranty of
#  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#  GNU Affero General Public License for more details.
#
#  You should have received a copy of the GNU Affero General Public License
#  along with WetterObservatoriumWeb. If not, see
#  <https://www.gnu.org/licenses/>.

"""Manipulate buffers of the WetterObservatorium in python"""

import struct
from typing import List

def openBuffer(path: str) -> List[List[int]]:
    """Opens a buffer and returns the contents as list.
    The entry at index 0 is the header.
    """
    result = []
    with open(path, "rb") as buffer:
        data = buffer.read()
    result.append(struct.unpack("<LLxx", data[0:10]))
    for ts, val in struct.iter_unpack("<QH", data[10:]):
        result.append([ts, val])

    return result

def saveBuffer(path: str, data: List[List[int]]) -> None:
    """Packs a list as buffer."""
    with open(path, "wb") as buffer:
        buffer.write(
            struct.pack("<LLxx", *data[0])
        )
        for entry in data[1:]:
            buffer.write(
                struct.pack("<QH", *entry)
            )
