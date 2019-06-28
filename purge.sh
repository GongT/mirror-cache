#!/bin/bash

function each_line() {
	L="${1: :-1}"
	OIFS=$IFS
	IFS=:
	set -- $L
	ARG_TYPE=$1
	ARG_PATH=$2
	shift
	shift
	ARG_QS="$*"
	IFS=$OIFS
	URL="/$ARG_TYPE$ARG_PATH?$ARG_QS"
	echo -ne "purge: $URL \e[38;5;14m\n\t"
	curl --silent -X PURGE -H 'Cookie: purge_cache=yes' -H 'Host: mirror.service.gongt.me' "127.0.0.1:59080$URL" | grep -oE '<title>.+</title>'
	echo -e "\e[0m"
}
export -f each_line

awk '{print $2}' | xargs -n1 -IF bash -c "each_line 'F'"
