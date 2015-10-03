<?php
namespace TYPO3\Fluid\Tests\Unit\ViewHelpers;

/*
 * This file is part of the TYPO3.Fluid package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\I18n\Locale;
use TYPO3\Flow\I18n\Translator;
use TYPO3\Fluid\ViewHelpers\TranslateViewHelper;
use TYPO3\Fluid\ViewHelpers\ViewHelperBaseTestcase;

require_once(__DIR__ . '/ViewHelperBaseTestcase.php');

/**
 * Test case for the Translate ViewHelper
 */
class TranslateViewHelperTest extends ViewHelperBaseTestcase
{
    /**
     * @var TranslateViewHelper
     */
    protected $translateViewHelper;

    /**
     * @var Locale
     */
    protected $dummyLocale;

    /**
     * @var Translator|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $mockTranslator;

    public function setUp()
    {
        parent::setUp();

        $this->translateViewHelper = $this->getAccessibleMock('TYPO3\Fluid\ViewHelpers\TranslateViewHelper', array('renderChildren'));

        $this->request->expects($this->any())->method('getControllerPackageKey')->will($this->returnValue('TYPO3.Fluid'));

        $this->dummyLocale = new Locale('de_DE');

        $this->mockTranslator = $this->getMockBuilder('TYPO3\Flow\I18n\Translator')->disableOriginalConstructor()->getMock();
        $this->inject($this->translateViewHelper, 'translator', $this->mockTranslator);

        $this->injectDependenciesIntoViewHelper($this->translateViewHelper);
    }

    /**
     * @test
     */
    public function viewHelperTranslatesByOriginalLabel()
    {
        $this->mockTranslator->expects($this->once())->method('translateByOriginalLabel', 'Untranslated Label', 'Main', 'TYPO3.Flow', array(), null, $this->dummyLocale)->will($this->returnValue('Translated Label'));

        $this->translateViewHelper->expects($this->once())->method('renderChildren')->will($this->returnValue('Untranslated Label'));

        $result = $this->translateViewHelper->render(null, null, array(), 'Main', null, null, 'de_DE');
        $this->assertEquals('Translated Label', $result);
    }

    /**
     * @test
     */
    public function viewHelperTranslatesById()
    {
        $this->mockTranslator->expects($this->once())->method('translateById', 'some.label', 'Main', 'TYPO3.Flow', array(), null, $this->dummyLocale)->will($this->returnValue('Translated Label'));

        $result = $this->translateViewHelper->render('some.label', null, array(), 'Main', null, null, 'de_DE');
        $this->assertEquals('Translated Label', $result);
    }

    /**
     * @test
     */
    public function viewHelperUsesValueIfIdIsNotFound()
    {
        $this->mockTranslator->expects($this->once())->method('translateById', 'some.label', 'Main', 'TYPO3.Flow', array(), null, $this->dummyLocale)->will($this->returnValue('some.label'));
        $this->mockTranslator->expects($this->once())->method('translateByOriginalLabel', 'Default from value', 'Main', 'TYPO3.Flow', array(), null, $this->dummyLocale)->will($this->returnValue('Default from value'));

        $this->translateViewHelper->expects($this->never())->method('renderChildren');

        $result = $this->translateViewHelper->render('some.label', 'Default from value', array(), 'Main', null, null, 'de_DE');
        $this->assertEquals('Default from value', $result);
    }

    /**
     * @test
     */
    public function viewHelperUsesRenderChildrenIfIdIsNotFound()
    {
        $this->mockTranslator->expects($this->once())->method('translateById', 'some.label', 'Main', 'TYPO3.Flow', array(), null, $this->dummyLocale)->will($this->returnValue('some.label'));
        $this->mockTranslator->expects($this->once())->method('translateByOriginalLabel', 'Default from renderChildren', 'Main', 'TYPO3.Flow', array(), null, $this->dummyLocale)->will($this->returnValue('Default from renderChildren'));

        $this->translateViewHelper->expects($this->once())->method('renderChildren')->will($this->returnValue('Default from renderChildren'));

        $result = $this->translateViewHelper->render('some.label', null, array(), 'Main', null, null, 'de_DE');
        $this->assertEquals('Default from renderChildren', $result);
    }

    /**
     * @test
     */
    public function viewHelperReturnsIdWhenRenderChildrenReturnsEmptyResultIfIdIsNotFound()
    {
        $this->mockTranslator->expects($this->once())->method('translateById', 'some.label', 'Main', 'TYPO3.Flow', array(), null, $this->dummyLocale)->will($this->returnValue('some.label'));

        $this->translateViewHelper->expects($this->once())->method('renderChildren')->will($this->returnValue(null));

        $result = $this->translateViewHelper->render('some.label', null, array(), 'Main', null, null, 'de_DE');
        $this->assertEquals('some.label', $result);
    }

    /**
     * @test
     * @expectedException \TYPO3\Fluid\Core\ViewHelper\Exception
     */
    public function renderThrowsExceptionIfGivenLocaleIdentifierIsInvalid()
    {
        $this->translateViewHelper->render('some.label', null, array(), 'Main', null, null, 'INVALIDLOCALE');
    }

    /**
     * @test
     * @expectedException \TYPO3\Fluid\Core\ViewHelper\Exception
     */
    public function renderThrowsExceptionIfNoPackageCouldBeResolved()
    {
        $mockRequest = $this->getMockBuilder('TYPO3\Flow\Mvc\ActionRequest')->disableOriginalConstructor()->getMock();
        $mockRequest->expects($this->any())->method('getControllerPackageKey')->will($this->returnValue(null));

        $mockControllerContext = $this->getMockBuilder('TYPO3\Flow\Mvc\Controller\ControllerContext')->disableOriginalConstructor()->getMock();
        $mockControllerContext->expects($this->any())->method('getRequest')->will($this->returnValue($mockRequest));

        $this->renderingContext->setControllerContext($mockControllerContext);

        $this->injectDependenciesIntoViewHelper($this->translateViewHelper);

        $this->translateViewHelper->render('some.label');
    }
}
