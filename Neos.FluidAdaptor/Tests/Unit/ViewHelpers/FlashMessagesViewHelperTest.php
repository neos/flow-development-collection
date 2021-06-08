<?php
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

use TYPO3Fluid\Fluid\Core\ViewHelper\TagBuilder;

require_once(__DIR__ . '/ViewHelperBaseTestcase.php');

/**
 * Testcase for FlashMessagesViewHelper
 */
class FlashMessagesViewHelperTest extends \Neos\FluidAdaptor\Tests\Unit\ViewHelpers\ViewHelperBaseTestcase
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
        $this->mockFlashMessageContainer = $this->createMock(\Neos\Flow\Mvc\FlashMessage\FlashMessageContainer::class);
        $mockControllerContext = $this->getMockBuilder(\Neos\Flow\Mvc\Controller\ControllerContext::class)->disableOriginalConstructor()->getMock();
        $mockControllerContext->expects(self::any())->method('getFlashMessageContainer')->will(self::returnValue($this->mockFlashMessageContainer));

        $this->mockTagBuilder = $this->createMock(TagBuilder::class);
        $this->viewHelper = $this->getAccessibleMock(\Neos\FluidAdaptor\ViewHelpers\FlashMessagesViewHelper::class, ['dummy']);
        $this->viewHelper->_set('controllerContext', $mockControllerContext);
        $this->viewHelper->_set('tag', $this->mockTagBuilder);
    }

    /**
     * @test
     */
    public function renderReturnsEmptyStringIfNoFlashMessagesAreInQueue()
    {
        $this->mockFlashMessageContainer->expects(self::once())->method('getMessagesAndFlush')->will(self::returnValue([]));
        $this->viewHelper = $this->prepareArguments($this->viewHelper, []);
        self::assertEmpty($this->viewHelper->render());
    }

    /**
     * Data provider for renderTests()
     */
    public function renderDataProvider()
    {
        return [
            [
                '<li class="flashmessages-ok">Some Flash Message</li>',
                [new \Neos\Error\Messages\Message('Some Flash Message')]
            ],
            [
                '<li class="flashmessages-error">Error &quot;dynamic&quot; Flash Message</li>',
                [new \Neos\Error\Messages\Error('Error %s Flash Message', null, ['"dynamic"'])]
            ],
            [
                '<li class="flashmessages-error">Error Flash &quot;Message&quot;</li><li class="flashmessages-notice">Notice Flash Message</li>',
                [new \Neos\Error\Messages\Error('Error Flash "Message"'), new \Neos\Error\Messages\Notice('Notice Flash Message')]
            ],
            [
                '<li class="flashmessages-warning"><h3>Some &quot;Warning&quot;</h3>Warning message body</li><li class="flashmessages-notice">Notice Flash Message</li>',
                [new \Neos\Error\Messages\Warning('Warning message body', null, [], 'Some "Warning"'), new \Neos\Error\Messages\Notice('Notice Flash Message')]
            ],
            [
                '<li class="customClass-ok">Message 01</li><li class="customClass-notice">Message 02</li>',
                [new \Neos\Error\Messages\Message('Message 01'), new \Neos\Error\Messages\Notice('Message 02')],
                'customClass'
            ],
        ];
    }

    /**
     * @test
     * @dataProvider renderDataProvider()
     * @param string $expectedResult
     * @param array $flashMessages
     * @param string $class
     * @return void
     */
    public function renderTests($expectedResult, array $flashMessages = [], $class = null)
    {
        $this->mockFlashMessageContainer->expects(self::once())->method('getMessagesAndFlush')->will(self::returnValue($flashMessages));
        $this->mockTagBuilder->expects(self::once())->method('setContent')->with($expectedResult);
        $this->viewHelper = $this->prepareArguments($this->viewHelper, ['class' => $class]);
        $this->viewHelper->render();
    }
}
