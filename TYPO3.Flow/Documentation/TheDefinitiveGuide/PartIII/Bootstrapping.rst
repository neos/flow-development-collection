.. _ch-bootstrapping:

=============
Bootstrapping
=============

.. sectionauthor:: Robert Lemke <robert@typo3.org>

This chapter outlines the bootstrapping mechanism FLOW3 uses on each request
to initialize vital parts of the framework and the application. It explains
the built-in request handlers which effectively control the boot sequence and
demonstrates how custom request handlers can be developed and registered.

Boot Sequence
=============

There are basically two types of requests which are handled by a FLOW3
application: command line and HTTP requests:

* *command line* requests are passed to the ``flow3.php`` script which
  resides in the ``Scripts`` folder of the FLOW3 package
* *HTTP requests* are first taken care of by the ``index.php`` script
  residing in the public ``Web`` directory.

Both scripts set certain environment variables and then instantiate and run the
``TYPO3\FLOW3\Core\Bootstrap`` class.

The bootstrap's ``run()`` method initializes the bare minimum needed for any
kind of operation. When it did that, it determines the actual request
handler which takes over the control of the further boot sequence and
handling the request. ::

	public function run() {
		Scripts::initializeClassLoader($this);
		Scripts::initializeSignalSlot($this);
		Scripts::initializePackageManagement($this);

		$this->activeRequestHandler = $this->resolveRequestHandler();
		$this->activeRequestHandler->handleRequest();
	}

The request handler in charge executes a sequence of steps which need to be
taken for initializing FLOW3 for the purpose defined by the specialized
request handler. FLOW3's ``Bootstrap`` class provides convenience methods for
building such a sequence and the result can be customized by adding further
or removing unnecessary steps.

After initialization, the request handler takes the necessary steps to handle
the request, does or does not echo a response and finally exits the
application. Control is not returned to the bootstrap again, but a request
handler should call the bootstrap's ``shutdown()`` method in order to cleanly
shut down important parts of the framework.

Run Levels
==========

There are two pre-defined levels to which FLOW3 can be initialized:

* *compiletime* brings FLOW3 into a state which allows for code generation
  and other low-level tasks which can only be done while FLOW3 is not yet
  fully ready for serving user requests. Compile time has only limited support
  for Dependency Injection and lacks support for many other functions such as
  Aspect-Oriented Programming and Security.

* *runtime* brings FLOW3 into a state which is fully capable of handling user
  requests and is optimized for speed. No changes to any of the code caches
  or configuration related to code is allowed during runtime.

The bootstrap's methods ``buildCompiletimeSequence()`` and
``buildRuntimeSequence()`` conveniently build a sequence which brings FLOW3
into either state on invocation.

Request Handlers
================

A request handler is in charge of executing the boot sequence and ultimately
answering the request it was designed for. It must implement the
``\TYPO3\FLOW3\Core\RequestHandlerInterface`` interface which,
among others, contains the following methods: ::

	public function handleRequest();

	public function canHandleRequest();

	public function getPriority();

On trying to find a suitable request handler, the bootstrap asks each
registered request handler if it can handle the current request
– ``canHandleRequest()`` – and if it can,
how eager it is to do so – ``getPriority()``. It then passes control to the
request handler which is most capable of responding to the request
– ``handleRequest()``.

Request handlers must first be registered in order to be considered during the
resolving phase. Registration is done in the ``Package`` class of the package
containing the request handler: ::

	class Package extends BasePackage {

		public function boot(\TYPO3\FLOW3\Core\Bootstrap $bootstrap) {
			$bootstrap->registerRequestHandler(new \Acme\Foo\BarRequestHandler($bootstrap));
		}

	}

.. tip::

	The FLOW3 package contains meaningful working examples for registration of
	request handlers and building boot sequences. A good starting point is
	the ``\TYPO3\FLOW3\Package`` class where the request handlers are
	registered.
