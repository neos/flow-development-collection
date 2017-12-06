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

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Core\Bootstrap;
use Neos\Flow\Error\Debugger;
use Neos\Flow\Http\HttpRequestHandlerInterface;
use Neos\Flow\Log\ThrowableStorage\FileStorage;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;

/**
 * The logger factory used to create logger instances.
 *
 * @api
 * @Flow\Scope("singleton")
 */
class LoggerFactory
{
    /**
     * @var array
     */
    protected $logInstanceCache = [];

    /**
     * @var
     */
    protected $requestInfoCallback;

    /**
     * LoggerFactory constructor.
     */
    public function __construct()
    {
        $this->requestInfoCallback = function () {
            $output = '';
            if (!(Bootstrap::$staticObjectManager instanceof ObjectManagerInterface)) {
                return $output;
            }

            $bootstrap = Bootstrap::$staticObjectManager->get(Bootstrap::class);
            /* @var Bootstrap $bootstrap */
            $requestHandler = $bootstrap->getActiveRequestHandler();
            if (!$requestHandler instanceof HttpRequestHandlerInterface) {
                return $output;
            }

            $request = $requestHandler->getHttpRequest();
            $response = $requestHandler->getHttpResponse();
            $output .= PHP_EOL . 'HTTP REQUEST:' . PHP_EOL . ($request == '' ? '[request was empty]' : $request) . PHP_EOL;
            $output .= PHP_EOL . 'HTTP RESPONSE:' . PHP_EOL . ($response == '' ? '[response was empty]' : $response) . PHP_EOL;
            $output .= PHP_EOL . 'PHP PROCESS:' . PHP_EOL . 'Inode: ' . getmyinode() . PHP_EOL . 'PID: ' . getmypid() . PHP_EOL . 'UID: ' . getmyuid() . PHP_EOL . 'GID: ' . getmygid() . PHP_EOL . 'User: ' . get_current_user() . PHP_EOL;

            return $output;
        };
    }

    /**
     * Factory method which creates the specified logger along with the specified backend(s).
     *
     * @param string $identifier An identifier for the logger
     * @param string $loggerObjectName Object name of the log frontend
     * @param mixed $backendObjectNames Object name (or array of object names) of the log backend(s)
     * @param array $backendOptions (optional) Array of backend options. If more than one backend is specified, this is an array of array.
     * @return \Neos\Flow\Log\LoggerInterface The created logger frontend
     * @api
     */
    public function create($identifier, $loggerObjectName, $backendObjectNames, array $backendOptions = [])
    {
        if (!isset($this->logInstanceCache[$identifier])) {
            $this->logInstanceCache[$identifier] = $this->instantiateLogger($loggerObjectName, $backendObjectNames, $backendOptions);
        }

        return $this->logInstanceCache[$identifier];
    }

    /**
     * Create a new logger instance.
     *
     * @param $loggerObjectName
     * @param $backendObjectNames
     * @param array $backendOptions
     * @return mixed
     */
    protected function instantiateLogger($loggerObjectName, $backendObjectNames, array $backendOptions = [])
    {
        $logger = new $loggerObjectName;
        if (is_array($backendObjectNames)) {
            foreach ($backendObjectNames as $i => $backendObjectName) {
                if (isset($backendOptions[$i])) {
                    $backend = new $backendObjectName($backendOptions[$i]);
                    $logger->addBackend($backend);
                }
            }
        } else {
            $backend = new $backendObjectNames($backendOptions);
            $logger->addBackend($backend);
        }

        if ($logger instanceof Logger) {
            $logger->injectThrowableStorage($this->instantiateThrowableStorage());
        }

        return $logger;
    }

    /**
     * @return FileStorage|ThrowableStorageInterface|object
     */
    protected function instantiateThrowableStorage()
    {
        // Fallback early throwable storage
        $throwableStorage = new FileStorage();
        $throwableStorage->injectStoragePath(FLOW_PATH_DATA . 'Logs/Exceptions');
        if (Bootstrap::$staticObjectManager instanceof ObjectManagerInterface) {
            $throwableStorage = Bootstrap::$staticObjectManager->get(ThrowableStorageInterface::class);
        }
        $throwableStorage->setBacktraceRenderer(function ($backtrace) {
            return Debugger::getBacktraceCode($backtrace, false, true);
        });
        $throwableStorage->setRequestInformationRenderer($this->requestInfoCallback);
        return $throwableStorage;
    }
}
