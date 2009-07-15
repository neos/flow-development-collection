#!/bin/bash

# This is a little convenience script which sets / fixes the permissions of the Data
# and the Web directory. This script will disappear as soon as we have some proper
# installation routine in place.
#
# Make sure to set the webserver group name to the one used by your system.

echo FLOW3 File Permission Script

usage() {
	echo
	echo Usage: $0 \<commandlineuser\> \<webuser\> \<webgroup\>
	echo Run as superuser, if needed
	echo
	exit 1
}

if [ "$#" != "3" ]; then
  usage
fi

echo Setting file permissions, this might take a minute ...

COMMANDLINE_USER="$1"
WEBSERVER_USER="$2"
WEBSERVER_GROUP="$3"

find . -type d -exec chmod 2770 {} \;
find . -type f -exec chmod 660 {} \;

chown -R $COMMANDLINE_USER:$WEBSERVER_GROUP ./*

chmod 770 flow3 
chmod 770 $0

chown -R $WEBSERVER_USER:$WEBSERVER_GROUP Web
chmod 770 Web
chmod 770 Web/index.php
