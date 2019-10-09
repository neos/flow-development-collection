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

use Neos\FluidAdaptor\Core\Widget\Exception\WidgetContextNotFoundException;

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
        $ajaxWidgetContextHolder = $this->getAccessibleMock(\Neos\FluidAdaptor\Core\Widget\AjaxWidgetContextHolder::class, ['dummy']);
        $ajaxWidgetContextHolder->_set('nextFreeAjaxWidgetId', 123);

        $widgetContext = $this->createMock(\Neos\FluidAdaptor\Core\Widget\WidgetContext::class, ['setAjaxWidgetIdentifier']);
        $widgetContext->expects(self::once())->method('setAjaxWidgetIdentifier')->with(123);

        $ajaxWidgetContextHolder->store($widgetContext);
        self::assertEquals(124, $ajaxWidgetContextHolder->_get('nextFreeAjaxWidgetId'));
    }

    /**
     * @test
     */
    public function storedWidgetContextCanBeRetrievedAgain()
    {
        $ajaxWidgetContextHolder = $this->getAccessibleMock(\Neos\FluidAdaptor\Core\Widget\AjaxWidgetContextHolder::class, ['dummy']);
        $ajaxWidgetContextHolder->_set('nextFreeAjaxWidgetId', 123);

        $widgetContext = $this->createMock(\Neos\FluidAdaptor\Core\Widget\WidgetContext::class, ['setAjaxWidgetIdentifier']);
        $ajaxWidgetContextHolder->store($widgetContext);

        self::assertSame($widgetContext, $ajaxWidgetContextHolder->get('123'));
    }

    /**
     * @test
     */
    public function getThrowsExceptionIfWidgetContextIsNotFound()
    {
        $this->expectException(WidgetContextNotFoundException::class);
        $ajaxWidgetContextHolder = new \Neos\FluidAdaptor\Core\Widget\AjaxWidgetContextHolder();
        $ajaxWidgetContextHolder->get(42);
    }
}
