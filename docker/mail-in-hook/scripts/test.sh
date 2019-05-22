#!/usr/bin/expect

# Grab environment variables via expect to handle them like usual
set MAIL_HOST $env(MAIL_HOST)
set MAIL_PORT $env(MAIL_PORT)
set MAIL_USER $env(MAIL_USER)
set MAIL_PASSWORD $env(MAIL_PASSWORD)

spawn openssl s_client -crlf -quiet -connect ${MAIL_HOST}:${MAIL_PORT}
expect "\\\* OK"
send "A login ${MAIL_USER} ${MAIL_PASSWORD}\n"
expect "A OK"
#expect eof
send "A select INBOX\n"
expect "A OK"
#expect eof
send ". fetch 1 rfc822.header\n"



expect eof
