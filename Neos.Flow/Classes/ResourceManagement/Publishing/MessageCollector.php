<?php
namespace Neos\Flow\ResourceManagement\Publishing;

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
use Neos\Error\Messages\Error;
use Neos\Error\Messages\Message;
use Neos\Error\Messages\Notice;
use Neos\Error\Messages\Warning;
use Neos\Flow\Exception;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

/**
 * Message Collector
 *
 * @Flow\Scope("singleton")
 */
class MessageCollector
{
    const LOGLEVEL_MAPPING = [
        Error::SEVERITY_ERROR => LogLevel::ERROR,
        Error::SEVERITY_NOTICE => LogLevel::NOTICE,
        Error::SEVERITY_OK => LogLevel::INFO,
        Error::SEVERITY_WARNING => LogLevel::WARNING
    ];

    /**
     * @var \SplObjectStorage
     */
    protected $messages;

    /**
     * @Flow\Inject(name="Neos.Flow:SystemLogger")
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * Injects the (system) logger based on PSR-3.
     *
     * @param LoggerInterface $logger
     * @return void
     */
    public function injectLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Message Collector Constructor
     */
    public function __construct()
    {
        $this->messages = new \SplObjectStorage();
    }

    /**
     * @param string $message The message to log
     * @param string $severity An integer value, one of the Error::SEVERITY_* constants
     * @param integer|null $code A unique error code
     * @return void
     * @throws Exception
     * @api
     */
    public function append(string $message, string $severity = Error::SEVERITY_ERROR, ?int $code = null): void
    {
        switch ($severity) {
            case Error::SEVERITY_ERROR:
                $notification = new Error($message, $code);
                break;
            case Error::SEVERITY_WARNING:
                $notification = new Warning($message, $code);
                break;
            case Error::SEVERITY_NOTICE:
                $notification = new Notice($message, $code);
                break;
            case Error::SEVERITY_OK:
                $notification = new Message($message, $code);
                break;
            default:
                throw new Exception('Invalid severity', 1455819761);
        }
        $this->messages->attach($notification);
    }

    /**
     * @return boolean
     * @api
     */
    public function hasMessages(): bool
    {
        return $this->messages->count() > 0;
    }

    /**
     * @param callable $callback a callback function to process every notification
     * @return void
     * @api
     */
    public function flush(callable $callback = null): void
    {
        foreach ($this->messages as $message) {
            /** @var Message $message */
            $this->messages->detach($message);
            $severity = self::LOGLEVEL_MAPPING[$message->getSeverity()];
            $this->logger->log($severity, 'ResourcePublishingMessage: ' . $message->getMessage());
            if ($callback !== null) {
                $callback($message);
            }
        }
    }

    /**
     * Flush all notification during the object lifecycle
     *
     * @return void
     */
    public function __destruct()
    {
        $this->flush();
    }
}
