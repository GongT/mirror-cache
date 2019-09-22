#!/bin/bash

set -e

NGINX_CACHE_PATH="/var/cache/nginx/mirror_cache"

function nxcacheof() {
	local x=$(echo -n "$1" | md5sum)
	echo "$NGINX_CACHE_PATH/${x:30:2}/${x:28:2}/${x:0:32}"
}

function each_line() {
	local F="$1"
	local P="$(nxcacheof "$1")"
	echo -ne "purge \e[2m${F}\e[0m:"
	if [[ -e "$P" ]]; then
		if unlink "$P" ; then
			echo -e "\e[38;5;10mOK!\e[0m"
		else
			echo -e "\e[38;5;9m - Failed!\e[0m"
		fi
	else
		echo -e "\e[38;5;14mNot Found\e[0m[$P]"
	fi
}

urldecode(){
  echo -e "$(sed 's/+/ /g;s/%\(..\)/\\x\1/g;')"
}

if [[ $# -gt 0 ]] && [[ "$*" != "-n" ]]; then
	LINES=( "$@" )
else
	LINES=( $( awk '{print $3}' | sort -u ) )
fi

if echo -- "$*" | grep -q -- "-n" ; then
	for LINE in "${LINES[@]}" ; do
		P="$(nxcacheof "$LINE")"
		[[ -e "$P" ]] && echo "$P"
	done
	exit 0
fi


for LINE in "${LINES[@]}" ; do
	each_line ${LINE} # "$(echo $LINE | urldecode)"
done

