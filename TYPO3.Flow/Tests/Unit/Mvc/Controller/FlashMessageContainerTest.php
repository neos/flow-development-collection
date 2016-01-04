<?php
namespace TYPO3\Flow\Tests\Unit\Mvc\Controller;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Error\Message;
use TYPO3\Flow\Error\Notice;
use TYPO3\Flow\Error\Warning;

/**
 * Testcase for the Flash Messages Container
 *
 */
class FlashMessageContainerTest extends \TYPO3\Flow\Tests\UnitTestCase
{
    /**
     * @var \TYPO3\Flow\Mvc\FlashMessageContainer
     */
    protected $flashMessageContainer;

    public function setUp()
    {
        $this->flashMessageContainer = new \TYPO3\Flow\Mvc\FlashMessageContainer();
    }

    /**
     * @test
     */
    public function addedFlashMessageCanBeReadOutAgain()
    {
        $messages = array(
            0 => new Notice('This is a test message', 1),
            1 => new Warning('This is another test message', 2)
        );
        $this->flashMessageContainer->addMessage($messages[0]);
        $this->flashMessageContainer->addMessage($messages[1]);
        $returnedFlashMessages = $this->flashMessageContainer->getMessages();

        $this->assertEquals(count($returnedFlashMessages), 2);

        $i = 0;
        foreach ($returnedFlashMessages as $flashMessage) {
            $this->assertEquals($flashMessage, $messages[$i++]);
        }
    }

    /**
     * @test
     */
    public function flushResetsFlashMessages()
    {
        $message1 = new Message('This is a test message');
        $this->flashMessageContainer->addMessage($message1);
        $this->flashMessageContainer->flush();
        $this->assertEquals(array(), $this->flashMessageContainer->getMessages());
    }

    /**
     * @test
     */
    public function getMessagesAndFlushFetchesAllEntriesAndFlushesTheFlashMessages()
    {
        $messages = array(
            0 => new Notice('This is a test message', 1),
            1 => new Warning('This is another test message', 2)
        );
        $this->flashMessageContainer->addMessage($messages[0]);
        $this->flashMessageContainer->addMessage($messages[1]);
        $returnedFlashMessages = $this->flashMessageContainer->getMessagesAndFlush();

        $this->assertEquals(count($returnedFlashMessages), 2);

        $i = 0;
        foreach ($returnedFlashMessages as $flashMessage) {
            $this->assertEquals($flashMessage, $messages[$i++]);
        }

        $this->assertEquals(array(), $this->flashMessageContainer->getMessages());
    }

    /**
     * @test
     */
    public function messagesCanBeFilteredBySeverity()
    {
        $messages = array(
            0 => new Notice('This is a test message', 1),
            1 => new Warning('This is another test message', 2)
        );
        $this->flashMessageContainer->addMessage($messages[0]);
        $this->flashMessageContainer->addMessage($messages[1]);

        $filteredFlashMessages = $this->flashMessageContainer->getMessages(Message::SEVERITY_NOTICE);

        $this->assertEquals(count($filteredFlashMessages), 1);

        reset($filteredFlashMessages);
        $flashMessage = current($filteredFlashMessages);
        $this->assertEquals($messages[0], $flashMessage);
    }

    /**
     * @test
     */
    public function getMessagesAndFlushCanAlsoFilterBySeverity()
    {
        $messages = array(
            0 => new Notice('This is a test message', 1),
            1 => new Warning('This is another test message', 2)
        );
        $this->flashMessageContainer->addMessage($messages[0]);
        $this->flashMessageContainer->addMessage($messages[1]);

        $filteredFlashMessages = $this->flashMessageContainer->getMessagesAndFlush(Message::SEVERITY_NOTICE);

        $this->assertEquals(count($filteredFlashMessages), 1);

        reset($filteredFlashMessages);
        $flashMessage = current($filteredFlashMessages);
        $this->assertEquals($messages[0], $flashMessage);

        $this->assertEquals(array(), $this->flashMessageContainer->getMessages(Message::SEVERITY_NOTICE));
        $this->assertEquals(array($messages[1]), array_values($this->flashMessageContainer->getMessages()));
    }
}
