FROM alpine:latest
RUN apk update && \
    apk add php7 php7-curl php7-mysqli

COPY process-mails.php /process-mails.sh
COPY crontab.txt /crontab.txt
COPY crontab-entry.sh /crontab-entry.sh
RUN chmod +x /process-mails.sh /crontab-entry.sh
RUN /usr/bin/crontab /crontab.txt
 
ENTRYPOINT [ "/crontab-entry.sh" ]
