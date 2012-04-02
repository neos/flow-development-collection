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

**Report bugs in the** `Forge Issue Tracker <http://forge.typo3.org/projects/flow3-distribution-base/issues>`_ **!**

Improve Documentation
=====================

A complex system like ours needs a lot of documentation. And despite the
complexity that documentation should be easy and fun to read. Right?

* *Tasks:* Proof read existing documentation, writing new documentation
* *Skills needed:* Writing skills and very good english are a must

Follow the these simple steps to get started:

#. Checkout our documentation with Git, and set up the git repository so you can contribute:

	.. code-block:: bash

		$ git clone git://git.typo3.org/FLOW3/Documentation.git
		$ # set up git so you can push to Gerrit

	If you do not know how to set up Git correctly, follow the easy steps of
	`our git wizard <http://www.wwwision.de/githelper/#FLOW3/Documentation.git>`_.

#. Install Sphinx to render the documentation. For that, follow the instructions in
   the `readme file <http://git.typo3.org/FLOW3/Documentation.git?a=blob;f=README.txt>`_.

#. Read the documentation and adjust it as needed.

#. push it to gerrit, or alternatively upload a patch with the changes to the
   `Forge Issue Tracker <http://forge.typo3.org/projects/flow3-distribution-base/issues>`_

Work on the Code
================

You found a bug? Have an idea for a missing feature? Found clever solution to an
open task? Just write the code and submit it to us for inclusion. Do it on a
regular basis and become famous. So they say.

* *Tasks:* Write clean and useful code. Bonus points for beautiful code :-)
* *Skills needed:* good to expert PHP knowledge, good understanding for OOP,
  knowledge about patterns and "enterprise architecture" is a plus
