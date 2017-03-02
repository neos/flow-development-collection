<?php
namespace Neos\FluidAdaptor\Tests\Unit\Core\Widget;

/*
 * This file is part of the Neos.FluidAdaptor package.
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
class AjaxWidgetContextHolderTest extends \Neos\Flow\Tests\UnitTestCase
{
    /**
     * @test
     */
    public function storeSetsTheAjaxWidgetIdentifierInContextAndIncreasesIt()
    {
        $ajaxWidgetContextHolder = $this->getAccessibleMock(\Neos\FluidAdaptor\Core\Widget\AjaxWidgetContextHolder::class, array('dummy'));
        $ajaxWidgetContextHolder->_set('nextFreeAjaxWidgetId', 123);

        $widgetContext = $this->createMock(\Neos\FluidAdaptor\Core\Widget\WidgetContext::class, array('setAjaxWidgetIdentifier'));
        $widgetContext->expects($this->once())->method('setAjaxWidgetIdentifier')->with(123);

        $ajaxWidgetContextHolder->store($widgetContext);
        $this->assertEquals(124, $ajaxWidgetContextHolder->_get('nextFreeAjaxWidgetId'));
    }

    /**
     * @test
     */
    public function storedWidgetContextCanBeRetrievedAgain()
    {
        $ajaxWidgetContextHolder = $this->getAccessibleMock(\Neos\FluidAdaptor\Core\Widget\AjaxWidgetContextHolder::class, array('dummy'));
        $ajaxWidgetContextHolder->_set('nextFreeAjaxWidgetId', 123);

        $widgetContext = $this->createMock(\Neos\FluidAdaptor\Core\Widget\WidgetContext::class, array('setAjaxWidgetIdentifier'));
        $ajaxWidgetContextHolder->store($widgetContext);

        $this->assertSame($widgetContext, $ajaxWidgetContextHolder->get('123'));
    }

    /**
     * @test
     * @expectedException \Neos\FluidAdaptor\Core\Widget\Exception\WidgetContextNotFoundException
     */
    public function getThrowsExceptionIfWidgetContextIsNotFound()
    {
        $ajaxWidgetContextHolder = new \Neos\FluidAdaptor\Core\Widget\AjaxWidgetContextHolder();
        $ajaxWidgetContextHolder->get(42);
    }
}
