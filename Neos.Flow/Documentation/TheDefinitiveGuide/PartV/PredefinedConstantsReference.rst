Predefined Constants Reference
==============================

The following constants are defined by the Flow core.

.. note::
 Every ``…PATH…`` constant contains forward slashes (``/``)
 as directory separator, no matter what operating system Flow is run on.

 Also note that every such path is absolute and has a trailing
 directory separator.

``FLOW_SAPITYPE`` (*string*)
  The current request type, which is either ``CLI`` or ``Web``.

``FLOW_PATH_FLOW`` (*string*)
  The absolute path to the Flow package itself

``FLOW_PATH_ROOT`` (*string*)
  The absolute path to the root of this Flow distribution, containing for example the ``Web``, ``Configuration``,
  ``Data``, ``Packages`` etc. directories.

``FLOW_PATH_WEB`` (*string*)
  Absolute path to the ``Web`` folder where, among others, the ``index.php`` file resides.

``FLOW_PATH_CONFIGURATION`` (*string*)
  Absolute path to the ``Configuration`` directory where the ``.yaml`` configuration files reside.

``FLOW_PATH_DATA`` (*string*)
  Absolute path to the ``Data`` directory, containing the ``Logs``, ``Persistent``, ``Temporary``,
  and other directories.

``FLOW_PATH_PACKAGES`` (*string*)
  Absolute path to the ``Packages`` directory, containing the ``Application``, ``Framework``,
  ``Sites``, ``Library``, and similar package directories.

``FLOW_VERSION_BRANCH`` (*string*)
  The current Flow branch version, for example ``1.2``.