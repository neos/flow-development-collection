<?php
namespace Neos\Flow\Mvc\FlashMessage\Storage;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Error\Messages\Error;
use Neos\Error\Messages\Message;
use Neos\Error\Messages\Notice;
use Neos\Error\Messages\Warning;
use Neos\Flow\Http\Cookie;
use Neos\Flow\Mvc\FlashMessage\FlashMessageContainer;
use Neos\Flow\Mvc\FlashMessage\FlashMessageStorageInterface;
use Psr\Http\Message\ServerRequestInterface as HttpRequestInterface;
use Psr\Http\Message\ResponseInterface as HttpResponseInterface;

class FlashMessageCookieStorage implements FlashMessageStorageInterface
{
    const DEFAULT_COOKIE_NAME = 'Neos_Flow_FlashMessages';

    /**
     * @var array
     */
    private $options;

    /**
     * @var string Name of the FlashMessage cookie, defaults to self::DEFAULT_COOKIE_NAME
     */
    private $cookieName;

    /**
     * @var FlashMessageContainer|null
     */
    private $flashMessageContainer;

    /**
     * @param array $options
     */
    public function __construct(array $options = [])
    {
        $this->options = $options;
        $this->cookieName = $this->options['cookieName'] ?? self::DEFAULT_COOKIE_NAME;
    }

    /**
     * @param HttpRequestInterface $request The current HTTP request for storages that persist the FlashMessages via HTTP
     * @return FlashMessageContainer
     */
    public function load(HttpRequestInterface $request): FlashMessageContainer
    {
        if ($this->flashMessageContainer === null) {
            $this->flashMessageContainer = $this->restoreFlashMessageContainerFromCookie($request);
            if ($this->flashMessageContainer === null) {
                $this->flashMessageContainer = new FlashMessageContainer();
            }
        }
        return $this->flashMessageContainer;
    }

    /**
     * @param HttpRequestInterface $request
     * @return FlashMessageContainer|null
     */
    private function restoreFlashMessageContainerFromCookie(HttpRequestInterface $request): ?FlashMessageContainer
    {
        $cookies = $request->getCookieParams();
        if (empty($cookies[$this->cookieName])) {
            return null;
        }
        $cookieValue = $cookies[$this->cookieName];
        $flashMessagesArray = json_decode($cookieValue, true);
        $flashMessageContainer = new FlashMessageContainer();
        foreach ($flashMessagesArray as $flashMessageArray) {
            $flashMessageContainer->addMessage($this->deserializeMessage($flashMessageArray));
        }
        return $flashMessageContainer;
    }

    /**
     * @param HttpResponseInterface $response The current HTTP response for storages that persist the FlashMessages via HTTP
     * @return HttpResponseInterface
     */
    public function persist(HttpResponseInterface $response): HttpResponseInterface
    {
        if ($this->flashMessageContainer === null) {
            return $response;
        }
        if (!$this->flashMessageContainer->hasMessages()) {
            $cookie = new Cookie($this->cookieName, null, 1502980557, 0);
            return $response->withAddedHeader('Set-Cookie', (string)$cookie);
        }
        $serializedMessages = [];
        foreach ($this->flashMessageContainer->getMessagesAndFlush() as $flashMessage) {
            $serializedMessages[] = $this->serializeMessage($flashMessage);
        }
        $cookie = new Cookie($this->cookieName, json_encode($serializedMessages), 0, null, null, '/', false, false);
        return $response->withAddedHeader('Set-Cookie', (string)$cookie);
    }

    /**
     * @param Message $message
     * @return array
     */
    private function serializeMessage(Message $message): array
    {
        return [
            'title' => $message->getTitle(),
            'message' => $message->getMessage(),
            'arguments' => $message->getArguments(),
            'code' => $message->getCode(),
            'severity' => $message->getSeverity(),
            'renderedMessage' => $message->render(),
        ];
    }

    /**
     * @param array $messageArray
     * @return Message
     */
    private function deserializeMessage(array $messageArray): Message
    {
        $messageBody = $messageArray['message'] ?? '';
        $messageCode = $messageArray['code'] ?? null;
        $messageArguments = $messageArray['arguments'] ?? [];
        $messageTitle = $messageArray['title'] ?? '';
        $messageSeverity = $messageArray['severity'] ?? null;
        switch ($messageSeverity) {
            case Message::SEVERITY_NOTICE:
                return new Notice($messageBody, $messageCode, $messageArguments, $messageTitle);
            case Message::SEVERITY_WARNING:
                return new Warning($messageBody, $messageCode, $messageArguments, $messageTitle);
            case Message::SEVERITY_ERROR:
                return new Error($messageBody, $messageCode, $messageArguments, $messageTitle);
            default:
                return new Message($messageBody, $messageCode, $messageArguments, $messageTitle);
        }
    }
}
