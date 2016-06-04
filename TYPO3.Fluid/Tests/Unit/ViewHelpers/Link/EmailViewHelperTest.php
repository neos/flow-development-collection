<?php
namespace TYPO3\Fluid\Tests\Unit\ViewHelpers\Link;

/*
 * This file is part of the TYPO3.Fluid package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

require_once(__DIR__ . '/../ViewHelperBaseTestcase.php');

/**
 */
class EmailViewHelperTest extends \TYPO3\Fluid\ViewHelpers\ViewHelperBaseTestcase
{
    /**
     * var \TYPO3\Fluid\ViewHelpers\Link\EmailViewHelper
     */
    protected $viewHelper;

    public function setUp()
    {
        parent::setUp();
        $this->viewHelper = $this->getAccessibleMock('TYPO3\Fluid\ViewHelpers\Link\EmailViewHelper', array('renderChildren'));
        $this->injectDependenciesIntoViewHelper($this->viewHelper);
        $this->viewHelper->initializeArguments();
    }

    /**
     * @test
     */
    public function renderCorrectlySetsTagNameAndAttributesAndContent()
    {
        $mockTagBuilder = $this->createMock('TYPO3\Fluid\Core\ViewHelper\TagBuilder', array('setTagName', 'addAttribute', 'setContent'));
        $mockTagBuilder->expects($this->once())->method('setTagName')->with('a');
        $mockTagBuilder->expects($this->once())->method('addAttribute')->with('href', 'mailto:some@email.tld');
        $mockTagBuilder->expects($this->once())->method('setContent')->with('some content');
        $this->viewHelper->injectTagBuilder($mockTagBuilder);

        $this->viewHelper->expects($this->any())->method('renderChildren')->will($this->returnValue('some content'));

        $this->viewHelper->initialize();
        $this->viewHelper->render('some@email.tld');
    }

    /**
     * @test
     */
    public function renderSetsTagContentToEmailIfRenderChildrenReturnNull()
    {
        $mockTagBuilder = $this->createMock('TYPO3\Fluid\Core\ViewHelper\TagBuilder', array('setTagName', 'addAttribute', 'setContent'));
        $mockTagBuilder->expects($this->once())->method('setContent')->with('some@email.tld');
        $this->viewHelper->injectTagBuilder($mockTagBuilder);

        $this->viewHelper->expects($this->any())->method('renderChildren')->will($this->returnValue(null));

        $this->viewHelper->initialize();
        $this->viewHelper->render('some@email.tld');
    }
}
