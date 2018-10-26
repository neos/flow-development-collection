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
 * @Flow\Autowiring(false)
 */
class LoggerFactory
{
    /**
     * @var PsrLoggerFactoryInterface
     */
    protected $psrLoggerFactory;

    /**
     * @var ThrowableStorageInterface
     */
    protected $throwableStorage;

    /**
     * @var array
     */
    protected $logInstanceCache = [];

    /**
     * @var \Closure
     */
    protected $requestInfoCallback;

    /**
     * LoggerFactory constructor.
     *
     * @param PsrLoggerFactoryInterface $psrLoggerFactory
     * @param ThrowableStorageInterface $throwableStorage
     */
    public function __construct(PsrLoggerFactoryInterface $psrLoggerFactory, ThrowableStorageInterface $throwableStorage)
    {
        $this->psrLoggerFactory = $psrLoggerFactory;
        $this->throwableStorage = $throwableStorage;
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
            if (is_a($loggerObjectName, DefaultLogger::class, true)) {
                $logger = $this->instantiateLogger($loggerObjectName, $backendObjectNames, $backendOptions);
            } else {
                $logger = $this->createPsrBasedLogger($identifier, $loggerObjectName);
            }

            $this->logInstanceCache[$identifier] = $logger;
        }

        return $this->logInstanceCache[$identifier];
    }

    /**
     * @param string $identifier
     * @param string $loggerObjectName
     * @return LoggerInterface
     */
    protected function createPsrBasedLogger(string $identifier, string $loggerObjectName): \Psr\Log\LoggerInterface
    {
        $psrLogger = $this->psrLoggerFactory->get($identifier);
        $logger = $loggerObjectName($psrLogger);
        $logger = $this->injectAdditionalDependencies($logger);
        return $logger;
    }

    /**
     * Create a new logger instance.
     *
     * @param string $loggerObjectName
     * @param array|string $backendObjectNames
     * @param array $backendOptions
     * @return \Neos\Flow\Log\LoggerInterface
     */
    protected function instantiateLogger(string $loggerObjectName, $backendObjectNames, array $backendOptions = []): LoggerInterface
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

        $logger = $this->injectAdditionalDependencies($logger);
        return $logger;
    }

    /**
     * @param LoggerInterface $logger
     * @return LoggerInterface
     */
    protected function injectAdditionalDependencies(LoggerInterface $logger): LoggerInterface
    {
        if ($logger instanceof Logger) {
            $logger->injectThrowableStorage($this->throwableStorage);
        }

        return $logger;
    }
}
