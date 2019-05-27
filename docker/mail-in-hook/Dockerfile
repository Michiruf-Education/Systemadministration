FROM alpine:latest
RUN apk --update-cache --no-cache add php7 php7-mysqli php7-json php7-phar php7-iconv php7-imap php7-mbstring

# Install composer
ADD https://getcomposer.org/installer /composer-setup.php
RUN php /composer-setup.php --install-dir=/usr/local/bin --filename=composer && \
    rm /composer-setup.php

# Add composer imap library
RUN composer require ddeboer/imap

# Add our script
COPY process-mails.php /process-mails
RUN chmod +x /process-mails

# Add cronjob
COPY crontab.txt /crontab.txt
COPY crontab-entry.sh /crontab-entry.sh
RUN chmod +x /crontab-entry.sh && \
    /usr/bin/crontab /crontab.txt
ENTRYPOINT [ "/crontab-entry.sh" ]
