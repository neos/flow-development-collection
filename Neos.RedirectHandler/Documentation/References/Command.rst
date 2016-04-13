.. _`Redirect Command Reference`:

Redirect Command Reference
=======================

.. note:

  This reference uses ``./flow`` as the command to invoke. If you are on
  Windows, this will probably not work, there you need to use ``flow.bat``
  instead.

The commands in this reference are shown with their full command identifiers.
On your system you can use shorter identifiers, whose availability depends
on the commands available in total (to avoid overlap the shortest possible
identifier is determined during runtime).

To see the shortest possible identifiers on your system as well as further
commands that may be available, use::

  ./flow help


Package *Neos.RedirectHandler*
---------------------

``Neos.RedirectHandler:redirection:add``
****************************************

**Adding custom redirects**

Creates a new custom redirect. Who can pass the options bellow in one line or assign them separately after running the command without the mandatory arguments. You can also add the optional argument ``host`` to define multiple hosts for the redirect.

If any redirect exists with the same ``source`` property, it will be replaced if the ``force`` property has been set.



Options
^^^^^^^

``--source``
  The origin for the redirect (relative path, without protocol or domain)
``--target``
  The target for the redirect (relative path, without protocol or domain)
``--statusCode``
  This is the status code that the redirect will return in the response header. In most cases this will be 301 (moved permanently)
``--hosts`` (optional)
  The hosts the redirect is valid for. If none is set, the redirect is valid for all domains.
``--force`` (optional)
  Replace existing redirects with the same ``source``.
  



``Neos.RedirectHandler:redirection:remove``
*******************************************

**Removing redirects**

This command is used the delete a single redirect. The redirect is identified by the ``source`` argument.

.. note:: When using multiple domains for redirects the ``host`` argument is necessary to identify the correct one.



Options
^^^^^^^

``--source``
  The origin for the redirect (relative path, without protocol or domain)
``--host`` (optional)
  Only remove redirects that use this host




``Neos.RedirectHandler:redirection:removeAll``
**********************************************

**Removing all redirects**

If only redirects for a certain domain should be deleted it is possible to add ``host`` as an optional argument.



Options
^^^^^^^

``--host`` (optional)
  Only remove redirects that use this host




``Neos.RedirectHandler:redirection:list``
*******************************************

**List all redirects**

Lists all saved redirects. Optionally it is possible to filter by ``host`` and to use the argument ``match`` to look for certain ``source`` or ``target`` paths.



Options
^^^^^^^

``--host`` (optional)
  Only remove redirects that use this host
``--match`` (optional)
  A string to match for the ``source`` or the ``target``




``Neos.RedirectHandler:redirection:export``
*******************************************

**Exporting redirects**

This command will export all redirects into a CSV format within the ``Data`` folder.
You can set a preferred filename before the export with the optional ``filename`` argument.



Options
^^^^^^^

``--filename`` (optional)
  The filename for the CSV file saved into the ``Data`` folder.




``Neos.RedirectHandler:redirection:import``
*******************************************

**Importing redirects**

This command is used to (re)import CSV files containing redirects.
The argument ``filename`` is the name of the file you uploaded to the ``Data`` folder.



Options
^^^^^^^

``--filename`` (optional)
  The filename for the CSV file saved the ``Data`` folder.


This is an extract of an importable redirect CSV:
.. code-block:: csv
locationA,locationB,301
locationC,locationD,301
locationD,locationE,301,neos.io

So the structure per line is:
``sourcePath``,``targetPath``,``statusCode``,``host`` (optional)


After a successful import a report will be shown. While `++` marks newly created redirects, `~~` marks already existing redirect source paths along with the used status code and ``source``.

.. note:: `redirection:import` will not delete pre-existing redirects. To do this run ``./flow redirection:removeall`` before the import.
**WARNING**: This will also delete all automatically generated redirects.