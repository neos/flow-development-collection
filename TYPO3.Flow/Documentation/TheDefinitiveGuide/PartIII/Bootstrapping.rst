.. _ch-bootstrapping:

=============
Bootstrapping
=============

.. sectionauthor:: Robert Lemke <robert@typo3.org>

This chapter outlines the bootstrapping mechanism TYPO3 Flow uses on each request
to initialize vital parts of the framework and the application. It explains
the built-in request handlers which effectively control the boot sequence and
demonstrates how custom request handlers can be developed and registered.

The TYPO3 Flow Application Context
==================================

Each request, no matter if it runs from the command line or through HTTP,
runs in a specific *application context*. TYPO3 Flow provides exactly three built-in
contexts:

* ``Development`` (default) - used for development
* ``Production`` - should be used for a live site
* ``Testing`` - is used for functional tests

The context TYPO3 Flow runs in is specified through the environment variable
``FLOW_CONTEXT``. It can be set per command at the command line or be part of the
web server configuration::

	# run the TYPO3 Flow CLI commands in production context
	FLOW_CONTEXT=Production ./flow help

	# In your Apache configuration, you usually use:
	SetEnv FLOW_CONTEXT Production

Custom Contexts
---------------

In certain situations, more specific contexts are desirable:

* a staging system may run in a Production context, but requires a different set of
  credentials than the production server.
* developers working on a project may need different application specific settings
  but prefer to maintain all configuration files in a common Git repository.

By defining custom contexts which inherit from one of the three base contexts,
more specific configuration sets can be realized.

While it is not possible to add new "top-level" contexts at the same level like
*Production* and *Testing*, you can create arbitrary *sub-contexts*, just by
specifying them like ``<MainContext>/<SubContext>``.

For a staging environment a custom context ``Production/Staging`` may provide the
necessary settings while the ``Production/Live`` context is used on the live instance.

Each sub context inherits the configuration from the parent context, which is
explained in full detail inside the *Configuration* chapter.

.. note:: This even works recursively, so if you have a multiple-server staging
          setup, you could use the context Production/Staging/Server1 and
          Production/Staging/Server2 if both staging servers needed different
          configuration.

Boot Sequence
=============

There are basically two types of requests which are handled by a TYPO3 Flow
application:

* *command line* requests are passed to the ``flow.php`` script which
  resides in the ``Scripts`` folder of the TYPO3 Flow package
* *HTTP requests* are first taken care of by the ``index.php`` script
  in the public ``Web`` directory.

Both scripts set certain environment variables and then instantiate and run the
``TYPO3\Flow\Core\Bootstrap`` class.

The bootstrap's ``run()`` method initializes the bare minimum needed for any
kind of operation. When it did that, it determines the actual request
handler which takes over the control of the further boot sequence and
handling the request.

::

	public function run() {
		Scripts::initializeClassLoader($this);
		Scripts::initializeSignalSlot($this);
		Scripts::initializePackageManagement($this);

		$this->activeRequestHandler = $this->resolveRequestHandler();
		$this->activeRequestHandler->handleRequest();
	}

The request handler in charge executes a sequence of steps which need to be
taken for initializing TYPO3 Flow for the purpose defined by the specialized
request handler. TYPO3 Flow's ``Bootstrap`` class provides convenience methods for
building such a sequence and the result can be customized by adding further
or removing unnecessary steps.

After initialization, the request handler takes the necessary steps to handle
the request, does or does not echo a response and finally exits the
application. Control is not returned to the bootstrap again, but a request
handler should call the bootstrap's ``shutdown()`` method in order to cleanly
shut down important parts of the framework.

Run Levels
==========

There are two pre-defined levels to which TYPO3 Flow can be initialized:

* *compiletime* brings TYPO3 Flow into a state which allows for code generation
  and other low-level tasks which can only be done while TYPO3 Flow is not yet
  fully ready for serving user requests. Compile time has only limited support
  for Dependency Injection and lacks support for many other functions such as
  Aspect-Oriented Programming and Security.

* *runtime* brings TYPO3 Flow into a state which is fully capable of handling user
  requests and is optimized for speed. No changes to any of the code caches
  or configuration related to code is allowed during runtime.

The bootstrap's methods ``buildCompiletimeSequence()`` and
``buildRuntimeSequence()`` conveniently build a sequence which brings TYPO3 Flow
into either state on invocation.

Request Handlers
================

A request handler is in charge of executing the boot sequence and ultimately
answering the request it was designed for. It must implement the
``\TYPO3\Flow\Core\RequestHandlerInterface`` interface which,
among others, contains the following methods::

	public function handleRequest();

	public function canHandleRequest();

	public function getPriority();

On trying to find a suitable request handler, the bootstrap asks each
registered request handler if it can handle the current request
using ``canHandleRequest()`` – and if it can,
how eager it is to do so through ``getPriority()``. It then passes control to the
request handler which is most capable of responding to the request by
calling ``handleRequest()``.

Request handlers must first be registered in order to be considered during the
resolving phase. Registration is done in the ``Package`` class of the package
containing the request handler::

	class Package extends BasePackage {

		public function boot(\TYPO3\Flow\Core\Bootstrap $bootstrap) {
			$bootstrap->registerRequestHandler(new \Acme\Foo\BarRequestHandler($bootstrap));
		}

	}

.. tip::

	The TYPO3 Flow package contains meaningful working examples for registration of
	request handlers and building boot sequences. A good starting point is
	the ``\TYPO3\Flow\Package`` class where the request handlers are
	registered.
