#!/bin/sh
# Issue that might got fixed by this file
# @see https://github.com/SteveLTN/https-portal/issues/92

# For dev
#rm /etc/docker/daemon.json

# Link our file in the docker environment
ln -s /docker/docker-daemon-config.json /etc/docker/daemon.json
