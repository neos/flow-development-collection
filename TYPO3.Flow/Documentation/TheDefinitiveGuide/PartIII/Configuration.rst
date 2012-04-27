=============
Configuration
=============

.. sectionauthor:: Robert Lemke <robert@typo3.org>

Configuration is an important aspect of versatile applications. FLOW3 provides you with
configuration mechanisms which have a small footprint and are convenient to use and
powerful at the same time. Hub for all configuration is the configuration manager which
handles alls configuration tasks like reading configuration, configuration cascading, and
(later) also writing configuration.

File Locations
==============

There are several locations where configuration files may be placed. All of them are
scanned by the configuration manager during initialization and cascaded into a single
configuration tree. The following locations exist (listed in the order they are loaded,
i.e. later values override prior ones):

* */Packages/<PackageDirectoryAndName>/Configuration/*
	The *Configuration* directory of each package is scanned first. Only at this stage new
	configuration options should be introduced (by just defining a default value).
* */Configuration/*
	Configuration in the global *Configuration* directory override the default settings
	which were defined in the package's configuration directories.
* */Packages/<PackageDirectoryAndName>/Configuration/<ApplicationContext>/*
* */Configuration/<ApplicationContext>/*
	There may exist a subdirectory for each application context (see FLOW3 Bootstrap
	section). This configuration is only loaded if FLOW3 runs in the respective
	application context.

The configuration manager also considers custom contexts, such as ``Production/Live``.
First, the base configuration is loaded, folled by the context specific configuration
for ``Production`` and ``Production/Live``.

Configuration Files
===================

FLOW3 distinguishes between different types of configuration. The most important type of
configuration are the settings, however other configuration types exist for special
purposes.

The configuration format is YAML and the configuration options of each type are
defined in their own dedicated file:

* *Settings.yaml*
	Contains user-level settings, i.e. configuration options the users or administrators
	are meant to change. Settings are the highest level of system configuration.
* *Routes.yaml*
	Contains routes configuration. This routing information is parsed and used by the MVC
	Web Routing mechanism. Refer to the :ref:`ch-routing` chapter for more information.
* *Objects.yaml*
	Contains object configuration, i.e. options which configure objects and the
	combination of those on a lower level. See the :ref:`ch-object-management` chapter for more
	information.
* *Policy.yaml*
	Contains the configuration of the security policies of the system. See the :ref:`ch-security`
	chapter for details.
* *PackageStates.php*
	Contains a list of packages and their current state, for  example if they are active
	or not. Don't edit this file directly, rather use the *flow3* command line tool do
	activate and deactivate packages.
* *Caches.yaml*
	Contains a list of caches which are registered automatically. Caches defined in this
	configuration file are registered in an early stage of the boot process and profit
	from mechanisms such as automatic flushing by the File Monitor. See the chapter about
	the :ref:`ch-caching` for details.

Defining Configuration
======================

Configuration Format
--------------------

The format of FLOW3's configuration files is YAML. YAML is a well-readable format which is
especially well-suited for defining configuration. The full specification among with many
examples can be found on the `YAML website <http://www.yaml.org/>`_. All important parts of the YAML
specification are supported by the parser used by FLOW3, it might happen though that some
exotic features won't have the desired effect. At best you look at the configuration files
which come with the FLOW3 distribution for getting more examples.

**Example: a package-level Settings.yaml**

.. code-block:: yaml

	#                                                                        #
	# Settings Configuration for the TYPO3.Viewhelpertest Package            #
	#                                                                        #

	TYPO3:
	  Viewhelpertest:
	    includeViewHelpers: [alias, base]

	    xhprof:
	      rootDirectory: '' # path to the XHProf library
	      outputDirectory: %FLOW3_PATH_DATA%Temporary/Viewhelpertest/XHProf/ # output directory

	    profilingTemplatesDirectory: %FLOW3_PATH_DATA%Temporary/Viewhelpertest/Fluidtemplates/


.. warning:: Always use *two spaces* for indentation in YAML files. The parser will not
	accept indentation using tabs.

Constants
---------

Sometimes it is necessary to use values in your configuration files which are defined as
PHP constants. These values can be included by special markers which are replaced by the
actual value during parse time. The format is ``%<CONSTANT_NAME>%`` where
``<CONSTANT_NAME>`` is the name of a PHP constant. Note that the constant name must be all
uppercase.

Some examples:

* ``%FLOW3_PATH_WEB%``
	Will be replaced by the path to the public web directory.
* ``%FLOW3_PATH_DATA%``
	Will be replaced by the path to the */Data/* directory.
* ``%PHP_VERSION%``
	Will be replaced by the current PHP version.

Accessing Settings
==================

In almost all cases, FLOW3 will automatically provide you with the right configuration.

What you usually want to work with are ``settings``, wich are application-specific to
your package. The following example demonstrates how to let FLOW3 inject the settings
of a classes' package and output some option value:

**Example: Settings Injection**

.. code-block:: yaml

	Acme:
	  Demo:
	    administrator:
	      email: 'john@doe.com'
	      name: 'John Doe'

.. code-block:: php

	namespace Acme\Demo;

	class SomeClass {

		/**
		 * @var array
		 */
		protected $settings;

		/**
		 * Inject the settings
		 *
		 * @param array $settings
		 * @return void
		 */
		public function injectSettings(array $settings) {
			$this->settings = $settings;
		}

		/**
		 * Outputs some settings of the "Demo" package.
		 *
		 * @return void
		 */
		public function theMethod() {
			echo ($this->settings['administrator']['name']);
			echo ($this->settings['administrator']['email']);
		}
	}

Working with other configuration
--------------------------------

Although infrequently necessary, it is also possible to retrieve options of the more
special configuration types. The ``ConfigurationManager`` provides a method called
``getConfiguration()`` for this purpose. The result this method returns depends on the
actual configuration type you are requesting.

Bottom line is that you should be highly aware of what you're doing when working with
these special options and that they might change in a later version of FLOW3. Usually
there are much better ways to get the desired information (e.g. ask the Object Manager for
object configuration).

Configuration Cache
===================

Parsing the YAML configuration files takes a bit of time which remarkably slows down the
initialization of FLOW3. That's why all configuration is cached by default when FLOW3 is
running in Production context. Because this cache cannot be cleared automatically it is
important to know that changes to any configuration file won't have any effect until you
manually flush the respective caches.

This feature can be configured through a switch in the *Settings.yaml* file:

.. code-block:: yaml

	TYPO3:
	  FLOW3:
	    configuration:
	      compileConfigurationFiles: TRUE

When enabled, the configuration manager will compile all loaded configuration into a PHP
file which will be loaded in subsequent calls instead of parsing the YAML files again.

.. important::

	Once the configuration is cached changes to the YAML files don't have any effect.
	Therefore in order to switch off the configuration cache again you need to disable the
	feature in the YAML file *and* flush all caches afterwards manually.

In order to flush caches, use the following command:


.. code-block:: bash

	$ ./flow3 flow3:cache:flush
