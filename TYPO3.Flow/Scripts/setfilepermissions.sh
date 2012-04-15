#!/bin/bash

# This is a little convenience script which sets / fixes the permissions of the Data
# and the Web directory. This script will disappear as soon as we have some proper
# installation routine in place.
#
# Make sure to set the webserver group name to the one used by your system.

echo FLOW3 File Permission Script
echo

if [ ! -d "Web" -o ! -d "Packages" -o ! -d "Configuration" ]; then
	echo Make sure you run this from the FLOW3 root directory!
	echo
	exit 1
fi

if [ "$#" != "3" ]; then
	echo "Usage: ./flow3 core:setfilepermissions <commandlineuser> <webuser> <webgroup>"
	echo "Run as super user."
	echo
	exit 1
fi

if [ "$(id -u)" != "0" ]; then
	echo "This command must be called as super user. Use 'sudo' or run as root." 1>&2
	echo
	exit 1
fi

COMMANDLINE_USER="$1"
WEBSERVER_USER="$2"
WEBSERVER_GROUP="$3"

echo "Checking permissions from here upwards."

unset PARENT_PATH
PARENT_PATH_PARTS=$(pwd | awk 'BEGIN{FS="/"}{for (i=1; i < NF; i++) print $i}')
for PARENT_PATH_PART in $PARENT_PATH_PARTS ; do
	PARENT_PATH="$PARENT_PATH/$PARENT_PATH_PART"
	sudo -u $WEBSERVER_USER test -x "$PARENT_PATH"
	if [ $? -gt 0 ]; then
		echo "  $PARENT_PATH seems NOT to be searchable (executable) for user $WEBSERVER_USER!"
		echo "  Sorry, you need to fix this yourself if it's a problem, I don't know your preferred permissions ..."
	fi
done

echo "Making sure Data and Web/_Resources exist."
sudo -u $COMMANDLINE_USER mkdir -p Data
sudo -u $COMMANDLINE_USER mkdir -p Web/_Resources

sudo rm -rf Data/Temporary/*

echo "Setting file permissions, trying to set ACLs via chmod ..." \
	&& sudo chmod +a "$COMMANDLINE_USER allow read,write,append,delete,delete_child,file_inherit,directory_inherit" Configuration Data Packages Web/_Resources >/dev/null 2>&1 \
	&& sudo chmod -R +ai "$COMMANDLINE_USER allow read,write,append,delete,delete_child,file_inherit,directory_inherit" Configuration Data Packages Web/_Resources >/dev/null 2>&1 \
	&& sudo chmod +a "$WEBSERVER_USER allow read,write,append,delete,delete_child,file_inherit,directory_inherit" Configuration Data Packages Web/_Resources >/dev/null 2>&1 \
	&& sudo chmod -R +ai "$WEBSERVER_USER allow read,write,append,delete,delete_child,file_inherit,directory_inherit" Configuration Data Packages Web/_Resources >/dev/null 2>&1
if [ "$?" -eq "0" ]; then echo "Done."; exit 0; fi

echo "Setting file permissions, trying to set ACLs via setfacl ..." \
	&& sudo setfacl -R -m u:$WEBSERVER_USER:rwx -m u:$COMMANDLINE_USER:rwx Configuration Data Packages Web/_Resources >/dev/null 2>&1 \
	&& sudo setfacl -dR -m u:$WEBSERVER_USER:rwx -m u:$COMMANDLINE_USER:rwx Configuration Data Packages Web/_Resources >/dev/null 2>&1
if [ "$?" -eq "0" ]; then echo "Done."; exit 0; fi

echo
echo "Note: Access Control Lists seem not to be supported by your system."
echo
echo "Setting file permissions per file, this might take a while ..."

sudo chown -R $COMMANDLINE_USER:$WEBSERVER_GROUP .
find . -type d -exec sudo chmod 2770 {} \;
find . -type f \! -name commit-msg -exec sudo chmod 660 {} \;

sudo chmod 770 flow3
sudo chmod 700 $0

sudo chown -R $WEBSERVER_USER:$WEBSERVER_GROUP Web/_Resources
sudo chmod 770 Web/_Resources

echo "Done."
