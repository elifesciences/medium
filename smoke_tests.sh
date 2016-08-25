#!/usr/bin/env bash
set -ex

[ $(curl --write-out %{http_code} --silent --output /dev/null $(hostname)/medium-articles) == 200 ]
