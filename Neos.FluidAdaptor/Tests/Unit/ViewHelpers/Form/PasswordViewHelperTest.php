<?php
namespace Neos\FluidAdaptor\Tests\Unit\ViewHelpers\Form;

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

require_once(__DIR__ . '/Fixtures/EmptySyntaxTreeNode.php');
require_once(__DIR__ . '/Fixtures/Fixture_UserDomainClass.php');
require_once(__DIR__ . '/FormFieldViewHelperBaseTestcase.php');

/**
 * Test for the "Password" Form view helper
 */
class PasswordViewHelperTest extends \Neos\FluidAdaptor\Tests\Unit\ViewHelpers\Form\FormFieldViewHelperBaseTestcase
{
    /**
     * @var \Neos\FluidAdaptor\ViewHelpers\Form\PasswordViewHelper
     */
    protected $viewHelper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->viewHelper = $this->getAccessibleMock(\Neos\FluidAdaptor\ViewHelpers\Form\PasswordViewHelper::class, ['setErrorClassAttribute', 'registerFieldNameForFormTokenGeneration']);
        $this->arguments['name'] = '';
        $this->injectDependenciesIntoViewHelper($this->viewHelper);
    }

    /**
     * @test
     */
    public function renderCorrectlySetsTagName()
    {
        $mockTagBuilder = $this->createMock(TagBuilder::class);
        $mockTagBuilder->expects(self::atLeastOnce())->method('setTagName')->with('input');
        $this->viewHelper->injectTagBuilder($mockTagBuilder);

        $this->viewHelper->initialize();
        $this->viewHelper->render();
    }

    /**
     * @test
     */
    public function renderCorrectlySetsTypeNameAndValueAttributes()
    {
        $mockTagBuilder = $this->getMockBuilder(TagBuilder::class)->setMethods(['setContent', 'render', 'addAttribute'])->getMock();
        $mockTagBuilder->expects(self::at(0))->method('addAttribute')->with('type', 'password');
        $mockTagBuilder->expects(self::at(1))->method('addAttribute')->with('name', 'NameOfTextbox');
        $this->viewHelper->expects(self::once())->method('registerFieldNameForFormTokenGeneration')->with('NameOfTextbox');
        $mockTagBuilder->expects(self::at(2))->method('addAttribute')->with('value', 'Current value');
        $mockTagBuilder->expects(self::once())->method('render');
        $this->viewHelper->injectTagBuilder($mockTagBuilder);

        $arguments = [
            'name' => 'NameOfTextbox',
            'value' => 'Current value'
        ];
        $this->viewHelper->setArguments($arguments);

        $this->viewHelper->setViewHelperNode(new \Neos\FluidAdaptor\ViewHelpers\Fixtures\EmptySyntaxTreeNode());
        $this->viewHelper->initialize();
        $this->viewHelper->render();
    }

    /**
     * @test
     */
    public function renderCorrectlySetsRequiredAttribute()
    {
        $mockTagBuilder = $this->getMockBuilder(TagBuilder::class)->setMethods(['addAttribute', 'setContent', 'render'])->disableOriginalConstructor()->getMock();
        $mockTagBuilder->expects(self::at(0))->method('addAttribute')->with('type', 'password');
        $mockTagBuilder->expects(self::at(1))->method('addAttribute')->with('name', 'NameOfTextbox');
        $this->viewHelper->expects(self::once())->method('registerFieldNameForFormTokenGeneration')->with('NameOfTextbox');
        $mockTagBuilder->expects(self::at(2))->method('addAttribute')->with('value', 'Current value');
        $mockTagBuilder->expects(self::once())->method('render');
        $this->viewHelper->injectTagBuilder($mockTagBuilder);

        $arguments = [
            'name' => 'NameOfTextbox',
            'value' => 'Current value'
        ];

        $this->viewHelper->setViewHelperNode(new \Neos\FluidAdaptor\ViewHelpers\Fixtures\EmptySyntaxTreeNode());

        $this->viewHelper = $this->prepareArguments($this->viewHelper, $arguments);

        $this->viewHelper->render();
    }

    /**
     * @test
     */
    public function renderCallsSetErrorClassAttribute()
    {
        $this->viewHelper->expects(self::once())->method('setErrorClassAttribute');
        $this->viewHelper->render();
    }
}
