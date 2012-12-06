==================
Package Management
==================

.. sectionauthor:: Robert Lemke <robert@typo3.org>


TYPO3 Flow is a package-based system. In fact, TYPO3 Flow itself is just a package as well - but
obviously an important one. Packages act as a container for different matters: Most of
them contain PHP code which adds certain functionality, others only contain documentation
and yet other packages consist of templates, images or other resources.

Package Locations
=================

Framework and Application Packages
----------------------------------

TYPO3 Flow packages are located in a sub folder of the *Packages/* directory. A typical
application (such as TYPO3 Neos for example) will use the core packages which are bundled with
TYPO3 Flow and use additional packages which are specific to the application. The framework
packages are kept in a directory called *Framework* while the application specific
packages reside in the *Application* directory. This leads to the following
folder structure:

Configuration/
  The global configuration folder

Data/
  The various data folders, temporary as well as persistent

Packages/
  Framework/
    The Framework directory contains packages of the TYPO3 Flow distribution.

  Application/
    The Application directory contains your own / application specific packages.

  Libraries/
    The Libraries directory contains 3rd party packages.


Additional Package Locations
----------------------------

Apart from the *Application*, *Framework* and *Libraries* package directories you
may define your very own additional package locations by just creating
another directory in the application's *Packages* directory. One
example for this is the TYPO3 Neos distribution, which expects packages with
website resources in a folder named *Sites*.

The location for Flow packages installed via Composer (as opposed to manually
placing them in a *Packages/* sub folder) is determined by looking at the package
type in the manifest file. This would place a package into *Packages/Acme*::

 "type": "typo3-flow-acme"

Package Directory Layout
========================

The TYPO3 Flow package directory structure follows a certain convention which has the advantage
that you don't need to care about any package-related configuration. If you put your files
into the right directories, everything will just work.

The directory layout inside a TYPO3 Flow package is as follows:

Classes/*VendorName*/*PackageName*
  This directory contains the actual source code for the package. Package authors
  are free to add (only!) class or interface files directly to this directory or add
  subdirectories to organize the content as necessary. All classes or interfaces
  below this directory are handled by the autoloading mechanism and will be
  registered at the object manager automatically (and will thus be considered
  "registered objects").

  One special file in here is the *Package.php* which contains the class with the
  package's bootstrap code (if needed).

Configuration
  All kinds of configuration which are delivered with the package reside in this
  directory. The configuration files are immutable and must not be changed by the
  user or administrator. The most prominent configuration files are the
  *Objects.yaml* file which may be used to configure the package's objects and
  the *Settings.yaml* file which contains general user-level settings.

Documentation
  Holds the package documentation. Please refer to the Documenter's Guide for
  more details about the directories and files within this directory.

Resources
  Contains static resources the package needs, such as library code, template files,
  graphics, ... In general, there is a distinction between public and private
  resources.

  Private
    Contains private resources for the package. All files inside this directory
    will never be directly available from the web.

    Installer/Distribution
      The files in this directory are copied to the root of a Flow installation
      when the package is installed or updated via `Composer`_. Anything in ``Defaults``
      is copied only, if the target does not exist (files are not overwritten).
      Files in ``Essentials`` are overwritten and thus kept up-to-date with the
      package they come from.
    Templates
      Template files used by the package should go here. If a user wants to modify
      the template it will end up elsewhere and should be pointed to by some
      configuration setting.
    PHP
      Should hold any PHP code that is an external library which should not be
      handled by the object manager (at least not by default), is of procedural
      nature or doesn't belong into the classes directory for any other reason.
    Java
      Should hold any Java code needed by the package. Repeat and rinse for
      Smalltalk, Modula, Pascal, ... ;)

  Public
    Contains public resources for the package. All files in this directory
    will be mirrored into TYPO3 Flow's *Web* directory by the Resource Manager
    (and therefore become accessible from the web). They will be delivered to
    the client directly without further processing.

    Although it is up to the package author to name the directories, we suggest the
    following directories:

    * Images
    * StyleSheets
    * JavaScript
    * Icons

Tests
  Unit
    Holds the unit tests for the package.

  Functional
    Holds the functional tests for the package.

As already mentioned, all classes which are found in the *Classes* directory will be
detected and registered. However, this only works if you follow the naming rules equally
for the class name as well as the filename. An example for a valid class name is
``\MyCompany\MyPackage\Controller\StandardController`` while the file containing this
class would be named *StandardController.php* and is expected to be in a directory
*MyCompany.MyPackage/Classes/MyCompany/MyPackage/Controller*.

All details about naming files, classes, methods and variables correctly can be found in
the TYPO3 Flow Coding Guidelines. You're highly encouraged to read (and follow) them.

Package Keys
============

Package keys are used to uniquely identify packages and provide them with a namespace for
different purposes. They save you from conflicts between packages which were provided by
different parties.

We use *vendor namespaces* for package keys, i.e. all packages which are released
and maintained by the TYPO3 Neos and Flow core teams start with ``TYPO3.*``. In your company
we suggest that you use your company name as vendor namespace.

Loading Order
=============

The loading order of packages follows the dependency chain as defined in the composer
manifests involved.

Activating and Deactivating Packages
====================================

All directories which are found below the *Packages* folder can hold
packages. Just make sure that you created a *composer.json* file in the
root directory of your package.

If no *PackageStates.php* exists in your *Configuration* folder, it will be created
and all found packages will be activated. If *PackageStates.php* exists, you can use the
package manager to activate and deactivate packages through the TYPO3 Flow command line script.

The TYPO3 Flow command line interface is triggered through the *flow* script
in the main directory of the TYPO3 Flow distribution. From a Unix
shell you should be able to run the script by entering ``./flow`` (on windows,
use ``flow.bat``).

To activate a package, use the ``package:activate`` command:

.. code-block:: bash

 $ ./flow package:activate <PackageKey>

To deactivate a package, use ``package:deactivate``. For a listing of all packages
(active and inactive) use ``package:list``.

Installing a Package
====================

There are various ways of installing packages. They can just be copied to a folder in
*Packages/*, either manually or by some tool, or by keeping them in your project's
SCM tool (directly or indirectly, via git submodules or svn:externals).

The true power of dependency management comes with the use of `Composer`_, though.
Installing a package through composer allows to install dependencies of that package
automatically as well.

If a package you would like to add is available on `Packagist`_ it can be installed
by running::

 composer require <vendor/package>

.. note::
 If you need to install `Composer`_ first, read the `installation instructions
 <http://getcomposer.org/download/>`_

In case a package is not available through `Packagist`_, you can still install via `Composer`_
as it supports direct fetching from popular SCM system. For this, define a repository entry
in your manifest to be able to use the package name as usual in the dependencies.

*composer.json*::

 "repositories": [
     {
         "type": "git",
         "url": "git://github.com/acme/demo.git"
     },
     …
 ],
 …
 "require": {
     …,
     "acme/demo": "dev-master"
 }

Creating a New Package
======================

Use the ``package:create`` command to create a new package:

.. code-block:: bash

	$ ./flow package:create Acme.Demo

This will create the package in *Packages/Application*. After that, adjust *composer.json*
to your needs. Apart from that no further steps are necessary.

Updating Packages
=================

The packages installed via `Composer`_ can be updated with the command::

 composer update

Package Meta Information
========================

All packages need to provide some meta information to TYPO3 Flow. The data is split in two
files, depending on primary use.

composer.json
-------------

The `Composer`_ manifest. It declares metadata like the name of a package as well
as dependencies, like needed PHP extensions, version constraints and other packages.
For details on the format and possibilities of that file, have a look at the `Composer`_
documentation.

Classes/*VendorName*/*PackageName*/Package.php
----------------------------------------------

This file contains bootstrap code for the package. If no bootstrap code is needed,
it does not need to exist.

*Example: Minimal Package.php* ::

	<?php
	namespace Acme\Demo;

	use TYPO3\Flow\Package\Package as BasePackage;

	/**
	 * The Acme.Demo Package
	 *
	 */
	class Package extends BasePackage {

		/**
		* Invokes custom PHP code directly after the package manager has been initialized.
		*
		* @param \TYPO3\Flow\Core\Bootstrap $bootstrap The current bootstrap
		* @return void
		*/
		public function boot(\TYPO3\Flow\Core\Bootstrap $bootstrap) {
			$bootstrap->registerRequestHandler(new \Acme\Demo\Quux\RequestHandler($bootstrap));

			$dispatcher = $bootstrap->getSignalSlotDispatcher();
			$dispatcher->connect('TYPO3\Flow\Mvc\Dispatcher', 'afterControllerInvocation', 'Acme\Demo\Baz', 'fooBar');
		}
	}
	?>

The bootstrap code can be used to wire some signal to a slot or to register
request handlers (as shown above), or anything else that can must be done
early the bootstrap stage.

Using 3rd Party Packages
========================

When using 3rd party packages via `Composer`_ a variety of issues can come up.

Reflection errors
-----------------

When a package includes tests and other resources Flow might run into trouble
when trying to reflect those. Since in most cases "fixing" such packages does
not make sense, a configuration option is provided to selectively ignore classes
from reflection. This allows a fine control going beyong simply disabling object
management (and thus features like DI and AOP) completely.

To exclude classes from object management (Reflection and Configuration building)
a sequence of package keys can be provided, each with a sequence of regular
expressions. Each regular expression will be tested against the list of fully
qualified class names in the package and classes will be excluded if matching::

  TYPO3:
    Flow:
      excludeClasses:
       'Acme.Broken' : ['.*']
       'other.weird.package' : ['Other\\Weird\\Package\\Tests\\.*']

Class loading
-------------

In a composer manifest various ways of autloloading can be configured. Currently
Flow only supports PSR-0 autoloading and will only use the first entry given in
the manifest. This leads to issues when loading some packages::

  "autoload": {
      "psr-0": {
          "Guzzle\\Tests": "tests/",
          "Guzzle": "src/"
      }
  },

In this case only the ``Guzzle\Tests`` entry will be used, leading to rather unexpected
results. This is of course an issue with the way Flow handles this, in the meantime
you need to adjust the manifest manually.

Other autoloading ways (classmap generation and files) are currently not supported by
Flow.

.. _TYPO3 project: http://typo3.org
.. _Composer:      http://getcomposer.org
.. _Packagist:     http://packagist.org
