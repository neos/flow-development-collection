<?php
namespace Neos\Flow\Tests\Unit\Mvc\View;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Mvc;
use Neos\Flow\Tests\UnitTestCase;

/**
 * Testcase for the MVC AbstractView
 */
class AbstractViewTest extends UnitTestCase
{
    /**
     * @test
     */
    public function assignAddsValueToInternalVariableCollection()
    {
        $view = $this->getAccessibleMock(Mvc\View\AbstractView::class, ['setControllerContext', 'render']);
        $view
            ->assign('foo', 'FooValue')
            ->assign('bar', 'BarValue');

        $expectedResult = ['foo' => 'FooValue', 'bar' => 'BarValue'];
        $actualResult = $view->_get('variables');
        $this->assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function assignCanOverridePreviouslyAssignedValues()
    {
        $view = $this->getAccessibleMock(Mvc\View\AbstractView::class, ['setControllerContext', 'render']);
        $view->assign('foo', 'FooValue');
        $view->assign('foo', 'FooValueOverridden');

        $expectedResult = ['foo' => 'FooValueOverridden'];
        $actualResult = $view->_get('variables');
        $this->assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function assignMultipleAddsValuesToInternalVariableCollection()
    {
        $view = $this->getAccessibleMock(Mvc\View\AbstractView::class, ['setControllerContext', 'render']);
        $view
            ->assignMultiple(['foo' => 'FooValue', 'bar' => 'BarValue'])
            ->assignMultiple(['baz' => 'BazValue']);

        $expectedResult = ['foo' => 'FooValue', 'bar' => 'BarValue', 'baz' => 'BazValue'];
        $actualResult = $view->_get('variables');
        $this->assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function assignMultipleCanOverridePreviouslyAssignedValues()
    {
        $view = $this->getAccessibleMock(Mvc\View\AbstractView::class, ['setControllerContext', 'render']);
        $view->assign('foo', 'FooValue');
        $view->assignMultiple(['foo' => 'FooValueOverridden', 'bar' => 'BarValue']);

        $expectedResult = ['foo' => 'FooValueOverridden', 'bar' => 'BarValue'];
        $actualResult = $view->_get('variables');
        $this->assertEquals($expectedResult, $actualResult);
    }
}
