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
    public function storeSetsTheAjaxWidgetIdentifierInContext()
    {
        $ajaxWidgetContextHolder = $this->getAccessibleMock(\Neos\FluidAdaptor\Core\Widget\AjaxWidgetContextHolder::class, ['dummy']);

        $widgetContext = $this->createMock(\Neos\FluidAdaptor\Core\Widget\WidgetContext::class, ['setAjaxWidgetIdentifier']);
        $widgetContext->expects(self::once())->method('setAjaxWidgetIdentifier');

        $ajaxWidgetContextHolder->store($widgetContext);
    }

    /**
     * @test
     */
    public function storedWidgetContextCanBeRetrievedAgain()
    {
        $ajaxWidgetContextHolder = $this->getAccessibleMock(\Neos\FluidAdaptor\Core\Widget\AjaxWidgetContextHolder::class, ['dummy']);

        $widgetContext = $this->createMock(\Neos\FluidAdaptor\Core\Widget\WidgetContext::class, ['setAjaxWidgetIdentifier']);
        $widgetContextId = null;
        $widgetContext->expects(self::once())->method('setAjaxWidgetIdentifier')->willReturnCallback(function ($identifier) use (&$widgetContextId) {
            $widgetContextId = $identifier;
        });
        $ajaxWidgetContextHolder->store($widgetContext);

        self::assertSame($widgetContext, $ajaxWidgetContextHolder->get($widgetContextId));
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
