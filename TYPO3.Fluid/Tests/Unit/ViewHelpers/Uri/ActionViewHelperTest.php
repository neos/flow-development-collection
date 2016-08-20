<?php
namespace TYPO3\Fluid\Tests\Unit\ViewHelpers\Uri;

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
 * Testcase for the action uri view helper
 *
 */
class ActionViewHelperTest extends \TYPO3\Fluid\ViewHelpers\ViewHelperBaseTestcase
{
    /**
     * var \TYPO3\Fluid\ViewHelpers\Uri\ActionViewHelper
     */
    protected $viewHelper;

    public function setUp()
    {
        parent::setUp();
        $this->viewHelper = new \TYPO3\Fluid\ViewHelpers\Uri\ActionViewHelper();
        $this->injectDependenciesIntoViewHelper($this->viewHelper);
        $this->viewHelper->initializeArguments();
    }

    /**
     * @test
     */
    public function renderReturnsUriReturnedFromUriBuilder()
    {
        $this->uriBuilder->expects($this->any())->method('uriFor')->will($this->returnValue('some/uri'));

        $this->viewHelper->initialize();
        $actualResult = $this->viewHelper->render('index');

        $this->assertEquals('some/uri', $actualResult);
    }

    /**
     * @test
     */
    public function renderCorrectlyPassesDefaultArgumentsToUriBuilder()
    {
        $this->uriBuilder->expects($this->once())->method('setSection')->with('');
        $this->uriBuilder->expects($this->once())->method('setCreateAbsoluteUri')->with(false);
        $this->uriBuilder->expects($this->once())->method('setArguments')->with(array());
        $this->uriBuilder->expects($this->once())->method('setAddQueryString')->with(false);
        $this->uriBuilder->expects($this->once())->method('setArgumentsToBeExcludedFromQueryString')->with(array());
        $this->uriBuilder->expects($this->once())->method('setFormat')->with('');
        $this->uriBuilder->expects($this->once())->method('uriFor')->with('theActionName', array(), null, null, null);

        $this->viewHelper->initialize();
        $this->viewHelper->render('theActionName');
    }

    /**
     * @test
     */
    public function renderCorrectlyPassesAllArgumentsToUriBuilder()
    {
        $this->uriBuilder->expects($this->once())->method('setSection')->with('someSection');
        $this->uriBuilder->expects($this->once())->method('setCreateAbsoluteUri')->with(true);
        $this->uriBuilder->expects($this->once())->method('setArguments')->with(array('additional' => 'Parameters'));
        $this->uriBuilder->expects($this->once())->method('setAddQueryString')->with(true);
        $this->uriBuilder->expects($this->once())->method('setArgumentsToBeExcludedFromQueryString')->with(array('arguments' => 'toBeExcluded'));
        $this->uriBuilder->expects($this->once())->method('setFormat')->with('someFormat');
        $this->uriBuilder->expects($this->once())->method('uriFor')->with('someAction', array('some' => 'argument'), 'someController', 'somePackage', 'someSubpackage');

        $this->viewHelper->initialize();
        $this->viewHelper->render('someAction', array('some' => 'argument'), 'someController', 'somePackage', 'someSubpackage', 'someSection', 'someFormat', array('additional' => 'Parameters'), true, true, array('arguments' => 'toBeExcluded'));
    }

    /**
     * @test
     */
    public function renderThrowsViewHelperExceptionIfUriBuilderThrowsFlowException()
    {
        $this->uriBuilder->expects($this->any())->method('uriFor')->will($this->throwException(new \TYPO3\Flow\Exception('Mock Exception', 12345)));
        $this->viewHelper->initialize();
        try {
            $this->viewHelper->render('someAction');
        } catch (\TYPO3\Fluid\Core\ViewHelper\Exception $exception) {
        }
        $this->assertEquals(12345, $exception->getPrevious()->getCode());
    }

    /**
     * @test
     * @expectedException \TYPO3\Fluid\Core\ViewHelper\Exception
     */
    public function renderThrowsExceptionIfUseParentRequestIsSetAndTheCurrentRequestHasNoParentRequest()
    {
        $this->viewHelper->initialize();
        $this->viewHelper->render('someAction', array(), null, null, null, '', '', array(), false, false, array(), true);
    }

    /**
     * @test
     */
    public function renderUsesParentRequestIfUseParentRequestIsSet()
    {
        $viewHelper = new \TYPO3\Fluid\ViewHelpers\Uri\ActionViewHelper();

        $parentRequest = $this->getMockBuilder(\TYPO3\Flow\Mvc\ActionRequest::class)->disableOriginalConstructor()->getMock();

        $this->request = $this->getMockBuilder(\TYPO3\Flow\Mvc\ActionRequest::class)->disableOriginalConstructor()->getMock();
        $this->request->expects($this->atLeastOnce())->method('isMainRequest')->will($this->returnValue(false));
        $this->request->expects($this->atLeastOnce())->method('getParentRequest')->will($this->returnValue($parentRequest));

        $this->controllerContext = $this->getMockBuilder(\TYPO3\Flow\Mvc\Controller\ControllerContext::class)->disableOriginalConstructor()->getMock();
        $this->controllerContext->expects($this->any())->method('getUriBuilder')->will($this->returnValue($this->uriBuilder));
        $this->controllerContext->expects($this->any())->method('getRequest')->will($this->returnValue($this->request));

        $this->uriBuilder->expects($this->atLeastOnce())->method('setRequest')->with($parentRequest);

        $this->renderingContext->setControllerContext($this->controllerContext);
        $this->injectDependenciesIntoViewHelper($viewHelper);

        $viewHelper->render('someAction', array(), null, null, null, '', '', array(), false, false, array(), true);
    }
}
