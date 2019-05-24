#!/bin/sh

delimiter="c;.4JOc%Y/tEäBp9@tGo"

dirInfo() {
	DIRECTORY=$1
	echo "Directory info for $DIRECTORY"
	echo "============================="
	curl \
		--insecure \
		--url "imaps://$MAIL_HOST:$MAIL_PORT" \
		--user "$MAIL_USER:$MAIL_PASSWORD" \
		--request "EXAMINE $DIRECTORY"
}

fetchAll() {
	DIRECTORY=$1
	echo "Fetch all for $DIRECTORY"
	echo "============================="
	id=1
	#for mail in $(fetchAllInternal $DIRECTORY | cut -d "$delimiter"); do
	##### TODO HERE I AM (klappt noch nicht.... HATE)
	for mail in $(fetchAllInternal $DIRECTORY | awk -F "$delimiter" '{print $$id}'); do
		echo "-----------------------------"
		echo "Message ${id}"
		echo "-----------------------------"
		echo "$mail"
		id=`expr $id + 1`
	done
}

fetchAllInternal() {
	DIRECTORY=$1
	echo "Fetch all for $DIRECTORY"
	echo "============================="

	# Start with an id of 1, because ids in imap are sequential
	results=""
	id=1
	while true; do
		result=$(curl \
			--insecure \
			--url "imaps://$MAIL_HOST:$MAIL_PORT/$DIRECTORY;UID=$id" \
			--user "$MAIL_USER:$MAIL_PASSWORD") \
			|| break
		echo "$result"
		id=`expr $id + 1`
		if [ -z "$results" ]; then
			results="$results$result"
		else			
			results="$results$result$delimiter"
		fi
	done
	echo "$results"
}

connectDb() {
	return
}

insertDb() {
	MAIL=$1
	echo "Insert mail into database"
	echo "============================="
	echo $MAIL
}

fetchAndInsertInMySQL() {
	DIRECTORY=$1
	echo "Insert all in mysql for $DIRECTORY"
	echo "============================="

	for mail in $(fetchAllInternal $DIRECTORY | cut -d "$delimiter"); do
		insertDb mail
	done
}

#dirInfo "INBOX.Analyze"
fetchAll "INBOX.Analyze"
#