#!/usr/bin/env bash
set -ex

host=$(hostname)
# host="localhost:8088"

successResponse=$(curl --write-out %{http_code} --silent --output /dev/null ${host}/medium-articles)

if [ "$successResponse" == 200 ]
then
    exit 1
else
   curl ${host}/medium-articles
   exit 0
fi
