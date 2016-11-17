----------------
Neos Filemonitor
----------------

.. note:: This repository is a **read-only subsplit** of a package that is part of the
          Flow framework (learn more on `www.flowframework.io <http://www.flowframework.io/>`_).

If you want to use the Flow framework, please have a look at the `Flow documentation
<http://flowframework.readthedocs.org/en/stable/>`_

Contribute
----------

If you want to contribute to the Flow framework, please have a look at
https://github.com/neos/flow-development-collection - it is the repository
used for development and all pull requests should go into it.

Dependenies
-----------
Tests depend on Flows BaseTestCase, SignalSlot, Caches and Flow logging additionally
the static method ``createFileMonitorAtBoot`` depends on various Flow core classes
and should be refactored to become part of the Flow core.
