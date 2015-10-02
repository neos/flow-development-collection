<?php
namespace TYPO3\Fluid\Tests\Unit\View;

/*
 * This file is part of the TYPO3.Fluid package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */


use org\bovigo\vfs\vfsStreamWrapper;
use TYPO3\Flow\Mvc\ActionRequest;
use TYPO3\Flow\Mvc\Controller\ControllerContext;
use TYPO3\Flow\Tests\UnitTestCase;
use TYPO3\Fluid\View\StandaloneView;

/**
 * Testcase for the StandaloneView
 */
class StandaloneViewTest extends UnitTestCase
{
    /**
     * @var StandaloneView
     */
    protected $standaloneView;

    /**
     * @var ControllerContext
     */
    protected $mockControllerContext;

    /**
     * @var ActionRequest
     */
    protected $mockRequest;

    public function setUp()
    {
        $this->standaloneView = $this->getAccessibleMock('TYPO3\Fluid\View\StandaloneView', array('dummy'));

        $this->mockRequest = $this->getMockBuilder('TYPO3\Flow\Mvc\ActionRequest')->disableOriginalConstructor()->getMock();
        $this->mockControllerContext = $this->getMockBuilder('TYPO3\Flow\Mvc\Controller\ControllerContext')->disableOriginalConstructor()->getMock();
        $this->mockControllerContext->expects($this->any())->method('getRequest')->will($this->returnValue($this->mockRequest));
        $this->inject($this->standaloneView, 'controllerContext', $this->mockControllerContext);
    }

    /**
     * @test
     * @expectedException \TYPO3\Fluid\View\Exception\InvalidTemplateResourceException
     */
    public function getTemplateSourceThrowsExceptionIfSpecifiedTemplatePathAndFilenameDoesNotExist()
    {
        $this->standaloneView->setTemplatePathAndFilename(__DIR__ . '/NonExistingTemplate.txt');
        $this->standaloneView->_call('getTemplateSource');
    }

    /**
     * @test
     * @expectedException \TYPO3\Fluid\View\Exception\InvalidTemplateResourceException
     */
    public function getTemplateSourceThrowsExceptionIfSpecifiedTemplatePathAndFilenamePointsToADirectory()
    {
        $this->standaloneView->setTemplatePathAndFilename(__DIR__);
        $this->standaloneView->_call('getTemplateSource');
    }

    /**
     * @test
     * @expectedException \TYPO3\Fluid\View\Exception\InvalidTemplateResourceException
     */
    public function getLayoutPathAndFilenameThrowsExceptionIfSpecifiedLayoutRootPathIsNoDirectory()
    {
        vfsStreamWrapper::register();
        mkdir('vfs://MyLayouts');
        \file_put_contents('vfs://MyLayouts/NotAFolder', 'foo');
        $this->standaloneView->setLayoutRootPath('vfs://MyLayouts/NotAFolder');
        $this->standaloneView->_call('getLayoutPathAndFilename');
    }

    /**
     * @test
     * @expectedException \TYPO3\Fluid\View\Exception\InvalidTemplateResourceException
     */
    public function getLayoutPathAndFilenameThrowsExceptionIfLayoutFileIsADirectory()
    {
        vfsStreamWrapper::register();
        mkdir('vfs://MyLayouts/NotAFile');
        $this->standaloneView->setLayoutRootPath('vfs://MyLayouts');
        $this->standaloneView->_call('getLayoutPathAndFilename', 'NotAFile');
    }

    /**
     * @test
     * @expectedException \TYPO3\Fluid\View\Exception\InvalidTemplateResourceException
     */
    public function getPartialPathAndFilenameThrowsExceptionIfSpecifiedPartialRootPathIsNoDirectory()
    {
        vfsStreamWrapper::register();
        mkdir('vfs://MyPartials');
        \file_put_contents('vfs://MyPartials/NotAFolder', 'foo');
        $this->standaloneView->setPartialRootPath('vfs://MyPartials/NotAFolder');
        $this->standaloneView->_call('getPartialPathAndFilename', 'SomePartial');
    }

    /**
     * @test
     * @expectedException \TYPO3\Fluid\View\Exception\InvalidTemplateResourceException
     */
    public function getPartialPathAndFilenameThrowsExceptionIfPartialFileIsADirectory()
    {
        vfsStreamWrapper::register();
        mkdir('vfs://MyPartials/NotAFile');
        $this->standaloneView->setPartialRootPath('vfs://MyPartials');
        $this->standaloneView->_call('getPartialPathAndFilename', 'NotAFile');
    }
}
