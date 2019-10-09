|Travis Build Status| |Code Climate| |StyleCI| |Latest Stable Version| |License| |Docs| |API| |Slack| |Forum| |Issues| |Translate| |Twitter|

.. |Travis Build Status| image:: https://travis-ci.org/neos/flow-development-collection.svg?branch=master
   :target: https://travis-ci.org/neos/flow-development-collection
   :alt: Travis
.. |Code Climate| image:: https://codeclimate.com/github/neos/flow-development-collection/badges/gpa.svg
   :target: https://codeclimate.com/github/neos/flow-development-collection
   :alt: Code Climate
.. |StyleCI| image:: https://styleci.io/repos/40963991/shield?style=flat&branch=master
   :target: https://styleci.io/repos/40963991
   :alt: StyleCI
.. |Latest Stable Version| image:: https://poser.pugx.org/neos/flow-development-collection/v/stable
   :target: https://packagist.org/packages/neos/flow-development-collection
   :alt: Latest Stable Version
.. |License| image:: https://poser.pugx.org/neos/flow-development-collection/license
   :target: https://raw.githubusercontent.com/neos/flow/master/LICENSE
   :alt: License
.. |Docs| image:: https://img.shields.io/badge/documentation-latest-blue.svg
   :target: https://flowframework.readthedocs.org/en/latest/
   :alt: Documentation
.. |API| image:: https://img.shields.io/badge/API%20docs-master-blue.svg
   :target: http://neos.github.io/flow/master/
   :alt: API Docs
.. |Slack| image:: http://slack.neos.io/badge.svg
   :target: http://slack.neos.io
   :alt: Slack
.. |Forum| image:: https://img.shields.io/badge/forum-Discourse-39c6ff.svg
   :target: https://discuss.neos.io/
   :alt: Discussion Forum
.. |Issues| image:: https://img.shields.io/github/issues/neos/flow-development-collection.svg
   :target: https://github.com/neos/flow-development-collection/issues
   :alt: Issues
.. |Translate| image:: https://img.shields.io/badge/translate-Crowdin-85ae52.svg
   :target: http://translate.neos.io/
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

If you want to contribute to Flow framework and want to set up a development environment, then follow these steps:

``composer create-project neos/flow-development-distribution flow-development dev-master --keep-vcs``

Note the **-distribution** package you create a poject from, instead of just checking out this repository.

The code for the framework is then found inside ``Packages/Framework``, which is itself the git repository for this flow-development-collection (due to the ``--keep-vcs`` option above), that you need to apply changes to and create pull requests for.

In the root of the development-distribution you created:

To run tests, run ``./bin/phpunit -c ./Build/BuildEssentials/PhpUnit/UnitTests.xml`` for unit or ``./bin/phpunit -c ./Build/BuildEssentials/PhpUnit/FunctionalTests.xml`` for functional/integration tests. If you are on 6.0 or later, you can
also run ``./bin/psalm --config=Packages/Framework/psalm.xml`` to run static analysis tools.

To switch the branch you work on:
``git checkout 4.3 && composer update``

.. note:: We use an upmerging strategy, so create all bugfixes to lowest maintained branch that contains the issue (typically the second last LTS release, which is 4.3 currently), or master for new features.

For more detailed information, see https://discuss.neos.io/t/development-setup/504 and https://discuss.neos.io/t/creating-a-pull-request/506
