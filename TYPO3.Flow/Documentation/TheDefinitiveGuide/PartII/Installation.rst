============
Installation
============

.. sectionauthor:: Robert Lemke <robert@typo3.org>

FLOW3 Download
==============

The most recent FLOW3 release can be obtained from http://flow3.typo3.org/download as a
.tgz, .zip or .bz2 archive. Once you downloaded the distribution just unpack the archive
in a directory of your choice, e.g. like this for the gzipped tar archive:

.. code-block:: none

	mkdir -p /var/apache2/htdocs/tutorial
	tar xfz FLOW3-1.0.0-beta1.tgz /var/apache2/htdocs/tutorial/

On Windows you create a directory (e.g. *c:\\xampp\\htdocs\\tutorial*), move
the .zip file into the new directory and unzip it with the Windows Explorer.

The FLOW3 distributions can also be cloned from our Git repository. The
following Unix command would download the FLOW3 distribution:

.. code-block:: none

	git clone --recursive git://git.typo3.org/FLOW3/Distributions/Base.git \
	/var/apache2/htdocs/tutorial/

.. note::
	Throughout this tutorial we assume that you installed the FLOW3 distribution in
	*/var/apache2/htdocs/tutorial* and that */var/apache2/htdocs* is the document root
	of your web server. On a Windows machine you might use *c:\\xampp\\htdocs* instead.

Directory Structure
===================

Let's take a look at the directory structure of a FLOW3 application:

======================	===================================================================================
Directory				Description
======================	===================================================================================
Configuration/			Application specific configuration, grouped by contexts
Data/					Persistent and temporary data, including caches, logs, resources and the database
Packages/				Contains sub directories which in turn contain package directories
Packages/Framework/		Packages which are part of the official FLOW3 distribution
Packages/Application/	Application specific packages
Web/					Public web root
======================	===================================================================================

A FLOW3 application usually consists of the above directories. As you see, most
of them contain data which is specific to your application, therefore upgrading
the FLOW3 distribution is a matter of replacing *Packages/Framework/* by
a new release.

FLOW3 is a package based system which means that all code, documentation and
other resources are bundled in packages. Each package has its own directory
with a defined sub structure. Your own PHP code and resources will usually end
up in a package residing below *Packages/Application/*. You're free to create
additional directories or symbolic links in *Packages/*, a common one would
be called *Shared/* which points to packages shared by multiple applications.

.. tip::
	On Unix-like machines it is a good idea to use symbolic links
	pointing to your own packages which are used in multiple projects. With
	this strategy you assure that only one master copy of the package exists
	and avoid the hassle of diverging copies which contain a few changes here
	and some fixes there.

File Permissions
================

Most of the directories and files must be readable and writable for the user
you're running FLOW3 with. This user will usually be the same one running your
web server (``httpd``, ``www`` or ``_www`` on most Unix based systems). However it
can and usually will happen that FLOW3 is launched from the command line by a
different user. Therefore it is important that both, the web server user and
the command line user are members of a common group and the file permissions
are set accordingly.

We recommend setting ownership of directories and files to the web server's
group. All users who also need to launch FLOW3 must also be added this group.
But don't worry, this is simply done by changing to the FLOW3 base directory
and calling the following command (this command must be called as super user):

.. code-block:: none

	sudo ./flow3 core:setfilepermissions john www-data www-data

.. note::

	Setting file permissions is not necessary and not possible on Windows machines.
	For Apache to be able to create symlinks, it needs to be started with Administrator
	privileges, though.

Now that the file permissions are set, all users who plan using FLOW3 from the
command line need to join the web server's group. On a Linux machine this can
be done by typing:

.. code-block:: none

	sudo usermod -a -G _www john

On a Mac you can add a user to the web group with the following command:

.. code-block:: none

	sudo dscl . -append /Groups/_www GroupMembership johndoe

You will have to exit your shell / terminal window and open it again for the
new group membership to take effect.

.. note::
	In this example the web user was ``_www`` and the web group
	is called ``_www`` as well (that's the case on a Mac using
	`MacPorts <http://www.macports.org/>`_ ). On your system the user or group
	might be ``www-data``, ``httpd`` or the like - make sure to find out and
	specify the correct user and group for your environment.

Web Server Configuration
========================

As you have seen previously, FLOW3 uses a directory called *Web* as the public
web root. We highly recommend that you create a virtual host which points to
this directory and thereby assure that all other directories are not accessible
from the web. For testing purposes on your local machine it is okay (but not
very convenient) to do without a virtual host, but don't try that on a public
server!

Setting Up a Virtual Host
-------------------------

Assuming that you chose Apache 2 as your web server, simply create a new virtual
host by adding the following directions to your Apache configuration
(``conf/extra/httpd-vhosts.conf`` on many systems; make sure it is actually
loaded with ``Include`` in ``httpd.conf``):

*httpd.conf*:

.. code-block:: none

	<VirtualHost *:80>
		DocumentRoot /var/apache2/htdocs/tutorial/Web/
		ServerName tutorial.local
	</VirtualHost>

This virtual host will later be accessible via the URL http://tutorial.local.

Because FLOW3 provides an ``.htaccess`` file with ``mod_rewrite`` rules in it,
you need to make sure that the directory grants the neccessary rights:

*httpd.conf*

.. code-block:: none

	<Directory /var/apache2/htdocs/tutorial/>
		AllowOverride FileInfo
	</Directory>

The way FLOW3 addresses resources on the web makes it incompatible with the ``MultiViews``
feature of Apache. This needs to be turned off, the default ``.htaccess`` file distributed
with FLOW3 contains this code already

.. code-block:: none

	<IfModule mod_negotiation.c>

		# prevents Apache's automatic file negotiation, it breaks resource URLs
		Options -MultiViews

	</IfModule>

Configure a Context
-------------------

As you'll learn soon, FLOW3 can be launched in different **contexts**, the most
popular being ``Production``, ``Development`` and ``Testing``. Although there
are various ways to choose the current context, the most convenient is to setup
a dedicated virtual host defining an environment variable. Just add the
following virtual host to your Apache configuration:

*httpd.conf*:

.. code-block:: none

	<VirtualHost *:80>
		DocumentRoot /var/apache2/htdocs/tutorial/Web/
		ServerName dev.tutorial.local
		SetEnv FLOW3_CONTEXT Development
	</VirtualHost>

You'll be able to access the same application running in ``Development``
context by accessing the URL http://dev.tutorial.local. What's left is telling
your operating system that the invented domain names can be found on your local
machine. Add the following line to your */etc/hosts* file
(*C:\windows\system32\drivers\etc\hosts* on Windows):

*hosts*:

.. code-block:: none

	127.0.0.1 tutorial.local dev.tutorial.local

.. tip::
	If you decided to skip setting up virtual hosts earlier on, you should
	enable the ``Development`` context by editing the ``.htaccess`` file in the
	``Web`` directory and remove the comment sign in front of the ``SetEnv``
	line:

.. code-block:: none

	# You can specify a default context by activating this option:
	SetEnv FLOW3_CONTEXT Development

Welcome to FLOW3
----------------

Restart Apache and test your new configuration by accessing
http://dev.tutorial.local in a web browser. You should be greeted by FLOW3's
welcome screen:

.. image:: /Images/GettingStarted/Welcome.png

