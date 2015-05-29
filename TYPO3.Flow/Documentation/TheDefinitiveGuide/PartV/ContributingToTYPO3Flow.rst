.. _ch-contributing:

==========================
Contributing to TYPO3 Flow
==========================

Got time, a computer and a brain? Here is how you can help:

Report and Validate Issues
==========================

We don't code bugs, at least not on purpose. But if you find one, report it in
our issue tracker. But please help us to solve it by attaching a detailed description
of how to reproduce the issue. If you can provide a unit test that shows the bug,
this rocks big time.

* *Tasks:* Find bugs, describe them, reproduce them in a unit test
* *Skills needed:* Attention to detail, knowledge about PHP and PHPUnit is a plus

**Report bugs in the** `TYPO3 Flow JIRA issue tracker <https://jira.typo3.org/browse/FLOW/>`_ **!**

Improve Documentation
=====================

A complex system like ours needs a lot of documentation. And despite the
complexity that documentation should be easy and fun to read. Right?

* *Tasks:* Proof read existing documentation, writing new documentation
* *Skills needed:* Writing skills and very good english are a must

For a quick start follow these simple steps:

#. Checkout the Package TYPO3.Flow with Git, and set up the git repository so you can contribute:

   .. code-block:: none

     $ git clone git://git.typo3.org/Packages/TYPO3.Flow.git
     $ # set up git so you can push to Gerrit

   If you do not know how to set up Git correctly, follow the easy steps of
   `our git wizard <http://www.wwwision.de/githelper/#Packages/TYPO3.Flow.git>`_.
   Of course you can also use the package TYPO3.Flow from any TYPO3 Flow distribution you
   installed already.

#. Read the documentation and adjust it as needed - it is in the *Documentation* directory.

#. Push it to gerrit, or alternatively upload a patch with the changes to the
   `TYPO3 Flow JIRA issue tracker`_.

If you like to see a rendered HTML result of the documentation a few more steps are necessary:

#. Use any installed TYPO3 Flow distribution. If you don't have TYPO3 Flow installed, install the
   Base distribution as described in the *Installation* section of the *Getting Started* chapter.

#. Set up git for use with gerrit (`our git wizard`_ might help with that).

#. Install Sphinx to render the documentation.

#. Import and activate the package TYPO3.DocTools. It provides you with a command to render
   standalone documentation.

   .. code-block:: none

     $ ./flow documentation:render --format html

#. Read the documentation and adjust it as needed.

#. Push it to gerrit, or alternatively upload a patch with the changes to the
   `TYPO3 Flow JIRA issue tracker`_.

Work on the Code
================

You found a bug? Have an idea for a missing feature? Found clever solution to an
open task? Just write the code and submit it to us for inclusion. Do it on a
regular basis and become famous. So they say.

* *Tasks:* Write clean and useful code. Bonus points for beautiful code :-)
* *Skills needed:* good to expert PHP knowledge, good understanding for OOP,
  knowledge about patterns and "enterprise architecture" is a plus