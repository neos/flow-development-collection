<?php
namespace Neos\Flow\Log\Psr;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Log\Backend\BackendInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LoggerTrait;
use Psr\Log\LogLevel;

/**
 * PSR-3 supporting logger.
 * This is the primary Logger implementation of the Neos.Flow.Log package.
 *
 * @api
 */
class Logger implements LoggerInterface
{
    use LoggerTrait;

    const LOGLEVEL_MAPPING = [
        LogLevel::EMERGENCY => LOG_EMERG,
        LogLevel::DEBUG => LOG_DEBUG,
        LogLevel::INFO => LOG_INFO,
        LogLevel::NOTICE => LOG_NOTICE,
        LogLevel::WARNING => LOG_WARNING,
        LogLevel::ERROR => LOG_ERR,
        LogLevel::CRITICAL => LOG_CRIT,
        LogLevel::ALERT => LOG_ALERT
    ];

    /**
     * @var BackendInterface[]
     */
    protected $backends = [];

    /**
     * Constructs the PSR-3 Logger.
     *
     * @param iterable $backends
     */
    public function __construct(iterable $backends)
    {
        foreach ($backends as $backend) {
            if (!$backend instanceof BackendInterface) {
                throw new \InvalidArgumentException(sprintf('The provided backend was not an instance of the "%s" interface', BackendInterface::class), 1515338089289);
            }
            $backend->open();
            $this->backends[] = $backend;
        }
    }

    /**
     * @param mixed $level
     * @param string $message
     * @param array $context
     * @api
     */
    public function log($level, $message, array $context = [])
    {
        $backendLogLevel = self::LOGLEVEL_MAPPING[$level];

        list($packageKey, $className, $methodName) = $this->extractLegacyDataFromContext($context);
        $additionalData = $this->removeLegacyDataFromContext($context);

        foreach ($this->backends as $backend) {
            $backend->append($message, $backendLogLevel, $additionalData, $packageKey, $className, $methodName);
        }
    }

    /**
     * @param array $context
     * @return array list of packageKey, className and methodName either string or null
     */
    protected function extractLegacyDataFromContext(array $context): array
    {
        return [
            $context['FLOW_LOG_ENVIRONMENT']['packageKey'] ?? null,
            $context['FLOW_LOG_ENVIRONMENT']['className'] ?? null,
            $context['FLOW_LOG_ENVIRONMENT']['methodName'] ?? null
        ];
    }

    /**
     * @param array $context
     * @return array
     */
    protected function removeLegacyDataFromContext(array $context): array
    {
        unset($context['FLOW_LOG_ENVIRONMENT']);
        return $context;
    }
}
