#!/usr/bin/env bash
. /opt/smoke.sh/smoke.sh

smoke_url_ok $(hostname)/ping
smoke_url_ok $(hostname)/medium-articles

smoke_report
