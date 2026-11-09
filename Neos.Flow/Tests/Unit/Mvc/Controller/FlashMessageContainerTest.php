<?php

declare(strict_types=1);

namespace Neos\Flow\Tests\Unit\Mvc\Controller;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */
use Neos\Error\Messages as FlowError;
use Neos\Flow\Mvc\FlashMessage\FlashMessageContainer;
use Neos\Flow\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Testcase for the Flash Messages Container
 */
final class FlashMessageContainerTest extends UnitTestCase
{
    /**
     * @var FlashMessageContainer
     */
    protected $flashMessageContainer;

    protected function setUp(): void
    {
        $this->flashMessageContainer = new FlashMessageContainer();
    }

    #[Test]
    public function addedFlashMessageCanBeReadOutAgain()
    {
        $messages = [
            0 => new FlowError\Notice('This is a test message', 1),
            1 => new FlowError\Warning('This is another test message', 2)
        ];
        $this->flashMessageContainer->addMessage($messages[0]);
        $this->flashMessageContainer->addMessage($messages[1]);
        $returnedFlashMessages = $this->flashMessageContainer->getMessages();

        self::assertCount(2, $returnedFlashMessages);

        $i = 0;
        foreach ($returnedFlashMessages as $flashMessage) {
            self::assertEquals($flashMessage, $messages[$i++]);
        }
    }

    #[Test]
    public function flushResetsFlashMessages()
    {
        $message1 = new FlowError\Message('This is a test message');
        $this->flashMessageContainer->addMessage($message1);
        $this->flashMessageContainer->flush();
        self::assertEquals([], $this->flashMessageContainer->getMessages());
    }

    #[Test]
    public function getMessagesAndFlushFetchesAllEntriesAndFlushesTheFlashMessages()
    {
        $messages = [
            0 => new FlowError\Notice('This is a test message', 1),
            1 => new FlowError\Warning('This is another test message', 2)
        ];
        $this->flashMessageContainer->addMessage($messages[0]);
        $this->flashMessageContainer->addMessage($messages[1]);
        $returnedFlashMessages = $this->flashMessageContainer->getMessagesAndFlush();

        self::assertCount(2, $returnedFlashMessages);

        $i = 0;
        foreach ($returnedFlashMessages as $flashMessage) {
            self::assertEquals($flashMessage, $messages[$i++]);
        }

        self::assertEquals([], $this->flashMessageContainer->getMessages());
    }

    #[Test]
    public function messagesCanBeFilteredBySeverity()
    {
        $messages = [
            0 => new FlowError\Notice('This is a test message', 1),
            1 => new FlowError\Warning('This is another test message', 2)
        ];
        $this->flashMessageContainer->addMessage($messages[0]);
        $this->flashMessageContainer->addMessage($messages[1]);

        $filteredFlashMessages = $this->flashMessageContainer->getMessages(FlowError\Message::SEVERITY_NOTICE);

        self::assertCount(1, $filteredFlashMessages);

        reset($filteredFlashMessages);
        $flashMessage = current($filteredFlashMessages);
        self::assertEquals($messages[0], $flashMessage);
    }

    #[Test]
    public function getMessagesAndFlushCanAlsoFilterBySeverity()
    {
        $messages = [
            0 => new FlowError\Notice('This is a test message', 1),
            1 => new FlowError\Warning('This is another test message', 2)
        ];
        $this->flashMessageContainer->addMessage($messages[0]);
        $this->flashMessageContainer->addMessage($messages[1]);

        $filteredFlashMessages = $this->flashMessageContainer->getMessagesAndFlush(FlowError\Message::SEVERITY_NOTICE);

        self::assertCount(1, $filteredFlashMessages);

        reset($filteredFlashMessages);
        $flashMessage = current($filteredFlashMessages);
        self::assertEquals($messages[0], $flashMessage);

        self::assertEquals([], $this->flashMessageContainer->getMessages(FlowError\Message::SEVERITY_NOTICE));
        self::assertEquals([$messages[1]], array_values($this->flashMessageContainer->getMessages()));
    }
}
