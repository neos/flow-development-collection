Predefined Constants Reference
==============================

The following constants are defined by the FLOW3 core.

.. note::
 Every ``…PATH…`` constant contains forward slashes (``/``)
 as directory separator, no matter what operating system FLOW3 is run on.

 Also note that every such path is absolute and has a trailing
 directory separator.

``FLOW3_SAPITYPE`` (*string*)
  The current request type, which is either ``CLI`` or ``Web``.

``FLOW3_PATH_FLOW`` (*string*)
  The absolute path to the FLOW3 package itself

``FLOW3_PATH_ROOT`` (*string*)
  The absolute path to the root of this FLOW3 distribution, containing
  for example the ``Web``, ``Configuration``, ``Data``, ``Packages``
  etc. directories.

``FLOW3_PATH_WEB`` (*string*)
  Absolute path to the ``Web`` folder where, among others, the
  ``index.php`` file resides.

``FLOW3_PATH_CONFIGURATION`` (*string*)
  Absolute path to the ``Configuration`` directory where the ``.yaml``
  configuration files reside.

``FLOW3_PATH_DATA`` (*string*)
  Absolute path to the ``Data`` directory, containing the ``Logs``,
  ``Persistent``, ``Temporary``, and other directories.

``FLOW3_PATH_PACKAGES`` (*string*)
  Absolute path to the ``Packages`` directory, containing the
  ``Application``, ``Framework``, ``Sites``, ``Library``, and similar
  package directories.

``FLOW3_VERSION_BRANCH`` (*string*)
  The current FLOW3 branch version, for example ``1.1``.
