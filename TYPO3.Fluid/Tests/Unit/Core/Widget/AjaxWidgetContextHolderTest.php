<?php
namespace TYPO3\Fluid\Tests\Unit\Core\Widget;

/*
 * This file is part of the TYPO3.Fluid package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

/**
 * Testcase for AjaxWidgetContextHolder
 *
 */
class AjaxWidgetContextHolderTest extends \TYPO3\Flow\Tests\UnitTestCase
{
    /**
     * @test
     */
    public function storeSetsTheAjaxWidgetIdentifierInContextAndIncreasesIt()
    {
        $ajaxWidgetContextHolder = $this->getAccessibleMock('TYPO3\Fluid\Core\Widget\AjaxWidgetContextHolder', array('dummy'));
        $ajaxWidgetContextHolder->_set('nextFreeAjaxWidgetId', 123);

        $widgetContext = $this->createMock('TYPO3\Fluid\Core\Widget\WidgetContext', array('setAjaxWidgetIdentifier'));
        $widgetContext->expects($this->once())->method('setAjaxWidgetIdentifier')->with(123);

        $ajaxWidgetContextHolder->store($widgetContext);
        $this->assertEquals(124, $ajaxWidgetContextHolder->_get('nextFreeAjaxWidgetId'));
    }

    /**
     * @test
     */
    public function storedWidgetContextCanBeRetrievedAgain()
    {
        $ajaxWidgetContextHolder = $this->getAccessibleMock('TYPO3\Fluid\Core\Widget\AjaxWidgetContextHolder', array('dummy'));
        $ajaxWidgetContextHolder->_set('nextFreeAjaxWidgetId', 123);

        $widgetContext = $this->createMock('TYPO3\Fluid\Core\Widget\WidgetContext', array('setAjaxWidgetIdentifier'));
        $ajaxWidgetContextHolder->store($widgetContext);

        $this->assertSame($widgetContext, $ajaxWidgetContextHolder->get('123'));
    }

    /**
     * @test
     * @expectedException \TYPO3\Fluid\Core\Widget\Exception\WidgetContextNotFoundException
     */
    public function getThrowsExceptionIfWidgetContextIsNotFound()
    {
        $ajaxWidgetContextHolder = new \TYPO3\Fluid\Core\Widget\AjaxWidgetContextHolder();
        $ajaxWidgetContextHolder->get(42);
    }
}
