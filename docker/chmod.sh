#!/bin/sh
chmod +x docker-daemon-config.install.sh

chown 2000:2000 /docker/DATA/mail-out/dovecot
chmod 600 /docker/DATA/mail-out/dovecot
# Dont know if +x necessary (more likely not)
chmod +x /docker/DATA/mail-out/dovecot
