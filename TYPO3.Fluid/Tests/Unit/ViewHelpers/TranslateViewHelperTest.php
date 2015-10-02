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

require_once(__DIR__ . '/ViewHelperBaseTestcase.php');

/**
 * Test case for the Translate ViewHelper
 */
class TranslateViewHelperTest extends \TYPO3\Fluid\ViewHelpers\ViewHelperBaseTestcase
{
    /**
     * @test
     */
    public function viewHelperTranslatesByOriginalLabel()
    {
        $dummyLocale = new \TYPO3\Flow\I18n\Locale('de_DE');

        $mockTranslator = $this->getMock('TYPO3\Flow\I18n\Translator');
        $mockTranslator->expects($this->once())->method('translateByOriginalLabel', 'Untranslated Label', 'Main', 'TYPO3.Flow', array(), null, $dummyLocale)->will($this->returnValue('Translated Label'));

        $viewHelper = $this->getAccessibleMock('TYPO3\Fluid\ViewHelpers\TranslateViewHelper', array('renderChildren'));
        $this->injectDependenciesIntoViewHelper($viewHelper);
        $viewHelper->expects($this->once())->method('renderChildren')->will($this->returnValue('Untranslated Label'));
        $viewHelper->_set('translator', $mockTranslator);

        $result = $viewHelper->render(null, null, array(), 'Main', null, 'de_DE');
        $this->assertEquals('Translated Label', $result);
    }

    /**
     * @test
     */
    public function viewHelperTranslatesById()
    {
        $dummyLocale = new \TYPO3\Flow\I18n\Locale('de_DE');

        $mockTranslator = $this->getMock('TYPO3\Flow\I18n\Translator');
        $mockTranslator->expects($this->once())->method('translateById', 'some.label', 'Main', 'TYPO3.Flow', array(), null, $dummyLocale)->will($this->returnValue('Translated Label'));

        $viewHelper = $this->getAccessibleMock('TYPO3\Fluid\ViewHelpers\TranslateViewHelper', array('renderChildren'));
        $this->injectDependenciesIntoViewHelper($viewHelper);
        $viewHelper->_set('translator', $mockTranslator);

        $result = $viewHelper->render('some.label', null, array(), 'Main', null, 'de_DE');
        $this->assertEquals('Translated Label', $result);
    }

    /**
     * @test
     */
    public function viewHelperUsesValueIfIdIsNotFound()
    {
        $dummyLocale = new \TYPO3\Flow\I18n\Locale('de_DE');

        $mockTranslator = $this->getMock('TYPO3\Flow\I18n\Translator');
        $mockTranslator->expects($this->once())->method('translateById', 'some.label', 'Main', 'TYPO3.Flow', array(), null, $dummyLocale)->will($this->returnValue('some.label'));
        $mockTranslator->expects($this->once())->method('translateByOriginalLabel', 'Default from value', 'Main', 'TYPO3.Flow', array(), null, $dummyLocale)->will($this->returnValue('Default from value'));

        $viewHelper = $this->getAccessibleMock('TYPO3\Fluid\ViewHelpers\TranslateViewHelper', array('renderChildren'));
        $this->injectDependenciesIntoViewHelper($viewHelper);
        $viewHelper->expects($this->never())->method('renderChildren');
        $viewHelper->_set('translator', $mockTranslator);

        $result = $viewHelper->render('some.label', 'Default from value', array(), 'Main', null, 'de_DE');
        $this->assertEquals('Default from value', $result);
    }

    /**
     * @test
     */
    public function viewHelperUsesRenderChildrenIfIdIsNotFound()
    {
        $dummyLocale = new \TYPO3\Flow\I18n\Locale('de_DE');

        $mockTranslator = $this->getMock('TYPO3\Flow\I18n\Translator');
        $mockTranslator->expects($this->once())->method('translateById', 'some.label', 'Main', 'TYPO3.Flow', array(), null, $dummyLocale)->will($this->returnValue('some.label'));
        $mockTranslator->expects($this->once())->method('translateByOriginalLabel', 'Default from renderChildren', 'Main', 'TYPO3.Flow', array(), null, $dummyLocale)->will($this->returnValue('Default from renderChildren'));

        $viewHelper = $this->getAccessibleMock('TYPO3\Fluid\ViewHelpers\TranslateViewHelper', array('renderChildren'));
        $this->injectDependenciesIntoViewHelper($viewHelper);
        $viewHelper->expects($this->once())->method('renderChildren')->will($this->returnValue('Default from renderChildren'));
        $viewHelper->_set('translator', $mockTranslator);

        $result = $viewHelper->render('some.label', null, array(), 'Main', null, 'de_DE');
        $this->assertEquals('Default from renderChildren', $result);
    }

    /**
     * @test
     */
    public function viewHelperReturnsIdWhenRenderChildrenReturnsEmptyResultIfIdIsNotFound()
    {
        $dummyLocale = new \TYPO3\Flow\I18n\Locale('de_DE');

        $mockTranslator = $this->getMock('TYPO3\Flow\I18n\Translator');
        $mockTranslator->expects($this->once())->method('translateById', 'some.label', 'Main', 'TYPO3.Flow', array(), null, $dummyLocale)->will($this->returnValue('some.label'));

        $viewHelper = $this->getAccessibleMock('TYPO3\Fluid\ViewHelpers\TranslateViewHelper', array('renderChildren'));
        $this->injectDependenciesIntoViewHelper($viewHelper);
        $viewHelper->expects($this->once())->method('renderChildren')->will($this->returnValue(null));
        $viewHelper->_set('translator', $mockTranslator);

        $result = $viewHelper->render('some.label', null, array(), 'Main', null, 'de_DE');
        $this->assertEquals('some.label', $result);
    }
}
