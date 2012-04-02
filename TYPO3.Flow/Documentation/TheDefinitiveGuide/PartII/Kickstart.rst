=========
Kickstart
=========

.. sectionauthor:: Robert Lemke <robert@typo3.org>

FLOW3 makes it easy to start with a new application. The ``Kickstart`` package provides
template based scaffolding for generating an initial layout of packages, controllers,
models and views.

.. note::

	At the time of this writing these functions are only available through FLOW3's command
	line interface. This might change in the future as a graphical interface to the
	kickstarter is developed.

Command Line Tool
=================

The script *flow3* resides in the main directory of the FLOW3 distribution.
From a shell you should be able to run the script by entering ``./flow3``:

.. code-block:: none

	myhost:tutorial johndoe$ ./flow3
	FLOW3 1.0.0 ("Development" context)
	usage: ./flow3 <command identifier>

	See './flow3 help' for a list of all available commands.

To get an overview of all available commands, enter ``./flow3 help``:

.. code-block:: none

	myhost:tutorial johndoe$ ./flow3 help
	FLOW3 1.0.0 ("Development" context)
	usage: ./flow3 <command identifier>

	The following commands are currently available:

	PACKAGE "TYPO3.FLOW3":
	-------------------------------------------------------------------------------
	* flow3:cache:flush                        Flush all caches
	  cache:warmup                             Warm up caches

	* flow3:core:setfilepermissions            Adjust file permissions for CLI and
	                                           web server access
	* flow3:core:shell                         Run the interactive Shell

	  doctrine:validate                        Validate the class/table mappings
	  doctrine:create                          Create the database schema
	  doctrine:update                          Update the database schema
	  doctrine:entitystatus                    Show the current status of entities
	                                           and mappings
	  doctrine:dql                             Run arbitrary DQL and display
	                                           results
	  doctrine:migrationstatus                 Show the current migration status
	  doctrine:migrate                         Migrate the database schema
	  doctrine:migrationexecute                Execute a single migration
	  doctrine:migrationversion                Mark/unmark a migration as migrated
	  doctrine:migrationgenerate               Generate a new migration

	  help                                     Display help for a command

	  package:create                           Create a new package
	  package:delete                           Delete an existing package
	  package:activate                         Activate an available package
	  package:deactivate                       Deactivate a package
	  package:list                             List available packages
	  package:import                           Import a package from a remote
	                                           location

	  routing:list                             List the known routes

	  security:importpublickey                 Import a public key
	  security:importprivatekey                Import a private key


	PACKAGE "TYPO3.KICKSTART":
	-------------------------------------------------------------------------------
	  kickstart:package                        Kickstart a new package
	  kickstart:actioncontroller               Kickstart a new action controller
	  kickstart:commandcontroller              Kickstart a new command controller
	  kickstart:model                          Kickstart a new domain model
	  kickstart:repository                     Kickstart a new domain repository

	* = compile time command

	See './flow3 help <commandidentifier>' for more information about a specific command.

Depending on your FLOW3 version you'll see more or less the above available
commands listed.


Kickstart the package
=====================

Let's create a new package **Blog** inside the Vendor namespace **TYPO3**:

.. code-block:: none

	myhost:tutorial johndoe$ ./flow3 kickstart:package TYPO3.Blog

The kickstarter will create two files:

.. code-block:: none

	Created .../TYPO3.Blog/Classes/Controller/StandardController.php
	Created .../TYPO3.Blog/Resources/Private/Templates/Standard/Index.html

and the directory *Packages/Application/TYPO3.Blog/* should now contain the
skeleton of the future ``Blog`` package:

.. code-block:: none

	myhost:tutorial johndoe$ cd Packages/Application/
	myhost:Application johndoe$ find TYPO3.Blog
	TYPO3.Blog
	TYPO3.Blog/Classes
	TYPO3.Blog/Classes/Controller
	TYPO3.Blog/Classes/Controller/StandardController.php
	TYPO3.Blog/Classes/Package.php
	TYPO3.Blog/Configuration
	TYPO3.Blog/Documentation
	TYPO3.Blog/Meta
	TYPO3.Blog/Meta/Package.xml
	TYPO3.Blog/Resources
	TYPO3.Blog/Resources/Private
	TYPO3.Blog/Resources/Private/Templates
	TYPO3.Blog/Resources/Private/Templates/Standard
	TYPO3.Blog/Resources/Private/Templates/Standard/Index.html
	TYPO3.Blog/Tests
	TYPO3.Blog/Tests/Functional
	TYPO3.Blog/Tests/Unit

Switch to your web browser and check at http://dev.tutorial.local/typo3.blog if the
generated controller produces some output:

.. image:: /Images/GettingStarted/FreshBlogPackage.png

.. tip::
	If you get an error at this point, like a "404 Not Found" this could be
	caused by outdated cache entries. Because FLOW3 should be running in
	``Development`` context at this point, it is supposed to detect changes to
	code and resource files, but this seems to sometimes fail... Before you go
	crazy looking for an error on your side, **try clearing the cache manually**
	by removing the contents of ``Data/Temporary/``.

Kickstart Controllers
=====================

If you look at the drawing of our overall model you'll notice that you need controllers
for the most important domain model, being ``Post``. We also need a ``SetupController``
which initially sets up the blog. Create them with the kickstarter as well:

.. code-block:: none

	myhost:tutorial johndoe$ ./flow3 kickstart:actioncontroller TYPO3.Blog Setup

resulting in:

.. code-block:: none

	Created .../TYPO3.Blog/Classes/Controller/SetupController.php
	Created .../TYPO3.Blog/Resources/Private/Templates/Setup/Index.html

For the ``PostController`` we know that we'll need some standard actions, so let's
have them created as well:

.. code-block:: none

	myhost:tutorial johndoe$ ./flow3 kickstart:actioncontroller --generate-actions --generate-related TYPO3.Blog Post

resulting in:

.. code-block:: none

	Created .../TYPO3.Blog/Classes/Domain/Model/Post.php
	Created .../TYPO3.Blog/Classes/Domain/Repository/PostRepository.php
	Created .../TYPO3.Blog/Classes/Controller/PostController.php
	Created .../TYPO3.Blog/Resources/Private/Layouts/Default.html
	Created .../TYPO3.Blog/Resources/Private/Templates/Post/Index.html
	Created .../TYPO3.Blog/Resources/Private/Templates/Post/New.html
	Created .../TYPO3.Blog/Resources/Private/Templates/Post/Edit.html
	Created .../TYPO3.Blog/Resources/Private/Templates/Post/Show.html

These new controllers can now be accessed via

* http://dev.tutorial.local/typo3.blog/setup and
* http://dev.tutorial.local/typo3.blog/post

respectively.

Please delete the file ``StandardController.php`` and its corresponding template
directory as you won't need them for our sample application [#]_.

Kickstart Models and Repositories
=================================

The kickstarter can also generate models and repositories, as you have seen above
when using the ``--generate-related`` option while kickstarting the ``PostController``.
Of course that can also be done specifically with the ``kickstart:model`` command.

Before we do this, you should have a look at the next section on models and repositories.

.. [#]	If you know you won't be using the StandardController, you can create a
		completely empty package with the ``package:create`` command.
