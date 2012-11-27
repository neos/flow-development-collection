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
application (such as TYPO3 for example) will use the core packages which are bundled with
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

Apart from the *Application* and *Framework* package directories you
may define your very own additional package locations by just creating
another directory in the application's *Packages* directory. One
example for this is the TYPO3 distribution, which expects packages with
website resources in a folder named *Sites*.

Loading Order
-------------

The loading order of additional package locations is undefined, except that TYPO3 Flow itself will of
course always be loaded first.


Package Directory Layout
========================

The TYPO3 Flow package directory structure follows a certain convention which has the advantage
that you don't need to care about any package-related configuration. If you put your files
into the right directories, everything will just work.

The suggested directory layout inside a TYPO3 Flow package is as follows:

Classes/*VendorName*/*PackageName*
  This directory contains the actual source code for the package. Package authors
  are free to add (only!) class or interface files directly to this directory or add
  subdirectories to organize the content as necessary. All classes or interfaces
  below this directory are handled by the autoloading mechanism and will be
  registered at the object manager automatically (and will thus be considered
  "registered objects").

  One special file in here is the *Package.php* which contains the class with the
  package's bootstrap code if needed.

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
  Public
    Contains public resources for the package. All files in this directory
    will be mirrored into TYPO3 Flow's *Web* directory by the Resource Manager
    (and therefore become accessible from the web).

  Although it is up to the package author to name the directories, we suggest the
  following conventions for directories below ``Private`` and ``Public``:

  Media
    This directory holds images, PDF, Flash, CSS and other files that will be
    delivered to the client directly without further processing.
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
    Smalltalk, Modula, Pascal, ;)

  More directories can be added as needed.

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
and maintained by the TYPO3 and TYPO3 Flow core teams start with ``TYPO3.*``. In your company
we suggest that you use your company name as vendor namespace.

Importing and Installing Packages
=================================

All directories which are found below the *Packages* folder can hold
packages. Just make sure that you created a *composer.json* file in the
root directory of your package.

If no *PackageStates.php* exists in your *Configuration* folder, it will be created
and all found packages will be activated. If *PackageStates.php* exists, you can use the
package manager to activate and deactivate packages through the TYPO3 Flow command line script.

.. tip:: It is very convenient for continuous integration and deployment scenarios that
	all found packages on the first hit will be automatically registered.

The TYPO3 Flow command line interface is triggered through the *flow* script
in the main directory of the TYPO3 Flow distribution. From a Unix
shell you should be able to run the script by entering ``./flow`` (on windows,
use ``flow.bat``).

To activate a package, use the ``package:activate`` command:

.. code-block:: bash

	$ ./flow package:activate <PackageKey>

To deactivate a package, use ``package:deactivate``. For a listing of all packages
(active and inactive) use ``package:list``.

Package Manager
===============

The Package Manager is in charge configuring, activating and deactivating
packages and registers their objects and resources.

.. note::

	Dependency management, package installation and constraints checking is
	done by `Composer`_.

Creating a New Package
======================

Use the ``package:create`` command to create a new package:

.. code-block:: bash

	$ ./flow package:create Acme.Demo

This will create the package in *Packages/Application*. After that, adjust *composer.json*
to your needs. Apart from that no further steps are necessary.

Package Meta Information
========================

All packages need to provide some meta information to TYPO3 Flow. The data is split in two
files, depending on primary use.

composer.json
-------------

The `Composer`_ manifest. It declares metadata like the name of a package as well
as dependencies, like needed PHP extensions, version constraints and other packages.

Classes/*VendorName*/*PackageName*/Package.php
----------------------------------------------

This file contains bootstrap code for the package. If no bootstrap code is needed,
it does not need to exist.

*Example: Minimal Package.php* ::

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
  			…
		}
	}

.. _TYPO3 project:         http://typo3.org
.. _http://typo3.org/ns/2008/flow/package/Package.rng: http://typo3.org/ns/2008/flow/package/Package.rng