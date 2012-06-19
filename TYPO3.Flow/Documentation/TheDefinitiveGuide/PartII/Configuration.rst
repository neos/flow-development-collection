=============
Configuration
=============

.. sectionauthor:: Robert Lemke <robert@typo3.org>

Contexts
========

Once you start developing an application you'll want to launch it in different
contexts: in a production context the configuration must be optimized for speed
and security while in a development context debugging capabilities and
convenience are more important. FLOW3 supports the notion of contexts which
allow for bundling configuration for different purposes. Each FLOW3 request
acts in exactly one context. However, it is possible to use the same
installation on the same server in distinct contexts by accessing it through a
different host name, port or passing special arguments.

.. sidebar:: **Why do I want contexts?**

	Imagine your application is running on a live server and your customer
	reports a bug. No matter how hard you try, you can't reproduce the issue on
	your local development server. Now contexts allow you to enter the live
	application on the production server in a development context without
	anyone noticing – both contexts run in parallel. This effectively allows
	you to debug an application in its realistic environment (although you
	still should do the actual development on a dedicated machine ...).

	An additional use for context is the simplified staging of your application.
	You'll want almost the same configuration on your production and your
	development server - but not exactly the same. The live environment will
	surely access a different database or might require other authentication
	methods. What you do in this case is sharing most of the configuration and
	define the difference in dedicated contexts.

FLOW3 provides configuration for the Production and Development context.
In the standard distribution a reasonable configuration is defined for
each context:

*	In the **Production context** all caches are enabled, logging is reduced to
	a minimum and only generic, friendly error messages are displayed to the
	user (more detailed descriptions end up in the log).

*	In **Development context** caches are active but a smart monitoring service
	flushes caches automatically if PHP code or configuration has been altered.
	Error messages and exceptions are displayed verbosely and additional aids
	are given for effective development.

.. tip::
	If FLOW3 throws some strange errors at you after you made code changes,
	make sure to either manually flush the cache or run the application in
	``Development`` context - because caches are not flushed automatically
	in ``Production`` context.

The configuration for each context is located in directories of the same name:

**Context Configurations**

============================	==================================================
Directory						Description
============================	==================================================
*Configuration/*				Global configuration, for all contexts
*Configuration/Development/*	Configuration for the ``Development`` context
*Configuration/Production/*		Configuration for the ``Production`` context
============================	==================================================

Configuring FLOW3
=================

One thing you certainly need to adjust is the database configuration. Aside from that
FLOW3 should work fine with the default configuration delivered with the distribution.
However, there are many switches you can adjust: specify another location for logging,
select a faster cache backend and much more.

The easiest way to find out which options are available is taking a look at the default
configuration of the FLOW3 package and other packages. The respective files are located in
``Packages/Framework/<packageKey>/Configuration/``. Don't modify these files directly but
rather copy the setting you'd like to change and insert it into a file within the global
or context configuration directories.

FLOW3 uses the YAML format [#]_ for its configuration files. If you never edited
a YAML file, there are two things you should know at least:

* Indentation has a meaning: by different levels of indentation, a structure is
  defined.
* Spaces, not tabs: you must indent with exactly 2 spaces per level, don't use tabs.

More detailed information about FLOW3's configuration management can be found
in the `Reference Manual <http://flow3.typo3.org/documentation/>`_.

.. note::
	If you're running FLOW3 on a Windows machine, you do have to make some
	adjustments to the standard configuration because it will cause problems
	with long paths and file names. By default FLOW3 caches files within the
	``Data/Temporary/<Context>/Caches/`` directory
	whose absolute path can eventually become too long for Windows.

	To avoid errors you should change the cache configuration so it points to a
	location with a very short absolute file path, for example ``C:\\tmp\\``.
	Do that by adding the following to the file ``Configuration/Settings.yaml``:

	*Configuration/Settings.yaml*:

	.. code-block:: yaml

		utility:
		  environment:
		    temporaryDirectoryBase: 'C\\:tmp\\'

.. important::
	Parsing the YAML configuration files takes a bit of time which remarkably
	slows down the initialization of FLOW3. That's why all configuration is
	cached by default when FLOW3 is running in Production context. Because this
	cache cannot be cleared automatically it is important to know that changes
	to any configuration file won't have any effect until you manually flush
	the respective caches.

	To avoid any hassle we recommend that you stay in Development context
	throughout this tutorial.


Database Setup
--------------

Before you can store anything, you need to set up a database and tell FLOW3 how
to access it. The credentials and driver options need to be specified in the global
FLOW3 settings.

.. tip::
	You should make it a habit to specify database settings in context-specific
	configuration files. This makes sure your functional tests will never accidentally
	truncate your production database. The same line of thought makes sense for other
	options as well, e.g. mail server settings.

After you have created an empty database and set up a user with sufficient access
rights, copy the file ``Configuration/Development/Settings.yaml.example`` to
``Configuration/Development/Settings.yaml``. Open and adjust the file to your needs -
for a common MySQL setup, it would look similar to this:

*Configuration/Development/Settings.yaml*:

.. code-block:: yaml

	TYPO3:
	  FLOW3:
	    persistence:
	     backendOptions:
	      dbname: 'gettingstarted'
	      user: 'myuser'
	      password: 'mypassword'

For global settings and Production context, the relevant files would be directly
in ``Configuration`` respectively ``Configuration/Production```.`

If you configured everything correctly, the following command will create the initial
table structure needed by FLOW3:

.. code-block:: none

	$ ./flow3 doctrine:migrate
	Migrating up to 2011xxxxxxxxxx from 0

	++ migrating 20110613223837
		-> CREATE TABLE flow3_resource_resourcepointer (hash VARCHAR(255) NOT NULL, PRIMARY
		-> CREATE TABLE flow3_resource_resource (flow3_persistence_identifier VARCHAR(40)

	...

	  ------------------------

	++ finished in 4.97
	++ 5 migrations executed
	++ 28 sql queries

-----

.. [#] **YAML Ain't Markup Language** http://yaml.org