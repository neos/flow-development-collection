<?php

declare(strict_types=1);

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
use Neos\Flow\Tests\UnitTestCase;
use Neos\FluidAdaptor\Core\Widget\AjaxWidgetContextHolder;
use Neos\FluidAdaptor\Core\Widget\Exception\WidgetContextNotFoundException;
use Neos\FluidAdaptor\Core\Widget\WidgetContext;
use PHPUnit\Framework\Attributes\Test;

/**
 * Testcase for AjaxWidgetContextHolder
 *
 */
final class AjaxWidgetContextHolderTest extends UnitTestCase
{
    #[Test]
    public function storeSetsTheAjaxWidgetIdentifierInContext()
    {
        $ajaxWidgetContextHolder = $this->getAccessibleMock(AjaxWidgetContextHolder::class, []);

        $widgetContext = $this->createMock(WidgetContext::class, ['setAjaxWidgetIdentifier']);
        $widgetContext->expects($this->once())->method('setAjaxWidgetIdentifier');

        $ajaxWidgetContextHolder->store($widgetContext);
    }

    #[Test]
    public function storedWidgetContextCanBeRetrievedAgain()
    {
        $ajaxWidgetContextHolder = $this->getAccessibleMock(AjaxWidgetContextHolder::class, []);

        $widgetContext = $this->createMock(WidgetContext::class, ['setAjaxWidgetIdentifier']);
        $widgetContextId = null;
        $widgetContext->expects($this->once())->method('setAjaxWidgetIdentifier')->willReturnCallback(function ($identifier) use (&$widgetContextId) {
            $widgetContextId = $identifier;
        });
        $ajaxWidgetContextHolder->store($widgetContext);

        self::assertSame($widgetContext, $ajaxWidgetContextHolder->get($widgetContextId));
    }

    #[Test]
    public function getThrowsExceptionIfWidgetContextIsNotFound()
    {
        $this->expectException(WidgetContextNotFoundException::class);
        $ajaxWidgetContextHolder = new AjaxWidgetContextHolder();
        $ajaxWidgetContextHolder->get(42);
    }
}
