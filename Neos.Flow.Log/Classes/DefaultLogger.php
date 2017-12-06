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

/**
 * The default logger of the Flow framework
 *
 * @api
 */
class DefaultLogger implements LoggerInterface
{
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
     */
    public function __construct()
    {
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
     */
    public function setBackend(Backend\BackendInterface $backend)
    {
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
     */
    public function addBackend(Backend\BackendInterface $backend)
    {
        $this->backends->attach($backend);
        $backend->open();
    }

    /**
     * Runs the close() method of a backend and removes the backend
     * from the logger.
     *
     * @param Backend\BackendInterface $backend The backend to remove
     * @return void
     * @throws NoSuchBackendException if the given backend is unknown to this logger
     * @api
     */
    public function removeBackend(Backend\BackendInterface $backend)
    {
        if (!$this->backends->contains($backend)) {
            throw new NoSuchBackendException('Backend is unknown to this logger.', 1229430381);
        }
        $backend->close();
        $this->backends->detach($backend);
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
        if ($packageKey === null) {
            $backtrace = debug_backtrace(false);
            $className = isset($backtrace[1]['class']) ? $backtrace[1]['class'] : null;
            $methodName = isset($backtrace[1]['function']) ? $backtrace[1]['function'] : null;
            $explodedClassName = explode('\\', $className);
            // FIXME: This is not really the package key:
            $packageKey = isset($explodedClassName[1]) ? $explodedClassName[1] : '';
        }
        foreach ($this->backends as $backend) {
            $backend->append($message, $severity, $additionalData, $packageKey, $className, $methodName);
        }
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
     * Cleanly closes all registered backends before destructing this Logger
     *
     * @return void
     */
    public function shutdownObject()
    {
        foreach ($this->backends as $backend) {
            $backend->close();
        }
    }
}
