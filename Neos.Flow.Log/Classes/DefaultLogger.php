<?php
namespace Neos\Flow\Log;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Log\Exception\NoSuchBackendException;
use Psr\Log\LogLevel;

/**
 * This is the deprecated Logger implementing the old Neos.Flow LoggerInterface.
 * it is replaced
 *
 * @deprecated This should be replaced with usages of the Psr\Logger
 * @see \Neos\Flow\Log\Psr\Logger
 */
class DefaultLogger implements LoggerInterface
{
    const LOGLEVEL_MAPPING = [
        LOG_EMERG => LogLevel::EMERGENCY,
        LOG_DEBUG => LogLevel::DEBUG,
        LOG_INFO => LogLevel::INFO,
        LOG_NOTICE => LogLevel::NOTICE,
        LOG_WARNING => LogLevel::WARNING,
        LOG_ERR => LogLevel::ERROR,
        LOG_CRIT => LogLevel::CRITICAL,
        LOG_ALERT => LogLevel::ALERT
    ];

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $internalPsrCompatibleLogger;

    /**
     * @var bool
     */
    protected $psrCompatibleLoggerWasInjected = false;

    /**
     * @var \SplObjectStorage
     */
    protected $backends;

    /**
     * @var \closure
     */
    protected $requestInfoCallback = null;

    /**
     * @var \closure
     */
    protected $renderBacktraceCallback = null;

    /**
     * Constructs the logger
     *
     * @param \Psr\Log\LoggerInterface $logger Should be a fully configured PSR logger instance that then will take over logging.
     */
    public function __construct(\Psr\Log\LoggerInterface $logger = null)
    {
        $this->internalPsrCompatibleLogger = $logger;
        if ($this->internalPsrCompatibleLogger !== null) {
            $this->psrCompatibleLoggerWasInjected = true;
        }
        $this->backends = new \SplObjectStorage();
    }

    /**
     * @param \Closure $closure
     */
    public function setRequestInfoCallback(\Closure $closure)
    {
        $this->requestInfoCallback = $closure;
    }

    /**
     * @param \Closure $closure
     */
    public function setRenderBacktraceCallback(\Closure $closure)
    {
        $this->renderBacktraceCallback = $closure;
    }

    /**
     * Sets the given backend as the only backend for this Logger.
     *
     * This method allows for conveniently injecting a backend through some Objects.yaml configuration.
     *
     * @param Backend\BackendInterface $backend A backend implementation
     * @return void
     * @api
     * @throws Exception
     */
    public function setBackend(Backend\BackendInterface $backend)
    {
        if ($this->psrCompatibleLoggerWasInjected) {
            throw new Exception('A PSR-3 logger was injected so setting backends is not possible. Create a new instance.', 1515342951935);
        }

        foreach ($this->backends as $backend) {
            $backend->close();
        }
        $this->backends = new \SplObjectStorage();
        $this->backends->attach($backend);
    }

    /**
     * Adds the backend to which the logger sends the logging data
     *
     * @param Backend\BackendInterface $backend A backend implementation
     * @return void
     * @api
     * @throws Exception
     */
    public function addBackend(Backend\BackendInterface $backend)
    {
        if ($this->psrCompatibleLoggerWasInjected) {
            throw new Exception('A PSR-3 logger was injected so adding backends is not possible. Create a new instance.', 1515343013004);
        }

        $this->backends->attach($backend);
    }

    /**
     * Runs the close() method of a backend and removes the backend
     * from the logger.
     *
     * @param Backend\BackendInterface $backend The backend to remove
     * @return void
     * @throws NoSuchBackendException if the given backend is unknown to this logger
     * @api
     * @throws Exception
     */
    public function removeBackend(Backend\BackendInterface $backend)
    {
        if ($this->psrCompatibleLoggerWasInjected) {
            throw new Exception('A PSR-3 logger was injected so removing backends is not possible. Create a new instance.', 1515343007859);
        }

        if (!$this->backends->contains($backend)) {
            throw new NoSuchBackendException('Backend is unknown to this logger.', 1229430381);
        }
        $backend->close();
        $this->backends->detach($backend);
        // This needs to be reset in order to re-create a new PSR compatible logger with the remaining backends attached.
        $this->internalPsrCompatibleLogger = null;
    }

    /**
     * Writes the given message along with the additional information into the log.
     *
     * @param string $message The message to log
     * @param integer $severity An integer value, one of the LOG_* constants
     * @param mixed $additionalData A variable containing more information about the event to be logged
     * @param string $packageKey Key of the package triggering the log (determined automatically if not specified)
     * @param string $className Name of the class triggering the log (determined automatically if not specified)
     * @param string $methodName Name of the method triggering the log (determined automatically if not specified)
     * @return void
     * @api
     */
    public function log($message, $severity = LOG_INFO, $additionalData = null, $packageKey = null, $className = null, $methodName = null)
    {
        if ($this->internalPsrCompatibleLogger === null) {
            $this->internalPsrCompatibleLogger = $this->createDefaultPsrLogger();
        }

        $psrLogLevel = self::LOGLEVEL_MAPPING[$severity] ?? LOG_INFO;

        $context = [];
        if ($additionalData !== null) {
            // In PSR-3 context must always be an array, therefore we create an outer array if different type.
            $context = is_array($additionalData) ? $additionalData : ['additionalData' => $additionalData];
        }

        if ($packageKey !== null || $className  !== null || $methodName !== null) {
            $context['FLOW_LOG_ENVIRONMENT'] = $this->createFlowLogEnvironment($packageKey, $className, $methodName);
        }

        $this->internalPsrCompatibleLogger->log($psrLogLevel, $message, $context);
    }

    /**
     * @param \Exception $exception
     * @param array $additionalData
     */
    public function logException(\Exception $exception, array $additionalData = [])
    {
        $backtrace = debug_backtrace(false);
        $className = isset($backtrace[1]['class']) ? $backtrace[1]['class'] : null;
        $methodName = isset($backtrace[1]['function']) ? $backtrace[1]['function'] : null;
        foreach ($this->backends as $backend) {
            $backend->append($exception->getMessage(), LOG_ERR, $additionalData, '', $className, $methodName);
        }
    }

    /**
     * Create a default PSR logger if none was injected.
     * This is just for backwards compatibility.
     */
    protected function createDefaultPsrLogger()
    {
        return new \Neos\Flow\Log\Psr\Logger($this->backends);
    }

    /**
     * @param string $packageKey
     * @param string $className
     * @param string $methodName
     * @return array
     */
    protected function createFlowLogEnvironment($packageKey = null, $className = null, $methodName =  null): array
    {
        $logEnvironment = [];
        if ($packageKey !== null) {
            $logEnvironment['packageKey'] = $packageKey;
        }

        if ($className !== null) {
            $logEnvironment['className'] = $className;
        }

        if ($methodName !== null) {
            $logEnvironment['methodName'] = $methodName;
        }

        return $logEnvironment;
    }

    /**
     * Cleanly closes all registered backends before destructing this Logger
     *
     * @return void
     */
    public function shutdownObject()
    {
        $this->internalPsrCompatibleLogger = null;
        foreach ($this->backends as $backend) {
            $backend->close();
        }
    }
}
