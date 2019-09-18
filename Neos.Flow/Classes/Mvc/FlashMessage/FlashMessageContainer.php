<?php
namespace Neos\Flow\Mvc\FlashMessage;

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
use Neos\Error\Messages\Message;

/**
 * This is a container for all Flash Messages.
 *
 * @internal You should not interact with this object directly, use the FlashMessageService instead or the addFlashMessage() method when in an AbstractController implementation
 */
class FlashMessageContainer
{
    /**
     * @var array
     */
    protected $messages = [];

    /**
     * Add a flash message object.
     *
     * @param Message $message
     * @return void
     * @api
     */
    public function addMessage(Message $message)
    {
        $this->messages[] = $message;
    }

    /**
     * Whether there are any messages in the FlashMessageContainer
     *
     * @return bool
     * @api
     */
    public function hasMessages(): bool
    {
        return $this->messages !== [];
    }

    /**
     * Returns all currently stored flash messages.
     *
     * @param string $severity severity of messages (from Message::SEVERITY_* constants) to return.
     * @return array<Message>
     * @api
     */
    public function getMessages($severity = null)
    {
        if ($severity === null) {
            return $this->messages;
        }

        $messages = [];
        foreach ($this->messages as $message) {
            if ($message->getSeverity() === $severity) {
                $messages[] = $message;
            }
        }
        return $messages;
    }

    /**
     * Remove messages from this container.
     *
     * @param string $severity severity of messages (from Message::SEVERITY_* constants) to remove.
     * @return void
     * @api
     */
    public function flush($severity = null)
    {
        if ($severity === null) {
            $this->messages = [];
        } else {
            foreach ($this->messages as $index => $message) {
                if ($message->getSeverity() === $severity) {
                    unset($this->messages[$index]);
                }
            }
        }
    }

    /**
     * Get all flash messages (with given severity) currently available and remove them from the container.
     *
     * @param string $severity severity of the messages (One of the Message::SEVERITY_* constants)
     * @return array<Message>
     * @api
     */
    public function getMessagesAndFlush($severity = null)
    {
        $messages = $this->getMessages($severity);
        if (count($messages) > 0) {
            $this->flush($severity);
        }
        return $messages;
    }
}
