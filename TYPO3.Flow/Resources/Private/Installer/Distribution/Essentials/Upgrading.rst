Upgrading instructions
======================

This file contains instructions for upgrading your Flow 3.1 based
applications to TYPO3 Flow 3.2.

What has changed
----------------

There haven’t been API changes in Flow 3.2 which require your code to be adjusted. But the package management has
been refactored. This change is considered breaking due to the following:

- New packages are only automatically picked up if they were installed via composer or created through the Flow package management. In all other cases you need to run the: package:rescan command to pick new package up.
- Some @api classes and methods were deprecated and will be removed or changed in the next major Flow version.
- Some newly added methods of PackageManager and Package are used in the Flow core now, so if someone would reimplement both according to interface it would not work with Flow, but that is already the case without this change, so it shouldn’t be an issue.

A more detailed overview of what is new can be found in the `3.2 release notes <http://flowframework.readthedocs.io/en/3.2/TheDefinitiveGuide/PartV/ReleaseNotes/320.html>`_.

Upgrading your Packages
-----------------------

Upgrading existing code
^^^^^^^^^^^^^^^^^^^^^^^

Here comes the easier part. As with earlier changes to Flow that required code changes on the user side we provide a code
migration tool.
Given you have a Flow system with your (outdated) package in place you should run the following before attempting to fix
anything by hand::

 ./flow core:migrate --package-key Acme.Demo

The package key is optional, if left out it will work on all packages it finds (except for library packages and packages
prefixed with "TYPO3.*") - for the first run you might want to limit things a little to keep the overview, though.

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
 ./flow doctrine:migrationgenerate

in *Development Context*, padded with some manual checking and adjustments needs to be done.
That should result in a working package.

If it does not and you have no idea what to do next, please get in touch
with us. The `support page <http://flow.typo3.org/support/>`_ provides more
information.