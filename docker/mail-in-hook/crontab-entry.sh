#!/bin/sh

# Start the cron daemon in foreground for docker
/usr/sbin/crond -f -l 8
