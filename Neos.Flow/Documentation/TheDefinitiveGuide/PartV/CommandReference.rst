.. _`Flow Command Reference`:

Flow Command Reference
======================

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

The following reference was automatically generated from code on 2020-12-07


.. _`Flow Command Reference: NEOS.FLUIDADAPTOR`:

Package *NEOS.FLUIDADAPTOR*
---------------------------


.. _`Flow Command Reference: NEOS.FLUIDADAPTOR neos.fluidadaptor:documentation:generatexsd`:

``neos.fluidadaptor:documentation:generatexsd``
***********************************************

**Generate Fluid ViewHelper XSD Schema**

Generates Schema documentation (XSD) for your ViewHelpers, preparing the
file to be placed online and used by any XSD-aware editor.
After creating the XSD file, reference it in your IDE and import the namespace
in your Fluid template by adding the xmlns:* attribute(s):
<html xmlns="http://www.w3.org/1999/xhtml" xmlns:f="https://neos.io/ns/Neos/Neos/ViewHelpers" ...>

Arguments
^^^^^^^^^

``--php-namespace``
  Namespace of the Fluid ViewHelpers without leading backslash (for example 'Neos\FluidAdaptor\ViewHelpers'). NOTE: Quote and/or escape this argument as needed to avoid backslashes from being interpreted!



Options
^^^^^^^

``--xsd-namespace``
  Unique target namespace used in the XSD schema (for example "http://yourdomain.org/ns/viewhelpers"). Defaults to "https://neos.io/ns/<php namespace>".
``--target-file``
  File path and name of the generated XSD schema. If not specified the schema will be output to standard output.
``--xsd-domain``
  Domain used in the XSD schema (for example "http://yourdomain.org"). Defaults to "https://neos.io".





