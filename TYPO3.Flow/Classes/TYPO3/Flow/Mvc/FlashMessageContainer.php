<?php
namespace TYPO3\Flow\Mvc;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Annotations as Flow;

/**
 * This is a container for all Flash Messages.
 *
 * @Flow\Scope("session")
 * @api
 */
class FlashMessageContainer
{
    /**
     * @var array
     */
    protected $messages = array();

    /**
     * Add a flash message object.
     *
     * @param \TYPO3\Flow\Error\Message $message
     * @return void
     * @Flow\Session(autoStart=true)
     * @api
     */
    public function addMessage(\TYPO3\Flow\Error\Message $message)
    {
        $this->messages[] = $message;
    }

    /**
     * Returns all currently stored flash messages.
     *
     * @param string $severity severity of messages (from \TYPO3\Flow\Error\Message::SEVERITY_* constants) to return.
     * @return array<\TYPO3\Flow\Error\Message>
     * @api
     */
    public function getMessages($severity = null)
    {
        if ($severity === null) {
            return $this->messages;
        }

        $messages = array();
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
     * @param string $severity severity of messages (from \TYPO3\Flow\Error\Message::SEVERITY_* constants) to remove.
     * @return void
     * @Flow\Session(autoStart=true)
     * @api
     */
    public function flush($severity = null)
    {
        if ($severity === null) {
            $this->messages = array();
            return;
        }

        foreach ($this->messages as $index => $message) {
            if ($message->getSeverity() === $severity) {
                unset($this->messages[$index]);
            }
        }
    }

    /**
     * Get all flash messages (with given severity) currently available and remove them from the container.
     *
     * @param string $severity severity of the messages (One of the \TYPO3\Flow\Error\Message::SEVERITY_* constants)
     * @return array<\TYPO3\Flow\Error\Message>
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
