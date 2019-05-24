FROM alpine:latest
RUN apk update && \
    apk add openssl && \
    apk add expect && \
    apk add mysql-client && \
    apk add bash


# CURL auch eine Option!!
# https://busylog.net/telnet-imap-commands-note/
ADD https://raw.githubusercontent.com/SixArm/urlencode.sh/master/urlencode.sh /urlencode.sh
RUN apk add curl && \
    chmod +x /urlencode.sh \
