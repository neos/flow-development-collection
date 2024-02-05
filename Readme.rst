|Code Climate| |StyleCI| |Latest Stable Version| |Commits since last release| |License| |Docs| |Slack| |Forum| |Issues| |Percentage of issues still open| |Average time to resolve an issue| |Translate| |Twitter|

.. |Average time to resolve an issue| image:: http://isitmaintained.com/badge/resolution/neos/flow-development-collection.svg
   :target: https://github.com/neos/flow-development-collection/issues
   :alt: issue resolution
.. |Percentage of issues still open| image:: http://isitmaintained.com/badge/open/neos/flow-development-collection.svg
   :target: https://github.com/neos/flow-development-collection/issues
   :alt: open issues
.. |Commits since last release| image:: https://img.shields.io/github/commits-since/neos/flow-development-collection/latest.svg
   :target: https://github.com/neos/flow-development-collection/releases/latest)
   :alt: commits since latest release
.. |Code Climate| image:: https://codeclimate.com/github/neos/flow-development-collection/badges/gpa.svg
   :target: https://codeclimate.com/github/neos/flow-development-collection
   :alt: Code Climate
.. |StyleCI| image:: https://styleci.io/repos/40963991/shield?style=flat&branch=8.2
   :target: https://styleci.io/repos/40963991
   :alt: StyleCI
.. |Latest Stable Version| image:: https://poser.pugx.org/neos/flow-development-collection/v/stable
   :target: https://packagist.org/packages/neos/flow-development-collection
   :alt: Latest Stable Version
.. |License| image:: https://poser.pugx.org/neos/flow-development-collection/license
   :target: https://raw.githubusercontent.com/neos/flow/8.2/LICENSE
   :alt: License
.. |Docs| image:: https://img.shields.io/badge/documentation-latest-blue.svg
   :target: https://flowframework.readthedocs.org/en/latest/
   :alt: Documentation
.. |Slack| image:: http://slack.neos.io/badge.svg
   :target: http://slack.neos.io
   :alt: Slack
.. |Forum| image:: https://img.shields.io/badge/forum-Discourse-39c6ff.svg
   :target: https://discuss.neos.io/
   :alt: Discussion Forum
.. |Issues| image:: https://img.shields.io/github/issues/neos/flow-development-collection.svg
   :target: https://github.com/neos/flow-development-collection/issues
   :alt: Issues
.. |Translate| image:: https://img.shields.io/badge/translate-weblate-85ae52.svg
   :target: https://hosted.weblate.org/projects/neos/
   :alt: Translation
.. |Twitter| image:: https://img.shields.io/twitter/follow/neoscms.svg?style=social
   :target: https://twitter.com/NeosCMS
   :alt: Twitter

---------------------------
Flow development collection
---------------------------

This repository is a collection of packages for the Flow framework (learn more on http://flow.neos.io/).
The repository is used for development and all pull requests should go into it.

If you want to use the Flow framework, please have a look at the documentation: https://flowframework.readthedocs.org/en/latest/

Contributing
============

If you want to contribute to Flow Framework and want to set up a development environment, then follow these steps:

Clone and install the flow dev distribution https://github.com/neos/flow-development-distribution via git and composer or use this shorthand:
``composer create-project neos/flow-development-distribution flow-development @dev --keep-vcs``

Note the **-distribution** package you create a project from, instead of just checking out this repository.

The code of the framework can then be found inside ``Packages/Framework``, which itself is the flow-development-collection Git repository (due to the ``--keep-vcs`` option above). You commit changes and create pull requests from this repository.
To test and commit changes to the framework switch into the Framework directory (``cd Packages/Framework``).

Here you can do all Git-related work (``git add .``, ``git commit``, etc).

Unit tests can be run here via ``../../bin/phpunit -c ../../Build/BuildEssentials/PhpUnit/UnitTests.xml``, functional tests via ``../../bin/phpunit -c ../../Build/BuildEssentials/PhpUnit/FunctionalTests.xml`` and static analysis via ``composer lint``.

To switch the branch you intend to work on run this command in the root of the dev distribution:
``git checkout 8.3 && composer update``

.. note:: We use an upmerging strategy, so create all bugfixes to lowest maintained branch that
contains the issue (typically the second last LTS release, check the diagram on
https://www.neos.io/features/release-process.html), or master for new features.

For more detailed information, see https://discuss.neos.io/t/development-setup/504 and https://discuss.neos.io/t/creating-a-pull-request/506
