Upgrading instructions
======================

This file contains instructions for upgrading your Flow 3.3 based
applications to Neos Flow 4.0

What has changed
----------------

Neos 3.0 and Flow 4.0 represent the biggest refactoring effort the Neos project has undergone so far. Not only have Neos
and Flow, and more than 100 related packages, been ported over to the new Neos namespace - you can now also say hello to
Fusion, which is the new name for TypoScript2. These steps are the basis for all the exciting things that we have
planned for Neos and Flow in the future.

Since a lot of refactoring, especially regarding the naming of things, has been done, developers will need to get
familiar with a few changes. This was necessary to prepare our basis for the features we are planning to build. Here's a
list of the most important changes and renamings, to help you get used to Neos 3.0 quickly. There's also a detailed
step-by-step upgrade guide further down in this post.

Neos Namespace
^^^^^^^^^^^^^^

Up until Neos 2.3, we were still using the TYPO3 namespace for all our PHP classes in Neos and Flow. The team pulled a
bunch of long nights, armed with a few crates of beer (but mostly coffee), to remove every reference to the old
namespace from both Neos and Flow. We’re happy to see this completed. Flow is now in the Neos\Flow namespace, Neos
itself is using Neos\Neos. This is a rather trivial, but very important change as it breaks compatibility with
practically all sites and packages developed for pre-3.0. This means that there’s quite a bit of code to adust when you
upgrade a package to Neos 3.0 / Flow 4.0. But fear not, we solved migration the "Flow" way – most of the adjustments can
be applied automatically! We have compiled a list of things to look at further below in this post.

TypoScript2 becomes Fusion
^^^^^^^^^^^^^^^^^^^^^^^^^^

The name TypoScript has, until now, been used for both TYPO3 TypoScript and “our” rendering layer, called TypoScript2.
As the two languages do not have much in common anymore and many developers are confused by the similar names, the team
decided to rename TypoScript2 to Fusion with Neos 3.0. This means that the name TypoScript is officially deprecated in
Neos. We even get a new file ending - say hello to .fusion!

Having said that, to not break compatibility too badly, we will continue to support the legacy .ts2 file ending and will
also provide a legacy TypoScriptService until the release of Neos 4.0. Check the upgrade guide below to see what you
will need to change.

PHP 7.1 support
^^^^^^^^^^^^^^^

PHP 7.1 and Flow 3.3/Neos 2.3 have not been getting along very well, breaking the rendering (Fluid and Fusion) for most
sites. This has been fixed, Neos 3.0 and Flow 4.0 are fully compatible with PHP 7.1. Additionally, since PHP 7.0 a few
more keywords have been reserved for future use. Among them are “Resource” and “Object”, which previously were used as
class names in Flow’s resource framework. Even though this does not cause real problems at present, we refactored our
class names and namespaces to comply with these new reserved keywords in order to be compatible with future versions of
PHP.

PSR-4 autoloading replaces PSR-0
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

All our packages now use PSR-4 autoloading. In most cases, this means that you will move all your package content from
something like Packages/Sites/Vendor.Namespace/Vendor/Namespace/... to just Packages/Sites/Vendor.Namespace, and update
your composer.json to indicate the use of PSR-4 instead of PSR-0.

Upgrading your Packages
-----------------------

Upgrading existing code
^^^^^^^^^^^^^^^^^^^^^^^

Here comes the easier part. As with earlier changes to Flow that required code changes on the user side we provide a
code migration tool. Given you have a Flow system with your (outdated) package in place you should run the following
before attempting to fix anything by hand ::

 ./flow core:migrate --package-key Acme.Demo

The package key is optional, if left out it will work on all packages it finds (except for library packages and packages
prefixed with "Neos.*") - for the first run you might want to limit things a little to keep the overview, though.

Make sure to run::

 ./flow help core:migrate

to see all the other helpful options this command provides.

Inside core:migrate
"""""""""""""""""""

The tool roughly works like this:

* Collect all code migrations from packages

* Collect all files from all packages (except *Framework* and
  *Libraries*) or the package given with ``--package-key``
* For each migration and package

  * Check for clean git working copy (otherwise skip it)
  * Check if migration is needed (looks for Migration footers in commit
    messages)
  * Apply migration and commit the changes

Afterwards you probably get a list of warnings and notes from the
migrations, check those to see if anything needs to be done manually.

Check the created commits and feel free to amend as needed, should
things be missing or wrong. The only thing you must keep in place from
the generated commit messages is the Migration: … footer. It is used to
detect if a migration has been applied already, so if you drop it,
things might get out of hands in the future.

Upgrading the database schema
-----------------------------

Upgrading the schema is done by running::

 ./flow doctrine:migrate

to update your database with any changes to the framework-supplied
schema.

Famous last words
-----------------

In a nutshell, running::

 ./flow core:migrate
 ./flow doctrine:migrate

in *Development Context*, padded with some manual checking and adjustments needs to be done.
That should result in a working package.

If it does not and you have no idea what to do next, please get in touch
with us. The `support page <https://www.neos.io/support/>`_ provides more
information.
