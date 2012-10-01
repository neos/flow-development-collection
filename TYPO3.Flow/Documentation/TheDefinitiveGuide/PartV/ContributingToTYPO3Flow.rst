.. _ch-contributing:

=====================
Contributing to FLOW3
=====================

Got time, a computer and a brain? Here is how you can help:

Report and Validate Issues
==========================

We don't code bugs, at least not on purpose. But if you find one, report it in
our issue tracker. But please help us to solve it by attaching a detailed description
of how to reproduce the issue. If you can provide a unit test that shows the bug,
this rocks big time.

* *Tasks:* Find bugs, describe them, reproduce them in a unit test
* *Skills needed:* Attention to detail, knowledge about PHP and PHPUnit is a plus

**Report bugs in the** `FLOW3 Base Distribution issue tracker <http://forge.typo3.org/projects/flow3-distribution-base/issues>`_ **!**

Improve Documentation
=====================

A complex system like ours needs a lot of documentation. And despite the
complexity that documentation should be easy and fun to read. Right?

* *Tasks:* Proof read existing documentation, writing new documentation
* *Skills needed:* Writing skills and very good english are a must

For a quick start follow these simple steps:

#. Checkout the Package TYPO3.FLOW3 with Git, and set up the git repository so you can contribute:

   .. code-block:: none

     $ git clone git://git.typo3.org/FLOW3/Packages/TYPO3.FLOW3.git
     $ # set up git so you can push to Gerrit

   If you do not know how to set up Git correctly, follow the easy steps of
   `our git wizard <http://www.wwwision.de/githelper/#FLOW3/Packages/TYPO3.FLOW3.git>`_.
   Of course you can also use the package TYPO3.FLOW3 from any FLOW3 distribution you installed
   already.

#. Read the documentation and adjust it as needed - it is in the *Documentation* directory.

#. Push it to gerrit, or alternatively upload a patch with the changes to the
   `FLOW3 issue tracker <http://forge.typo3.org/projects/package-typo3-flow3/issues>`_.

If you like to see a rendered HTML result of the documentation a few more steps are necessary:

#. Use any installed FLOW3 distribution. If you don't have FLOW3 installed, install the FLOW3
   Base distribution as described in the *Installation* section of the *Getting Started* chapter.

#. Set up git for use with gerrit (`our git wizard`_ might help with that).

#. Install Sphinx to render the documentation.

#. Import and activate the package TYPO3.DocTools. It provides you with a command to render
   standalone documentation.

   .. code-block:: none

     $ ./flow3 documentation:render --format html

#. Read the documentation and adjust it as needed.

#. Push it to gerrit, or alternatively upload a patch with the changes to the
   `FLOW3 issue tracker`_.

Work on the Code
================

You found a bug? Have an idea for a missing feature? Found clever solution to an
open task? Just write the code and submit it to us for inclusion. Do it on a
regular basis and become famous. So they say.

* *Tasks:* Write clean and useful code. Bonus points for beautiful code :-)
* *Skills needed:* good to expert PHP knowledge, good understanding for OOP,
  knowledge about patterns and "enterprise architecture" is a plus