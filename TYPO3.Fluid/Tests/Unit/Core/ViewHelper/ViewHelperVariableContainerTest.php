<?php
namespace TYPO3\Fluid\Tests\Unit\Core\ViewHelper;

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
use TYPO3\Fluid\Core\ViewHelper\ViewHelperVariableContainer;

require_once(__DIR__ . '/../Fixtures/TestViewHelper.php');

/**
 * Testcase for AbstractViewHelper
 */
class ViewHelperVariableContainerTest extends UnitTestCase
{
    /**
     * @var ViewHelperVariableContainer
     */
    protected $viewHelperVariableContainer;

    protected function setUp()
    {
        $this->viewHelperVariableContainer = new ViewHelperVariableContainer();
    }

    /**
     * @test
     */
    public function storedDataCanBeReadOutAgain()
    {
        $variable = 'Hello world';
        $this->assertFalse($this->viewHelperVariableContainer->exists('TYPO3\Fluid\ViewHelpers\TestViewHelper', 'test'));
        $this->viewHelperVariableContainer->add('TYPO3\Fluid\ViewHelpers\TestViewHelper', 'test', $variable);
        $this->assertTrue($this->viewHelperVariableContainer->exists('TYPO3\Fluid\ViewHelpers\TestViewHelper', 'test'));

        $this->assertEquals($variable, $this->viewHelperVariableContainer->get('TYPO3\Fluid\ViewHelpers\TestViewHelper', 'test'));
    }

    /**
     * @test
     * @expectedException \TYPO3\Fluid\Core\ViewHelper\Exception\InvalidVariableException
     */
    public function gettingNonNonExistentValueThrowsException()
    {
        $this->viewHelperVariableContainer->get('TYPO3\Fluid\ViewHelper\NonExistent', 'nonExistentKey');
    }

    /**
     * @test
     * @expectedException \TYPO3\Fluid\Core\ViewHelper\Exception\InvalidVariableException
     */
    public function settingKeyWhichIsAlreadyStoredThrowsException()
    {
        $this->viewHelperVariableContainer->add('TYPO3\Fluid\ViewHelper\NonExistent', 'nonExistentKey', 'value1');
        $this->viewHelperVariableContainer->add('TYPO3\Fluid\ViewHelper\NonExistent', 'nonExistentKey', 'value2');
    }

    /**
     * @test
     */
    public function addOrUpdateSetsAKeyIfItDoesNotExistYet()
    {
        $this->viewHelperVariableContainer->add('TYPO3\Fluid\ViewHelper\NonExistent', 'nonExistentKey', 'value1');
        $this->assertEquals($this->viewHelperVariableContainer->get('TYPO3\Fluid\ViewHelper\NonExistent', 'nonExistentKey'), 'value1');
    }

    /**
     * @test
     */
    public function addOrUpdateOverridesAnExistingKey()
    {
        $this->viewHelperVariableContainer->add('TYPO3\Fluid\ViewHelper\NonExistent', 'someKey', 'value1');
        $this->viewHelperVariableContainer->addOrUpdate('TYPO3\Fluid\ViewHelper\NonExistent', 'someKey', 'value2');
        $this->assertEquals($this->viewHelperVariableContainer->get('TYPO3\Fluid\ViewHelper\NonExistent', 'someKey'), 'value2');
    }

    /**
     * @test
     */
    public function aSetValueCanBeRemovedAgain()
    {
        $this->viewHelperVariableContainer->add('TYPO3\Fluid\ViewHelper\NonExistent', 'nonExistentKey', 'value1');
        $this->viewHelperVariableContainer->remove('TYPO3\Fluid\ViewHelper\NonExistent', 'nonExistentKey');
        $this->assertFalse($this->viewHelperVariableContainer->exists('TYPO3\Fluid\ViewHelper\NonExistent', 'nonExistentKey'));
    }

    /**
     * @test
     * @expectedException \TYPO3\Fluid\Core\ViewHelper\Exception\InvalidVariableException
     */
    public function removingNonExistentKeyThrowsException()
    {
        $this->viewHelperVariableContainer->remove('TYPO3\Fluid\ViewHelper\NonExistent', 'nonExistentKey');
    }

    /**
     * @test
     */
    public function existsReturnsFalseIfTheSpecifiedKeyDoesNotExist()
    {
        $this->assertFalse($this->viewHelperVariableContainer->exists('TYPO3\Fluid\ViewHelper\NonExistent', 'nonExistentKey'));
    }

    /**
     * @test
     */
    public function existsReturnsTrueIfTheSpecifiedKeyExists()
    {
        $this->viewHelperVariableContainer->add('TYPO3\Fluid\ViewHelper\NonExistent', 'someKey', 'someValue');
        $this->assertTrue($this->viewHelperVariableContainer->exists('TYPO3\Fluid\ViewHelper\NonExistent', 'someKey'));
    }

    /**
     * @test
     */
    public function existsReturnsTrueIfTheSpecifiedKeyExistsAndIsNull()
    {
        $this->viewHelperVariableContainer->add('TYPO3\Fluid\ViewHelper\NonExistent', 'someKey', null);
        $this->assertTrue($this->viewHelperVariableContainer->exists('TYPO3\Fluid\ViewHelper\NonExistent', 'someKey'));
    }

    /**
     * @test
     */
    public function viewCanBeReadOutAgain()
    {
        $view = $this->createMock('TYPO3\Fluid\View\AbstractTemplateView', array('getTemplateSource', 'getLayoutSource', 'getPartialSource', 'hasTemplate', 'canRender', 'getTemplateIdentifier', 'getLayoutIdentifier', 'getPartialIdentifier'));
        $this->viewHelperVariableContainer->setView($view);
        $this->assertSame($view, $this->viewHelperVariableContainer->getView());
    }
}
