<?php
namespace TYPO3\Flow\Tests\Unit\Mvc\View;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

/**
 * Testcase for the MVC AbstractView
 *
 */
class AbstractViewTest extends \TYPO3\Flow\Tests\UnitTestCase
{
    /**
     * @test
     */
    public function assignAddsValueToInternalVariableCollection()
    {
        $view = $this->getAccessibleMock(\TYPO3\Flow\Mvc\View\AbstractView::class, array('setControllerContext', 'render'));
        $view
            ->assign('foo', 'FooValue')
            ->assign('bar', 'BarValue');

        $expectedResult = array('foo' => 'FooValue', 'bar' => 'BarValue');
        $actualResult = $view->_get('variables');
        $this->assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function assignCanOverridePreviouslyAssignedValues()
    {
        $view = $this->getAccessibleMock(\TYPO3\Flow\Mvc\View\AbstractView::class, array('setControllerContext', 'render'));
        $view->assign('foo', 'FooValue');
        $view->assign('foo', 'FooValueOverridden');

        $expectedResult = array('foo' => 'FooValueOverridden');
        $actualResult = $view->_get('variables');
        $this->assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function assignMultipleAddsValuesToInternalVariableCollection()
    {
        $view = $this->getAccessibleMock(\TYPO3\Flow\Mvc\View\AbstractView::class, array('setControllerContext', 'render'));
        $view
            ->assignMultiple(array('foo' => 'FooValue', 'bar' => 'BarValue'))
            ->assignMultiple(array('baz' => 'BazValue'));

        $expectedResult = array('foo' => 'FooValue', 'bar' => 'BarValue', 'baz' => 'BazValue');
        $actualResult = $view->_get('variables');
        $this->assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function assignMultipleCanOverridePreviouslyAssignedValues()
    {
        $view = $this->getAccessibleMock(\TYPO3\Flow\Mvc\View\AbstractView::class, array('setControllerContext', 'render'));
        $view->assign('foo', 'FooValue');
        $view->assignMultiple(array('foo' => 'FooValueOverridden', 'bar' => 'BarValue'));

        $expectedResult = array('foo' => 'FooValueOverridden', 'bar' => 'BarValue');
        $actualResult = $view->_get('variables');
        $this->assertEquals($expectedResult, $actualResult);
    }
}
