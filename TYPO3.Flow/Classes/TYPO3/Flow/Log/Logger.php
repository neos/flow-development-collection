<?php
namespace TYPO3\Flow\Log;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */
use TYPO3\Flow\Core\Bootstrap;
use TYPO3\Flow\Error\Debugger;
use TYPO3\Flow\Exception;
use TYPO3\Flow\Http\HttpRequestHandlerInterface;
use TYPO3\Flow\Log\Exception\NoSuchBackendException;
use TYPO3\Flow\Object\ObjectManagerInterface;

/**
 * The default logger of the Flow framework
 *
 * @api
 */
class Logger implements SystemLoggerInterface, SecurityLoggerInterface
{
    /**
     * @var \SplObjectStorage
     */
    protected $backends;

    /**
     * Constructs the logger
     *
     */
    public function __construct()
    {
        $this->backends = new \SplObjectStorage();
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
     * Writes information about the given exception into the log.
     *
     * @param \Exception $exception The exception to log
     * @param array $additionalData Additional data to log
     * @return void
     * @api
     */
    public function logException(\Exception $exception, array $additionalData = array())
    {
        $backTrace = $exception->getTrace();
        $className = isset($backTrace[0]['class']) ? $backTrace[0]['class'] : '?';
        $methodName = isset($backTrace[0]['function']) ? $backTrace[0]['function'] : '?';
        $message = $this->getExceptionLogMessage($exception);

        if ($exception->getPrevious() !== null) {
            $additionalData['previousException'] = $this->getExceptionLogMessage($exception->getPrevious());
        }

        $explodedClassName = explode('\\', $className);
        // FIXME: This is not really the package key:
        $packageKey = (isset($explodedClassName[1])) ? $explodedClassName[1] : null;

        if (!file_exists(FLOW_PATH_DATA . 'Logs/Exceptions')) {
            mkdir(FLOW_PATH_DATA . 'Logs/Exceptions');
        }
        if (file_exists(FLOW_PATH_DATA . 'Logs/Exceptions') && is_dir(FLOW_PATH_DATA . 'Logs/Exceptions') && is_writable(FLOW_PATH_DATA . 'Logs/Exceptions')) {
            $referenceCode = ($exception instanceof Exception) ? $exception->getReferenceCode() : date('YmdHis', $_SERVER['REQUEST_TIME']) . substr(md5(rand()), 0, 6);
            $exceptionDumpPathAndFilename = FLOW_PATH_DATA . 'Logs/Exceptions/' . $referenceCode . '.txt';
            file_put_contents($exceptionDumpPathAndFilename, $this->renderExceptionInfo($exception));
            $message .= ' - See also: ' . basename($exceptionDumpPathAndFilename);
        } else {
            $this->log(sprintf('Could not write exception backtrace into %s because the directory could not be created or is not writable.', FLOW_PATH_DATA . 'Logs/Exceptions/'), LOG_WARNING, array(), 'Flow', __CLASS__, __FUNCTION__);
        }

        $this->log($message, LOG_CRIT, $additionalData, $packageKey, $className, $methodName);
    }

    /**
     * Get current exception post mortem informations with support for exception chaining
     *
     * @param \Exception $exception
     * @return string
     */
    protected function renderExceptionInfo(\Exception $exception)
    {
        $maximumDepth = 100;
        $backTrace = $exception->getTrace();
        $message = $this->getExceptionLogMessage($exception);
        $postMortemInfo = $this->renderBacktrace($message, $backTrace);
        $depth = 0;
        while ($exception->getPrevious() instanceof \Exception && $depth < $maximumDepth) {
            $exception = $exception->getPrevious();
            $message = 'Previous exception: ' . $this->getExceptionLogMessage($exception);
            $backTrace = $exception->getTrace();
            $postMortemInfo .= PHP_EOL . $this->renderBacktrace($message, $backTrace);
            ++$depth;
        }

        $postMortemInfo .= $this->renderRequestInfo();

        if ($depth === $maximumDepth) {
            $postMortemInfo .= PHP_EOL . 'Maximum chainging depth reached ...';
        }

        return $postMortemInfo;
    }

    /**
     * @param \Exception $exception
     * @return string
     */
    protected function getExceptionLogMessage(\Exception $exception)
    {
        $exceptionCodeNumber = ($exception->getCode() > 0) ? ' #' . $exception->getCode() : '';
        $backTrace = $exception->getTrace();
        $line = isset($backTrace[0]['line']) ? ' in line ' . $backTrace[0]['line'] . ' of ' . $backTrace[0]['file'] : '';
        return 'Exception' . $exceptionCodeNumber . $line . ': ' . $exception->getMessage();
    }

    /**
     * Renders background information about the circumstances of the exception.
     *
     * @param string $message
     * @param array $backTrace
     * @return string
     */
    protected function renderBacktrace($message, $backTrace)
    {
        return $message . PHP_EOL . PHP_EOL . Debugger::getBacktraceCode($backTrace, false, true);
    }

    /**
     * Render information about the current request, if possible
     *
     * @return string
     */
    protected function renderRequestInfo()
    {
        $output = '';
        if (Bootstrap::$staticObjectManager instanceof ObjectManagerInterface) {
            $bootstrap = Bootstrap::$staticObjectManager->get(\TYPO3\Flow\Core\Bootstrap::class);
            /* @var Bootstrap $bootstrap */
            $requestHandler = $bootstrap->getActiveRequestHandler();
            if ($requestHandler instanceof HttpRequestHandlerInterface) {
                $request = $requestHandler->getHttpRequest();
                $response = $requestHandler->getHttpResponse();
                $output .= PHP_EOL . 'HTTP REQUEST:' . PHP_EOL . ($request == '' ? '[request was empty]' : $request) . PHP_EOL;
                $output .= PHP_EOL . 'HTTP RESPONSE:' . PHP_EOL . ($response == '' ? '[response was empty]' : $response) . PHP_EOL;
            }
        }

        return $output;
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
