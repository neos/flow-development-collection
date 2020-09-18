.. _logging:

=======
Logging
=======

.. sectionauthor:: Alexander Berl <alexander@neos.io>

In Flow logging is implemented according to the `PSR-3 Standard`_. This means you can use any logging facility that implements this interface.
The concrete implementation can easily be configured with the :doc:`Object Management` of Flow.

Default loggers
===============

By default Flow comes with two basic loggers, the so called "system logger" and "security logger".
As the name implies, the former is responsible for logging general system level messages and the latter for
logging security related information. Under the hood they use a ``FileBackend`` for storing the messages, but
that can be configured differently via the settings.

You can use these two loggers for example if you extend the system via signals or AOP, or you are debugging
your code. For that you inject a ``Psr\LoggerInterface`` wherever you need it like this::

	/**
	 * @Flow\Inject(name="Neos.Flow:SystemLogger")
	 * @var LoggerInterface
	 */
	protected $systemLogger;
	
	/**
	 * @Flow\Inject(name="Neos.Flow:SecurityLogger")
	 * @var LoggerInterface
	 */
	protected $securityLogger;

This is achieved via the :ref:`virtual objects configuration <sect-virtual-objects>` that allows to configure a single class in multiple
versions with different constructor arguments and assign a name for this configuration, which can be referenced in the ``@Flow\Inject`` annotation.

Exception logging
=================

One of the primary use case for logging is exceptions that need to be stored for later inspection. You might want to just append
the exception message (and maybe the stack trace) as a message to your logger instance. However, this is not recommended for multiple reasons.
Most notably the exception stack trace is very verbose and clutters the log and you might also want to log further information about the environment
in which the exception happened, like the HTTP request. But alone from a conceptual perspective, storing an exception is slightly
different from logging information about what your application is doing. A log message is a text, while an exception trace is basically a document.
That's why Flow provides a so called ``ThrowableStorageInterface`` that you can implement and use for storing exception messages side by side to your logs.
The most important method of this interface is the ``logThrowable()`` method, which takes a ``Throwable`` class and an array of additional data and is
supposed to return a string message that should end up in your primary logger. This message should then refer to the location where the exception is stored,
so you can find it later on.

By default, Flow comes with a ``FileStorage`` implementation, which will write the exception to the file system with a unique name and information about
the request and PHP process.
You might already have stumbled across such an exception (though hopefully not!) inside the ``Data/Logs/Exceptions`` directory of your Flow installation::

  Exception: Argument 1 passed to Neos\Flow\Http\Middleware\MiddlewaresChain_Original::__construct() must be of the type string, array given

  10 Neos\Flow\Http\Middleware\MiddlewaresChain_Original::__construct(array|0|, array|3|)
  9 call_user_func_array("parent::__construct", array|2|)
  8 Neos\Flow\Http\Middleware\MiddlewaresChain::__construct(array|0|, array|3|)
  7 Neos\Flow\Http\Middleware\MiddlewaresChainFactory_Original::create(array|3|, array|0|)
  6 call_user_func_array(array|2|, array|2|)
  5 Neos\Flow\ObjectManagement\ObjectManager::buildObjectByFactory("Neos\Flow\Http\Middleware\MiddlewaresChain")
  4 Neos\Flow\ObjectManagement\ObjectManager::get("Neos\Flow\Http\Middleware\MiddlewaresChain")
  3 Neos\Flow\Http\RequestHandler::resolveDependencies()
  2 Neos\Flow\Http\RequestHandler::handleRequest()
  1 Neos\Flow\Core\Bootstrap::run()


  HTTP REQUEST:
  127.0.0.1:8081keep-aliveno-cacheno-cacheimageMozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/80.0.3987.132 Safari/537.361image/webp,image/apng,image/*,*/*;q=0.8same-originno-corshttp://127.0.0.1:8081/flow/welcomegzip, deflate, brde-DE,de;q=0.9,en-US;q=0.8,en;q=0.7

  HTTP RESPONSE:
  200

  PHP PROCESS:
  Inode: 
  PID: 2296
  UID: 1
  GID: 1
  User: 


.. _PSR-3 Standard: https://www.php-fig.org/psr/psr-3/
