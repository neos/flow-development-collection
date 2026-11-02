<?php

declare(strict_types=1);

namespace Neos\FluidAdaptor\Tests\Unit\ViewHelpers;

/*
 * This file is part of the Neos.FluidAdaptor package.
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
use Neos\Flow\Mvc\Controller\ControllerContext;
use Neos\Flow\Mvc\FlashMessage\FlashMessageContainer;
use Neos\FluidAdaptor\ViewHelpers\FlashMessagesViewHelper;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3Fluid\Fluid\Core\ViewHelper\TagBuilder;

require_once(__DIR__ . '/ViewHelperBaseTestcase.php');

/**
 * Testcase for FlashMessagesViewHelper
 */
final class FlashMessagesViewHelperTest extends ViewHelperBaseTestcase
{
    /**
     * @var \Neos\FluidAdaptor\ViewHelpers\FlashMessagesViewHelper
     */
    protected $viewHelper;

    /**
     * @var \Neos\Flow\Mvc\FlashMessage\FlashMessageContainer
     */
    protected $mockFlashMessageContainer;

    /**
     * @var TagBuilder
     */
    protected $mockTagBuilder;

    /**
     * Sets up this test case
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->mockFlashMessageContainer = $this->createMock(FlashMessageContainer::class);
        $mockControllerContext = $this->createMock(ControllerContext::class);
        $mockControllerContext->method('getFlashMessageContainer')->willReturn(($this->mockFlashMessageContainer));

        $this->mockTagBuilder = $this->createMock(TagBuilder::class);
        $this->viewHelper = $this->getAccessibleMock(FlashMessagesViewHelper::class, []);
        $this->viewHelper->_set('controllerContext', $mockControllerContext);
        $this->viewHelper->_set('tag', $this->mockTagBuilder);
    }

    #[Test]
    public function renderReturnsEmptyStringIfNoFlashMessagesAreInQueue()
    {
        $this->mockFlashMessageContainer->expects($this->once())->method('getMessagesAndFlush')->willReturn(([]));
        $this->viewHelper = $this->prepareArguments($this->viewHelper, []);
        self::assertEmpty($this->viewHelper->render());
    }

    /**
     * Data provider for renderTests()
     */
    public static function renderDataProvider(): \Iterator
    {
        yield [
            '<li class="flashmessages-ok">Some Flash Message</li>',
            [new Message('Some Flash Message')]
        ];
        yield [
            '<li class="flashmessages-error">Error &quot;dynamic&quot; Flash Message</li>',
            [new Error('Error %s Flash Message', null, ['"dynamic"'])]
        ];
        yield [
            '<li class="flashmessages-error">Error Flash &quot;Message&quot;</li><li class="flashmessages-notice">Notice Flash Message</li>',
            [new Error('Error Flash "Message"'), new Notice('Notice Flash Message')]
        ];
        yield [
            '<li class="flashmessages-warning"><h3>Some &quot;Warning&quot;</h3>Warning message body</li><li class="flashmessages-notice">Notice Flash Message</li>',
            [new Warning('Warning message body', null, [], 'Some "Warning"'), new Notice('Notice Flash Message')]
        ];
        yield [
            '<li class="customClass-ok">Message 01</li><li class="customClass-notice">Message 02</li>',
            [new Message('Message 01'), new Notice('Message 02')],
            'customClass'
        ];
    }

    /**
     * @param string $expectedResult
     * @param array $flashMessages
     * @param string $class
     * @return void
     */
    #[DataProvider('renderDataProvider')]
    #[Test]
    public function renderTests($expectedResult, array $flashMessages = [], $class = null)
    {
        $this->mockFlashMessageContainer->expects($this->once())->method('getMessagesAndFlush')->willReturn(($flashMessages));
        $this->mockTagBuilder->expects($this->once())->method('setContent')->with($expectedResult);
        $this->viewHelper = $this->prepareArguments($this->viewHelper, ['class' => $class]);
        $this->viewHelper->render();
    }
}
