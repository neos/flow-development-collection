<?php
namespace TYPO3\Fluid\Tests\Unit\Core\Parser\Interceptor;

/*
 * This file is part of the TYPO3.Fluid package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Tests\UnitTestCase;
use TYPO3\Fluid\Core\Parser\Interceptor\Escape;
use TYPO3\Fluid\Core\Parser\InterceptorInterface;
use TYPO3\Fluid\Core\Parser\ParsingState;
use TYPO3\Fluid\Core\Parser\SyntaxTree\ViewHelperNode;
use TYPO3\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * Testcase for Interceptor\Escape
 */
class EscapeTest extends UnitTestCase
{
    /**
     * @var Escape|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $escapeInterceptor;

    /**
     * @var AbstractViewHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $mockViewHelper;

    /**
     * @var ViewHelperNode|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $mockNode;

    /**
     * @var ParsingState|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $mockParsingState;

    public function setUp()
    {
        $this->escapeInterceptor = $this->getAccessibleMock('TYPO3\Fluid\Core\Parser\Interceptor\Escape', array('dummy'));
        $this->mockViewHelper = $this->getMockBuilder('TYPO3\Fluid\Core\ViewHelper\AbstractViewHelper')->disableOriginalConstructor()->getMock();
        $this->mockNode = $this->getMockBuilder('TYPO3\Fluid\Core\Parser\SyntaxTree\ViewHelperNode')->disableOriginalConstructor()->getMock();
        $this->mockParsingState = $this->getMockBuilder('TYPO3\Fluid\Core\Parser\ParsingState')->disableOriginalConstructor()->getMock();
    }

    /**
     * @test
     */
    public function processDoesNotDisableEscapingInterceptorByDefault()
    {
        $interceptorPosition = InterceptorInterface::INTERCEPT_OPENING_VIEWHELPER;
        $this->mockViewHelper->expects($this->once())->method('isChildrenEscapingEnabled')->will($this->returnValue(true));
        $this->mockNode->expects($this->once())->method('getUninitializedViewHelper')->will($this->returnValue($this->mockViewHelper));

        $this->assertTrue($this->escapeInterceptor->_get('childrenEscapingEnabled'));
        $this->escapeInterceptor->process($this->mockNode, $interceptorPosition, $this->mockParsingState);
        $this->assertTrue($this->escapeInterceptor->_get('childrenEscapingEnabled'));
    }

    /**
     * @test
     */
    public function processDisablesEscapingInterceptorIfViewHelperDisablesIt()
    {
        $interceptorPosition = InterceptorInterface::INTERCEPT_OPENING_VIEWHELPER;
        $this->mockViewHelper->expects($this->once())->method('isChildrenEscapingEnabled')->will($this->returnValue(false));
        $this->mockNode->expects($this->once())->method('getUninitializedViewHelper')->will($this->returnValue($this->mockViewHelper));

        $this->assertTrue($this->escapeInterceptor->_get('childrenEscapingEnabled'));
        $this->escapeInterceptor->process($this->mockNode, $interceptorPosition, $this->mockParsingState);
        $this->assertFalse($this->escapeInterceptor->_get('childrenEscapingEnabled'));
    }

    /**
     * @test
     */
    public function processReenablesEscapingInterceptorOnClosingViewHelperTagIfItWasDisabledBefore()
    {
        $interceptorPosition = InterceptorInterface::INTERCEPT_CLOSING_VIEWHELPER;
        $this->mockViewHelper->expects($this->any())->method('isOutputEscapingEnabled')->will($this->returnValue(false));
        $this->mockNode->expects($this->any())->method('getUninitializedViewHelper')->will($this->returnValue($this->mockViewHelper));

        $this->escapeInterceptor->_set('childrenEscapingEnabled', false);
        $this->escapeInterceptor->_set('viewHelperNodesWhichDisableTheInterceptor', array($this->mockNode));

        $this->escapeInterceptor->process($this->mockNode, $interceptorPosition, $this->mockParsingState);
        $this->assertTrue($this->escapeInterceptor->_get('childrenEscapingEnabled'));
    }

    /**
     * @test
     */
    public function processWrapsCurrentViewHelperInHtmlspecialcharsViewHelperOnObjectAccessor()
    {
        $interceptorPosition = InterceptorInterface::INTERCEPT_OBJECTACCESSOR;
        $mockNode = $this->getMockBuilder('TYPO3\Fluid\Core\Parser\SyntaxTree\ObjectAccessorNode')->disableOriginalConstructor()->getMock();
        $mockEscapeViewHelper = $this->createMock('TYPO3\Fluid\ViewHelpers\Format\HtmlspecialcharsViewHelper');
        $mockObjectManager = $this->createMock('TYPO3\Flow\Object\ObjectManagerInterface');
        $mockObjectManager->expects($this->at(0))->method('get')->with('TYPO3\Fluid\ViewHelpers\Format\HtmlspecialcharsViewHelper')->will($this->returnValue($mockEscapeViewHelper));
        $mockObjectManager->expects($this->at(1))->method('get')->with('TYPO3\Fluid\Core\Parser\SyntaxTree\ViewHelperNode', $mockEscapeViewHelper, array('value' => $mockNode))->will($this->returnValue($this->mockNode));
        $this->escapeInterceptor->injectObjectManager($mockObjectManager);

        $actualResult = $this->escapeInterceptor->process($mockNode, $interceptorPosition, $this->mockParsingState);
        $this->assertSame($this->mockNode, $actualResult);
    }
}
