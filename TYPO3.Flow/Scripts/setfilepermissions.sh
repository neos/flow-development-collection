#!/bin/bash

# This is a little convenience script which sets / fixes the permissions of the Data
# and the Web directory. This script will disappear as soon as we have some proper
# installation routine in place.
#
# Make sure to set the webserver group name to the one used by your system.

echo FLOW3 File Permission Script

if [ ! -d "Web" -o ! -d "Packages" -o ! -d "Configuration" ]; then
	echo
	echo Make sure you run this from the FLOW3 root directory!
	echo
	exit 1
fi

if [ "$#" != "3" ]; then
	echo
	echo "Usage: $0 <commandlineuser> <webuser> <webgroup>"
	echo "Run as superuser, if needed (probably)"
	echo
	exit 1
fi

COMMANDLINE_USER="$1"
WEBSERVER_USER="$2"
WEBSERVER_GROUP="$3"

echo
echo "Checking permissions from here upwards ..."
echo " (if a password prompt appears it's from sudo)"

unset PARENT_PATH
PARENT_PATH_PARTS=$(pwd | awk 'BEGIN{FS="/"}{for (i=1; i < NF; i++) print $i}')
for PARENT_PATH_PART in $PARENT_PATH_PARTS ; do
	PARENT_PATH="$PARENT_PATH/$PARENT_PATH_PART"
	sudo -u $WEBSERVER_USER test -x "$PARENT_PATH"
	if [ $? -gt 0 ]; then
		echo "  $PARENT_PATH is NOT searchable (executable) for user $WEBSERVER_USER!"
		echo "  Sorry, you need to fix this yourself, I don't know your preferred permissions ..."
		exit 1
	fi
done

echo
echo "Making sure Data and Web/_Resources exist ..."
mkdir -p Data
mkdir -p Web/_Resources

echo
echo "Setting file permissions, this might take a minute ..."

chown -R $COMMANDLINE_USER:$WEBSERVER_GROUP .
find . -type d -exec chmod 2770 {} \;
find . -type f \! -name commit-msg -exec chmod 660 {} \;

chmod 770 flow3
chmod 770 flow3_dev
chmod 700 $0

chown -R $WEBSERVER_USER:$WEBSERVER_GROUP Web/_Resources
chmod 770 Web/_Resources
