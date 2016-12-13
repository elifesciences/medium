#!/usr/bin/env bash
set -ex
composer sync
./bin/update
[ $(curl --write-out %{http_code} --silent --output /dev/null $(hostname)/medium-articles) == 200 ]
