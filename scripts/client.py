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

"""Helper script to send data to the WetterObservatorium"""

import argparse
import hashlib
import hmac
import json

from wsgiref.handlers import format_date_time
from time import time

import requests

def send_data(host: str, location: str, payload: dict, secret: str) -> None:
    """Sends signed data to the WetterObservatorium.

    :param str host: the host of the WetterObservatorium with protocoll and port
                     (but no trailing slash)
    :param str location: the location where the data was measured
    :param dict data: the dictionary with the recorded data
    :param str secret: the secret key
    """
    # Always add and sign date header
    date = format_date_time(time())
    headers = {
        'Date': date,
        'Content-Type': 'application/json',
    }
    request = requests.Request(
        'POST',
        f'{host}/api/{location}',
        data=json.dumps(payload),
        headers=headers
    )
    prepped = request.prepare()
    data_to_sign = f'date: {date}\n{prepped.body}'
    signature = hmac.new(
        secret.encode('utf-8'),
        data_to_sign.encode('utf-8'),
        digestmod=hashlib.sha512
    )
    prepped.headers['Authorization'] = 'hmac username="pyclient", ' \
        'algorithm="sha512", ' \
        'headers="date", ' \
        f'signature="{signature.hexdigest()}"'

    with requests.Session() as session:
        response = session.send(prepped)
        print(response.text)

if __name__ == '__main__':
    parser = argparse.ArgumentParser('Send Data to the WetterQbservatorium')
    parser.add_argument(
        'host',
        help='The host of the WetterObservatorium with port and protocol '
        '(no trailing slash at the end)',
        type=str,
    )
    parser.add_argument(
        'location',
        help='The location where the data was measured',
        type=str,
    )
    parser.add_argument(
        'secret',
        help='The secret to sign the data',
        type=str,
    )
    parser.add_argument(
        '--temperature',
        help='The measured temperature converted to natural numbers',
        type=int,
    )
    parser.add_argument(
        '--humidity',
        help='The measured humidity converted to natural numbers',
        type=int,
    )
    args = parser.parse_args()

    data = {}
    if args.temperature is not None:
        data['temperature'] = args.temperature
    if args.humidity is not None:
        data['humidity'] = args.humidity
    if not data:
        raise Exception(
            'No data provided',
        )

    send_data(
        args.host,
        args.location,
        data,
        args.secret
    )
