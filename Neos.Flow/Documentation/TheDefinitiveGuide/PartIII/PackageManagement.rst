==================
Package Management
==================

.. sectionauthor:: Robert Lemke <robert@neos.io>


Flow is a package-based system. In fact, Flow itself is just a package as well - but
obviously an important one. Packages act as a container for different matters: Most of
them contain PHP code which adds certain functionality, others only contain documentation
and yet other packages consist of templates, images or other resources.

Package Locations
=================

Framework and Application Packages
----------------------------------

Flow packages are located in a sub folder of the *Packages/* directory. A typical
application (such as Neos for example) will use the core packages which are bundled with
Flow and use additional packages which are specific to the application. The framework
packages are kept in a directory called *Framework* while the application specific
packages reside in the *Application* directory. This leads to the following
folder structure:

Configuration/
  The global configuration folder

Data/
  The various data folders, temporary as well as persistent

Packages/
  Framework/
    The Framework directory contains packages of the Flow distribution.

  Application/
    The Application directory contains your own / application specific packages.

  Libraries/
    The Libraries directory contains 3rd party packages.


Additional Package Locations
----------------------------

Apart from the *Application*, *Framework* and *Libraries* package directories you
may define your very own additional package locations by just creating
another directory in the application's *Packages* directory. One
example for this is the Neos distribution, which expects packages with
website resources in a folder named *Sites*.

The location for Flow packages installed via Composer (as opposed to manually
placing them in a *Packages/* sub folder) is determined by looking at the package
type in the manifest file. This would place a package into *Packages/Acme*::

 "type": "neos-acme"

If you would like to use ``package:create`` to create packages of this type in
*Packages/Acme* instead of the default location *Packages/Application*, add an
entry in the *Settings.yaml* of the package that expects packages of that type::

  Neos:
    Flow:
      package:
        packagesPathByType:
          'neos-acme': 'Acme'

.. note::

	Packages where the type starts with ``typo3-flow-`` or ``neos-`` are considered
	Flow packages and will therefore be reflected and proxied by default. We recommend
	using only the ``neos-`` prefix for the type when creating new packages (but only from
	Flow 3.2 upwards) as the other is deprecated and will stop working in the next major.

Package Directory Layout
========================

The Flow package directory structure follows a certain convention which has the advantage
that you don't need to care about any package-related configuration. If you put your files
into the right directories, everything will just work.

The directory layout inside a Flow package is as follows:

Classes
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
    will be mirrored into Flow's *Web* directory by the ResourceManager
    (and therefore become accessible from the web). They will be delivered to
    the client directly without further processing.

    Although it is up to the package author to name the directories, we suggest the
    following directories:

    * Images
    * Styles
    * Scripts

    The general rule for this is: The folder uses the plural form of the resource type
    it contains.

    Third party bundles that contain multiple resources such as ``jQuery UI`` or ``Twitter Bootstrap``
    should reside in a sub directory ``Libraries``.

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
the Flow Coding Guidelines. You're highly encouraged to read (and follow) them.

Package Keys
============

Package keys are used to uniquely identify packages and provide them with a namespace for
different purposes. They save you from conflicts between packages which were provided by
different parties.

We use *vendor namespaces* for package keys, i.e. all packages which are released
and maintained by the Neos and Flow core teams start with ``Neos.*`` (for historical
reasons) or ``Neos.*``. In your company we suggest that you use your company name as vendor
namespace.

To define the package key for your package we recommend you set the "extra.neos.package-key"
option in your composer.json as in the following example:

*composer.json*::

 "extra": {
     "neos": {
         "package-key": "Vendor.PackageKey"
     }
 }


Loading Order
=============

The loading order of packages follows the dependency chain as defined in the composer
manifests involved, solely taking the "require" part into consideration.
Additionally you can configure packages that should be loaded before by adding an array
of composer package names to "extra.neos.loading-order.after" as in this example:

*composer.json*::

 "extra": {
     "neos": {
         "loading-order": {
             "after": [
                  "some/package"
             ]
         }
     }
 }

Activating and Deactivating Packages
====================================

All directories which are found below the *Packages* folder can hold
packages. Just make sure that you created a *composer.json* file in the
root directory of your package.

If no *PackageStates.php* exists in your *Configuration* folder, it will be created
and all found packages will be activated. If *PackageStates.php* exists, you can use the
package manager to activate and deactivate packages through the Flow command line script.

The Flow command line interface is triggered through the *flow* script
in the main directory of the Flow distribution. From a Unix
shell you should be able to run the script by entering ``./flow`` (on windows,
use ``flow.bat``).

To activate a package, use the ``package:activate`` command:

.. code-block:: bash

 $ ./flow package:activate <PackageKey>

To deactivate a package, use ``package:deactivate``. For a listing of all packages
(active and inactive) use ``package:list``.

.. note::

	We discourge using this feature. It is available for historical reasons and might
	stay around for a while, but might be deprecated and removed in the future. Our
	best practice is to remove packages that are not needed.

Installing a Package
====================

There are various ways of installing packages. They can just be copied to a folder in
*Packages/*, either manually or by some tool, or by keeping them in your project's
VCS tool (directly or indirectly, via git submodules or svn:externals).

The true power of dependency management comes with the use of `Composer`_, though.
Installing a package through composer allows to install dependencies of that package
automatically as well. That is why we suggest only using composer to install packages.

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

All packages need to provide some meta information to Flow. The data is split in two
files, depending on primary use.

composer.json
-------------

The `Composer`_ manifest. It declares metadata like the name of a package as well
as dependencies, like needed PHP extensions, version constraints and other packages.
For details on the format and possibilities of that file, have a look at the `Composer`_
documentation.

Classes/Package.php
----------------------------------------------

This file contains bootstrap code for the package. If no bootstrap code is needed,
it does not need to exist.

*Example: Minimal Package.php* ::

	<?php
	namespace Acme\Demo;

	use Neos\Flow\Package\Package as BasePackage;

	/**
	 * The Acme.Demo Package
	 *
	 */
	class Package extends BasePackage {

		/**
		* Invokes custom PHP code directly after the package manager has been initialized.
		*
		* @param \Neos\Flow\Core\Bootstrap $bootstrap The current bootstrap
		* @return void
		*/
		public function boot(\Neos\Flow\Core\Bootstrap $bootstrap) {
			$bootstrap->registerRequestHandler(new \Acme\Demo\Quux\RequestHandler($bootstrap));

			$dispatcher = $bootstrap->getSignalSlotDispatcher();
			$dispatcher->connect(\Neos\Flow\Mvc\Dispatcher::class, 'afterControllerInvocation', \Acme\Demo\Baz::class, 'fooBar');
		}
	}
	?>

The bootstrap code can be used to wire some signal to a slot or to register
request handlers (as shown above), or anything else that can must be done
early the bootstrap stage.

After creating a new ``Package.php`` in your package you need to execute:

.. code-block:: bash

	$ ./flow flow:package:rescan

Otherwise the ``Package.php`` will not be found.

Using Third Party Packages
==========================

When using 3rd party packages via `Composer`_ everything should work as expected.
Flow uses the `Composer`_ autoloader to load code.
Third party packages will not have any Flow "magic" enabled by default. That means
no AOP will work on classes from third party packages. If you need this see :ref:`sect-enabling-non-flow-packages`

-----

.. _Composer: https://getcomposer.org/
.. _Packagist: https://packagist.org/
