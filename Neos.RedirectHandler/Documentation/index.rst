=======================
How to manage redirects
=======================


Adding redirects
================
To create a new redirect use `./flow redirection:add`. You will be asked for the following arguments:

- `sourcePath` The origin for the redirect (relative path, without protocol or domain).
- `targetPath` The target for the redirect (relative path, without protocol or domain).
- `statusCode` This is the status code that the redirect will return in the response header. In most cases this will be 301 (moved permanently).


You can also pass the arguments above in one line. In this case you can also add the optional argument `host` to define multiple hosts for the redirect.
`./flow redirection:add -source-path="locationA" -target-path="locationB" -status-code="301" -host="neos.io"`

Removing redirects
==================
To remove a single redirect `./flow redirection:remove` can be used. The redirect is identified by the `source-path` argument.

.. note:: When using multiple domains for redirects the `host` argument is necessary to identify the correct one.

Removing all redirects
======================
To remove all redirects use `./flow redirection:removeall`.
If only redirects for a certain domain should be deleted it is possible to add the host as an optional argument e.g. `./flow redirection:removeall -host="neos.io"`

Importing and exporting redirects with CSV
==========================================
When managing many redirects exporting and importing redirects via CSV can save a lot of time.

`./flow redirection:export` will export all redirects into a CSV format within the `Data`` folder.

You can set an preferred filename before the export with the `filename` argument.

With `./flow redirection:import -filname="myRedirects.csv"` you can (re)import CSV files containing redirects.
While the argument filename is the name of the file you uploaded to the `Data` folder.

This is an extract of an importable redirect CSV:
.. code-block:: csv
locationA,locationB,301
locationC,locationD,301
locationD,locationE,301,neos.io

So the structure per line is:
`sourcePath`,`targetPath`,`statusCode`,`host` (optional)


After a successful import a report will be shown. While `+` marks newly created redirects, `~` marks already existing redirect source paths along with the used status code and `sourcePath`.


.. note:: `redirection:import` will not delete pre-existing redirects. To do this run `./flow redirection:removeall` before the import. *WARNING*: This will also delete all automatically generated redirects.

=========================================
Automatically generated redirects in Neos
=========================================

Whenever you change the `URL path segment` or move a document node, a redirect will automatically be generated as soon as it is published into the live workspace.

..note:: To get an overview over all currently active redirects you can always run `./flow redirection:export` to receive an CSV file with all your redirects.

For the next release, there will also be a backend module to show and manage redirects in the Neos backend.

===========================
Configuration for redirects
===========================

You can configure the default behaviour for automatically generated redirects within your `Settings.yaml`

.. code-block:: yaml

Neos:
 RedirectHandler:
  features:
    hitCounter: true
  statusCode:
    'redirect': 307
    'gone': 410


The following options are available:

- `hitCounter`: turn on/off the hit counter for redirects.
- `statusCode`: define the default status code for redirect or gone status (node deleted).