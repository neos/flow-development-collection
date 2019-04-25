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
use Neos\Flow\Http\Request as HttpRequest;
use Neos\Flow\Http\Response;
use Neos\Flow\Mvc\FlashMessage\FlashMessageContainer;
use Neos\Flow\Mvc\FlashMessage\FlashMessageStorageInterface;

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
     * @var FlashMessageContainer
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
     * @param HttpRequest $request The current HTTP request for storages that persist the FlashMessages via HTTP
     * @return FlashMessageContainer
     */
    public function load(HttpRequest $request): FlashMessageContainer
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
     * @return FlashMessageContainer|null
     */
    private function restoreFlashMessageContainerFromCookie(HttpRequest $request)
    {
        if (!$request->hasCookie($this->cookieName)) {
            return null;
        }
        $cookieValue = $request->getCookie($this->cookieName)->getValue();
        $flashMessagesArray = json_decode($cookieValue, true);
        $flashMessageContainer = new FlashMessageContainer();
        foreach ($flashMessagesArray as $flashMessageArray) {
            $flashMessageContainer->addMessage($this->deserializeMessage($flashMessageArray));
        }
        return $flashMessageContainer;
    }

    /**
     * @param Response $response The current HTTP response for storages that persist the FlashMessages via HTTP
     * @return void
     */
    public function persist(Response $response)
    {
        if ($this->flashMessageContainer === null) {
            return;
        }
        if (!$this->flashMessageContainer->hasMessages()) {
            $response->setCookie(new Cookie($this->cookieName, null, 1502980557, 0));
            return;
        }
        $serializedMessages = [];
        foreach ($this->flashMessageContainer->getMessagesAndFlush() as $flashMessage) {
            $serializedMessages[] = $this->serializeMessage($flashMessage);
        }
        $response->setCookie(new Cookie($this->cookieName, json_encode($serializedMessages), 0, null, null, '/', false, false));
    }

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
