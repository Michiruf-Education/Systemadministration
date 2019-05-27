FROM alpine:latest
RUN apk update && \
	apk add opensmtpd && \
	apk add bash
	
VOLUME /etc/smtpd /var/spool/smtpd

EXPOSE 25 587 993

ENTRYPOINT ["/usr/sbin/smtpd", "-d"]
