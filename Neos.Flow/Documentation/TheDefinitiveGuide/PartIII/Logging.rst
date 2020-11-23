.. _logging:

=======
Logging
=======

.. sectionauthor:: Alexander Berl <alexander@neos.io>

In Flow logging is implemented according to the `PSR-3 Standard`_. This means you can use any logging facility that implements this interface.
The concrete implementation can easily be configured with the :doc:`Object Management` of Flow. By default, Flow comes with an PSR-3 implementation
in the ``Neos.Flow.Log`` package that can be configured with one or more storage backends and supports log rotation. The supported backends currently
consist of a ``FileBackend``, ``(Ansi)ConsoleBackend``, ``JsonFileBackend`` and a ``NullBackend``.

Default loggers
===============

By default Flow comes with four loggers, the so called "system logger", "security logger", "sql logger" and "i18n logger".
As the names imply, the first is responsible for logging general system level messages and is used by default if an instance of the ``LoggerInterface`` is injected. The second for
logging security related information. The SQL logger needs to be enabled first via ``Neos.Flow.persistence.doctrine.sqlLogger`` setting and will
create a log of all database queries, so this can become a big performance penalty and should only be used for debugging purposes.
Last but not least is the i18n logger which will log away all messages related to the translation framework, for example when the XLIFF translation
sources are badly formatted.
Under the hood they all use a ``FileBackend`` for storing the messages, but that can be configured differently via the settings keys ``Neos.Flow.log.psr3.Neos\Flow\Log\PsrLoggerFactory.*``.

Loggers can be used to record events that happen in an application, for example a failed login attempt or a caught exception.
To make use of a logger, the ``Psr\Log\LoggerInterface`` can be injected, that refers to the ``SystemLogger`` which – by default – persists any log message in the file system::

	use Psr\Log\LoggerInterface;
  ...
  
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

	/**
	 * @Flow\Inject(name="Neos.Flow:SqlLogger")
	 * @var LoggerInterface
	 */
	protected $sqlLogger;

	/**
	 * @Flow\Inject(name="Neos.Flow:I18nLogger")
	 * @var LoggerInterface
	 */
	protected $i18nLogger;

This is achieved via the :ref:`virtual objects configuration <sect-virtual-objects>` that allows to configure a single class in multiple
versions with different constructor arguments and assign a name for this configuration, which can be referenced in the ``@Flow\Inject`` annotation.

If you just need a default logger and don't really care for the specific type of logger, you can also skip the ``(name="Neos.Flow:...")`` part and you will
receive an instance of the ``SystemLogger`` by default::

	/**
	 * @Flow\Inject
	 * @var LoggerInterface
	 */
	protected $logger;

Alternatively, if you prefer to keep your class free of framework specific annotations (``@Flow\Inject(...)``), you could as well just inject the specific
configuration of the logger via the ``Objects.yaml`` like this::

	Acme\Your\Class:
	  properties:
	    systemLogger:
	      object:
	        name: 'Neos.Flow:SystemLogger'

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

.. _throwable-storage:

In order to log such exceptions yourself you have to inject both a ``ThrowableStorageInterface`` as well as a ``LoggerInterface`` at a place where you can reach them
from your ``try/catch`` block. This would roughly look as follows::

	use Neos\Flow\Log\ThrowableStorageInterface;
	use Psr\Log\LoggerInterface;

	...

	/**
	 * @Flow\Inject
	 * @var ThrowableStorageInterface
	 */
	protected $throwableStorage;

	/**
	 * @Flow\Inject
	 * @var LoggerInterface
	 */
	protected $logger;

	...

	public function trySomething()
	{
		try {
			...
		} catch (\Throwable $exception) {
			$logMessage = $this->throwableStorage->logThrowable($exception);
			$this->logger->error($logMessage, LogEnvironment::fromMethodName(__METHOD__));
		}
	}

The ``LogEnvironment::fromMethodName(__METHOD__)`` is a helper that builds an additional data array for the log in the structure of::

	[
			'FLOW_LOG_ENVIRONMENT' => [
					'packageKey' => PackageKeyFromClassName($className),
					'className' => $className,
					'methodName' => $functionName
			]
	]

This is used so the log contains helpful information about where the log is coming from. It derives the package key from the namespace
of the method (``__METHOD__``) the log is called from. Of course you can freely customize the additional context and everything in the
array will be serialized and formatted into your log with the backends provided through the ``Neos.Flow.Log`` package. Just don't use
the ``FLOW_LOG_ENVIRONMENT`` key, as that is used internally and only accepts the three keys above.

.. _PSR-3 Standard: https://www.php-fig.org/psr/psr-3/
