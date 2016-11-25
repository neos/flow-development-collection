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

use Neos\Flow\Persistence\PersistenceManagerInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\TagBuilder;

require_once(__DIR__ . '/Fixtures/EmptySyntaxTreeNode.php');
require_once(__DIR__ . '/Fixtures/Fixture_UserDomainClass.php');
require_once(__DIR__ . '/FormFieldViewHelperBaseTestcase.php');

/**
 * Test for the "Select" Form view helper
 */
class SelectViewHelperTest extends \Neos\FluidAdaptor\Tests\Unit\ViewHelpers\Form\FormFieldViewHelperBaseTestcase
{
    /**
     * @var \Neos\FluidAdaptor\ViewHelpers\Form\SelectViewHelper
     */
    protected $viewHelper;

    public function setUp()
    {
        parent::setUp();
        $this->arguments['name'] = '';
        $this->arguments['sortByOptionLabel'] = false;
        $this->viewHelper = $this->getAccessibleMock(\Neos\FluidAdaptor\ViewHelpers\Form\SelectViewHelper::class, array('setErrorClassAttribute', 'registerFieldNameForFormTokenGeneration'));
    }

    /**
     * @test
     */
    public function selectCorrectlySetsTagName()
    {
        $this->tagBuilder->expects($this->any())->method('setTagName')->with('select');

        $this->arguments['options'] = array();
        $this->injectDependenciesIntoViewHelper($this->viewHelper);

        $this->viewHelper->initialize();
        $this->viewHelper->render();
    }

    /**
     * @test
     */
    public function selectCreatesExpectedOptions()
    {
        $this->tagBuilder->expects($this->once())->method('addAttribute')->with('name', 'myName');
        $this->viewHelper->expects($this->once())->method('registerFieldNameForFormTokenGeneration')->with('myName');
        $this->tagBuilder->expects($this->once())->method('setContent')->with('<option value="value1">label1</option>' . chr(10) . '<option value="value2" selected="selected">label2</option>' . chr(10));
        $this->tagBuilder->expects($this->once())->method('render');

        $this->arguments['options'] = array(
            'value1' => 'label1',
            'value2' => 'label2'
        );
        $this->arguments['value'] = 'value2';
        $this->arguments['name'] = 'myName';

        $this->injectDependenciesIntoViewHelper($this->viewHelper);
        $this->viewHelper->initialize();
        $this->viewHelper->render();
    }

    /**
     * @test
     */
    public function anEmptyOptionTagIsRenderedIfOptionsArrayIsEmptyToAssureXhtmlCompatibility()
    {
        $this->tagBuilder->expects($this->once())->method('addAttribute')->with('name', 'myName');
        $this->viewHelper->expects($this->once())->method('registerFieldNameForFormTokenGeneration')->with('myName');
        $this->tagBuilder->expects($this->once())->method('setContent')->with('<option value=""></option>' . chr(10));
        $this->tagBuilder->expects($this->once())->method('render');

        $this->arguments['options'] = array();
        $this->arguments['value'] = 'value2';
        $this->arguments['name'] = 'myName';
        $this->injectDependenciesIntoViewHelper($this->viewHelper);

        $this->viewHelper->initialize();
        $this->viewHelper->render();
    }

    /**
     * @test
     */
    public function orderOfOptionsIsNotAlteredByDefault()
    {
        $this->tagBuilder->expects($this->once())->method('addAttribute')->with('name', 'myName');
        $this->viewHelper->expects($this->once())->method('registerFieldNameForFormTokenGeneration')->with('myName');
        $this->tagBuilder->expects($this->once())->method('setContent')->with('<option value="value3">label3</option>' . chr(10) . '<option value="value1">label1</option>' . chr(10) . '<option value="value2" selected="selected">label2</option>' . chr(10));
        $this->tagBuilder->expects($this->once())->method('render');

        $this->arguments['options'] = array(
            'value3' => 'label3',
            'value1' => 'label1',
            'value2' => 'label2'
        );

        $this->arguments['value'] = 'value2';
        $this->arguments['name'] = 'myName';

        $this->injectDependenciesIntoViewHelper($this->viewHelper);

        $this->viewHelper->initialize();
        $this->viewHelper->render();
    }

    /**
     * @test
     */
    public function optionsAreSortedByLabelIfSortByOptionLabelIsSet()
    {
        $this->tagBuilder->expects($this->once())->method('addAttribute')->with('name', 'myName');
        $this->viewHelper->expects($this->once())->method('registerFieldNameForFormTokenGeneration')->with('myName');
        $this->tagBuilder->expects($this->once())->method('setContent')->with('<option value="value1">label1</option>' . chr(10) . '<option value="value2" selected="selected">label2</option>' . chr(10) . '<option value="value3">label3</option>' . chr(10));
        $this->tagBuilder->expects($this->once())->method('render');

        $this->arguments['options'] = array(
            'value3' => 'label3',
            'value1' => 'label1',
            'value2' => 'label2'
        );

        $this->arguments['value'] = 'value2';
        $this->arguments['name'] = 'myName';
        $this->arguments['sortByOptionLabel'] = true;

        $this->injectDependenciesIntoViewHelper($this->viewHelper);

        $this->viewHelper->initialize();
        $this->viewHelper->render();
    }

    /**
     * @test
     */
    public function multipleSelectCreatesExpectedOptions()
    {
        $this->tagBuilder = new TagBuilder();

        $this->arguments['options'] = array(
            'value1' => 'label1',
            'value2' => 'label2',
            'value3' => 'label3'
        );

        $this->arguments['value'] = array('value3', 'value1');
        $this->arguments['name'] = 'myName';
        $this->arguments['multiple'] = 'multiple';

        $this->injectDependenciesIntoViewHelper($this->viewHelper);

        $this->viewHelper->initializeArguments();
        $this->viewHelper->initialize();
        $result = $this->viewHelper->render();
        $expected = '<select multiple="multiple" name="myName[]"><option value="value1" selected="selected">label1</option>' . chr(10) .
            '<option value="value2">label2</option>' . chr(10) .
            '<option value="value3" selected="selected">label3</option>' . chr(10) .
            '</select>';
        $this->assertSame($expected, $result);
    }

    /**
     * @test
     */
    public function multipleSelectCreatesExpectedOptionsInObjectAccessorMode()
    {
        $this->tagBuilder = new TagBuilder();

        $user = new \Neos\FluidAdaptor\ViewHelpers\Fixtures\UserDomainClass(1, 'Sebastian', 'Düvel');

        $this->viewHelperVariableContainerData = array(
            \Neos\FluidAdaptor\ViewHelpers\FormViewHelper::class => array(
                'formObjectName' => 'someFormObjectName',
                'formObject' => $user,
            )
        );

        $this->arguments['options'] = array(
            'value1' => 'label1',
            'value2' => 'label2',
            'value3' => 'label3'
        );
        $this->arguments['property'] = 'interests';
        $this->arguments['multiple'] = 'multiple';
        $this->arguments['selectAllByDefault'] = null;

        /** @var PersistenceManagerInterface|\PHPUnit_Framework_MockObject_MockObject $mockPersistenceManager */
        $mockPersistenceManager = $this->createMock(\Neos\Flow\Persistence\PersistenceManagerInterface::class);
        $mockPersistenceManager->expects($this->any())->method('getIdentifierByObject')->with($user->getInterests())->will($this->returnValue(null));
        $this->viewHelper->injectPersistenceManager($mockPersistenceManager);

        $this->injectDependenciesIntoViewHelper($this->viewHelper);

        $this->viewHelper->initializeArguments();
        $this->viewHelper->initialize();
        $result = $this->viewHelper->render();
        $expected = '<select multiple="multiple" name="someFormObjectName[interests][]"><option value="value1" selected="selected">label1</option>' . chr(10) .
            '<option value="value2">label2</option>' . chr(10) .
            '<option value="value3" selected="selected">label3</option>' . chr(10) .
            '</select>';
        $this->assertSame($expected, $result);
    }

    /**
     * @test
     */
    public function selectOnDomainObjectsCreatesExpectedOptions()
    {
        $mockPersistenceManager = $this->createMock(\Neos\Flow\Persistence\PersistenceManagerInterface::class);
        $mockPersistenceManager->expects($this->any())->method('getIdentifierByObject')->will($this->returnValue(2));
        $this->viewHelper->injectPersistenceManager($mockPersistenceManager);

        $this->tagBuilder->expects($this->once())->method('addAttribute')->with('name', 'myName[__identity]');
        $this->viewHelper->expects($this->once())->method('registerFieldNameForFormTokenGeneration')->with('myName[__identity]');
        $this->tagBuilder->expects($this->once())->method('setContent')->with('<option value="1">Ingmar</option>' . chr(10) . '<option value="2" selected="selected">Sebastian</option>' . chr(10) . '<option value="3">Robert</option>' . chr(10));
        $this->tagBuilder->expects($this->once())->method('render');

        $user_is = new \Neos\FluidAdaptor\ViewHelpers\Fixtures\UserDomainClass(1, 'Ingmar', 'Schlecht');
        $user_sk = new \Neos\FluidAdaptor\ViewHelpers\Fixtures\UserDomainClass(2, 'Sebastian', 'Kurfuerst');
        $user_rl = new \Neos\FluidAdaptor\ViewHelpers\Fixtures\UserDomainClass(3, 'Robert', 'Lemke');

        $this->arguments['options'] = array(
            $user_is,
            $user_sk,
            $user_rl
        );

        $this->arguments['value'] = $user_sk;
        $this->arguments['optionValueField'] = 'id';
        $this->arguments['optionLabelField'] = 'firstName';
        $this->arguments['name'] = 'myName';
        $this->injectDependenciesIntoViewHelper($this->viewHelper);

        $this->viewHelper->initialize();
        $this->viewHelper->render();
    }

    /**
     * @test
     */
    public function multipleSelectOnDomainObjectsCreatesExpectedOptions()
    {
        $this->tagBuilder = new TagBuilder();
        $this->viewHelper->expects($this->exactly(3))->method('registerFieldNameForFormTokenGeneration')->with('myName[]');

        $user_is = new \Neos\FluidAdaptor\ViewHelpers\Fixtures\UserDomainClass(1, 'Ingmar', 'Schlecht');
        $user_sk = new \Neos\FluidAdaptor\ViewHelpers\Fixtures\UserDomainClass(2, 'Sebastian', 'Kurfuerst');
        $user_rl = new \Neos\FluidAdaptor\ViewHelpers\Fixtures\UserDomainClass(3, 'Robert', 'Lemke');

        $this->arguments['options'] = array(
            $user_is,
            $user_sk,
            $user_rl
        );
        $this->arguments['value'] = array($user_rl, $user_is);
        $this->arguments['optionValueField'] = 'id';
        $this->arguments['optionLabelField'] = 'lastName';
        $this->arguments['name'] = 'myName';
        $this->arguments['multiple'] = 'multiple';

        $this->injectDependenciesIntoViewHelper($this->viewHelper);

        $this->viewHelper->initializeArguments();
        $this->viewHelper->initialize();
        $actual = $this->viewHelper->render();

        $expected = '<select multiple="multiple" name="myName[]"><option value="1" selected="selected">Schlecht</option>' . chr(10) .
            '<option value="2">Kurfuerst</option>' . chr(10) .
            '<option value="3" selected="selected">Lemke</option>' . chr(10) .
            '</select>';
        $this->assertSame($expected, $actual);
    }

    /**
     * @test
     */
    public function multipleSelectOnDomainObjectsCreatesExpectedOptionsWithoutOptionValueField()
    {
        $mockPersistenceManager = $this->createMock(\Neos\Flow\Persistence\PersistenceManagerInterface::class);
        $mockPersistenceManager->expects($this->any())->method('getIdentifierByObject')->will($this->returnCallback(
            function ($object) {
                return $object->getId();
            }
        ));
        $this->viewHelper->injectPersistenceManager($mockPersistenceManager);

        $this->tagBuilder = new TagBuilder();
        $this->viewHelper->expects($this->exactly(3))->method('registerFieldNameForFormTokenGeneration')->with('myName[]');

        $user_is = new \Neos\FluidAdaptor\ViewHelpers\Fixtures\UserDomainClass(1, 'Ingmar', 'Schlecht');
        $user_sk = new \Neos\FluidAdaptor\ViewHelpers\Fixtures\UserDomainClass(2, 'Sebastian', 'Kurfuerst');
        $user_rl = new \Neos\FluidAdaptor\ViewHelpers\Fixtures\UserDomainClass(3, 'Robert', 'Lemke');

        $this->arguments['options'] = array($user_is,$user_sk,$user_rl);
        $this->arguments['value'] = array($user_rl, $user_is);
        $this->arguments['optionLabelField'] = 'lastName';
        $this->arguments['name'] = 'myName';
        $this->arguments['multiple'] = 'multiple';

        $this->injectDependenciesIntoViewHelper($this->viewHelper);

        $this->viewHelper->initializeArguments();
        $this->viewHelper->initialize();
        $actual = $this->viewHelper->render();

        $expected = '<select multiple="multiple" name="myName[]">' .
            '<option value="1" selected="selected">Schlecht</option>' . chr(10) .
            '<option value="2">Kurfuerst</option>' . chr(10) .
            '<option value="3" selected="selected">Lemke</option>' . chr(10) .
            '</select>';
        $this->assertSame($expected, $actual);
    }

    /**
     * @test
     */
    public function selectWithoutFurtherConfigurationOnDomainObjectsUsesUuidForValueAndLabel()
    {
        $mockPersistenceManager = $this->createMock(\Neos\Flow\Persistence\PersistenceManagerInterface::class);
        $mockPersistenceManager->expects($this->any())->method('getIdentifierByObject')->will($this->returnValue('fakeUUID'));
        $this->viewHelper->injectPersistenceManager($mockPersistenceManager);

        $this->tagBuilder->expects($this->once())->method('addAttribute')->with('name', 'myName');
        $this->viewHelper->expects($this->once())->method('registerFieldNameForFormTokenGeneration')->with('myName');
        $this->tagBuilder->expects($this->once())->method('setContent')->with('<option value="fakeUUID">fakeUUID</option>' . chr(10));
        $this->tagBuilder->expects($this->once())->method('render');

        $user = new \Neos\FluidAdaptor\ViewHelpers\Fixtures\UserDomainClass(1, 'Ingmar', 'Schlecht');

        $this->arguments['options'] = array(
            $user
        );
        $this->arguments['name'] = 'myName';
        $this->injectDependenciesIntoViewHelper($this->viewHelper);

        $this->viewHelper->initialize();
        $this->viewHelper->render();
    }

    /**
     * @test
     */
    public function selectWithoutFurtherConfigurationOnDomainObjectsUsesToStringForLabelIfAvailable()
    {
        $mockPersistenceManager = $this->createMock(\Neos\Flow\Persistence\PersistenceManagerInterface::class);
        $mockPersistenceManager->expects($this->any())->method('getIdentifierByObject')->will($this->returnValue('fakeUUID'));
        $this->viewHelper->injectPersistenceManager($mockPersistenceManager);

        $this->tagBuilder->expects($this->once())->method('addAttribute')->with('name', 'myName');
        $this->viewHelper->expects($this->once())->method('registerFieldNameForFormTokenGeneration')->with('myName');
        $this->tagBuilder->expects($this->once())->method('setContent')->with('<option value="fakeUUID">toStringResult</option>' . chr(10));
        $this->tagBuilder->expects($this->once())->method('render');

        $user = $this->getMockBuilder(\Neos\FluidAdaptor\ViewHelpers\Fixtures\UserDomainClass::class)->setMethods(array('__toString'))->setConstructorArgs(array(1, 'Ingmar', 'Schlecht'))->getMock();
        $user->expects($this->atLeastOnce())->method('__toString')->will($this->returnValue('toStringResult'));

        $this->arguments['options'] = array(
            $user
        );
        $this->arguments['name'] = 'myName';
        $this->injectDependenciesIntoViewHelper($this->viewHelper);

        $this->viewHelper->initialize();
        $this->viewHelper->render();
    }

    /**
     * @test
     * @expectedException \Neos\FluidAdaptor\Core\ViewHelper\Exception
     */
    public function selectOnDomainObjectsThrowsExceptionIfNoValueCanBeFound()
    {
        $mockPersistenceManager = $this->createMock(\Neos\Flow\Persistence\PersistenceManagerInterface::class);
        $mockPersistenceManager->expects($this->any())->method('getIdentifierByObject')->will($this->returnValue(null));
        $this->viewHelper->injectPersistenceManager($mockPersistenceManager);

        $user = new \Neos\FluidAdaptor\ViewHelpers\Fixtures\UserDomainClass(1, 'Ingmar', 'Schlecht');

        $this->arguments['options'] = array(
            $user
        );
        $this->arguments['name'] = 'myName';
        $this->injectDependenciesIntoViewHelper($this->viewHelper);

        $this->viewHelper->initialize();
        $this->viewHelper->render();
    }

    /**
     * @test
     */
    public function renderCallsSetErrorClassAttribute()
    {
        $this->arguments['options'] = array();

        $this->injectDependenciesIntoViewHelper($this->viewHelper);

        $this->viewHelper->expects($this->once())->method('setErrorClassAttribute');
        $this->viewHelper->render();
    }

    /**
     * @test
     */
    public function allOptionsAreSelectedIfSelectAllIsTrue()
    {
        $this->tagBuilder->expects($this->once())->method('setContent')->with('<option value="value1" selected="selected">label1</option>' . chr(10) . '<option value="value2" selected="selected">label2</option>' . chr(10) . '<option value="value3" selected="selected">label3</option>' . chr(10));

        $this->arguments['options'] = array(
            'value1' => 'label1',
            'value2' => 'label2',
            'value3' => 'label3'
        );
        $this->arguments['name'] = 'myName';
        $this->arguments['multiple'] = 'multiple';
        $this->arguments['selectAllByDefault'] = true;

        $this->injectDependenciesIntoViewHelper($this->viewHelper);

        $this->viewHelper->initialize();
        $this->viewHelper->render();
    }

    /**
     * @test
     */
    public function selectAllHasNoEffectIfValueIsSet()
    {
        $this->tagBuilder->expects($this->once())->method('setContent')->with('<option value="value1" selected="selected">label1</option>' . chr(10) . '<option value="value2" selected="selected">label2</option>' . chr(10) . '<option value="value3">label3</option>' . chr(10));

        $this->arguments['options'] = array(
            'value1' => 'label1',
            'value2' => 'label2',
            'value3' => 'label3'
        );
        $this->arguments['value'] = array('value2', 'value1');
        $this->arguments['name'] = 'myName';
        $this->arguments['multiple'] = 'multiple';
        $this->arguments['selectAllByDefault'] = true;

        $this->injectDependenciesIntoViewHelper($this->viewHelper);

        $this->viewHelper->initialize();
        $this->viewHelper->render();
    }

    /**
     * @test
     */
    public function translateLabelIsCalledIfTranslateArgumentIsGiven()
    {
        $this->arguments['options'] = array('foo' => 'bar');
        $this->arguments['translate'] = array('by' => 'id');
        $viewHelper = $this->getAccessibleMock(\Neos\FluidAdaptor\ViewHelpers\Form\SelectViewHelper::class, array('getTranslatedLabel', 'setErrorClassAttribute', 'registerFieldNameForFormTokenGeneration'));
        $this->injectDependenciesIntoViewHelper($viewHelper);

        $viewHelper->expects($this->once())->method('getTranslatedLabel')->with('foo', 'bar');
        $viewHelper->render();
    }

    /**
     * @test
     */
    public function translateByIdAskForTranslationOfValueById()
    {
        $this->arguments['translate'] = array('by' => 'id');
        $this->injectDependenciesIntoViewHelper($this->viewHelper);

        $mockTranslator = $this->createMock(\Neos\Flow\I18n\Translator::class);
        $mockTranslator->expects($this->once())->method('translateById')->with('value1', array(), null, null, 'Main', '');
        $this->viewHelper->_set('translator', $mockTranslator);
        $this->viewHelper->_call('getTranslatedLabel', 'value1', 'label1');
    }

    /**
     * @test
     */
    public function translateByLabelAskForTranslationOfLabelByLabel()
    {
        $this->arguments['translate'] = array('by' => 'label');
        $this->injectDependenciesIntoViewHelper($this->viewHelper);

        $mockTranslator = $this->createMock(\Neos\Flow\I18n\Translator::class);
        $mockTranslator->expects($this->once())->method('translateByOriginalLabel')->with('label1', array(), null, null, 'Main', '');
        $this->viewHelper->_set('translator', $mockTranslator);
        $this->viewHelper->_call('getTranslatedLabel', 'value1', 'label1');
    }

    /**
     * @test
     */
    public function translateByLabelUsingValueUsesValue()
    {
        $this->arguments['translate'] = array('by' => 'label', 'using' => 'value');
        $this->injectDependenciesIntoViewHelper($this->viewHelper);

        $mockTranslator = $this->createMock(\Neos\Flow\I18n\Translator::class);
        $mockTranslator->expects($this->once())->method('translateByOriginalLabel')->with('value1', array(), null, null, 'Main', '');
        $this->viewHelper->_set('translator', $mockTranslator);
        $this->viewHelper->_call('getTranslatedLabel', 'value1', 'label1');
    }

    /**
     * @test
     */
    public function translateByIdUsingLabelUsesLabel()
    {
        $this->arguments['translate'] = array('by' => 'id', 'using' => 'label');
        $this->injectDependenciesIntoViewHelper($this->viewHelper);

        $mockTranslator = $this->createMock(\Neos\Flow\I18n\Translator::class);
        $mockTranslator->expects($this->once())->method('translateById')->with('label1', array(), null, null, 'Main', '');
        $this->viewHelper->_set('translator', $mockTranslator);
        $this->viewHelper->_call('getTranslatedLabel', 'value1', 'label1');
    }

    /**
     * @test
     */
    public function translateOptionsAreObserved()
    {
        $this->arguments['translate'] = array('by' => 'id', 'using' => 'label', 'locale' => 'dk', 'source' => 'WeirdMessageCatalog', 'package' => 'Foo.Bar', 'prefix' => 'somePrefix.');
        $this->injectDependenciesIntoViewHelper($this->viewHelper);

        $mockTranslator = $this->createMock(\Neos\Flow\I18n\Translator::class);
        $mockTranslator->expects($this->once())->method('translateById')->with('somePrefix.label1', array(), null, new \Neos\Flow\I18n\Locale('dk'), 'WeirdMessageCatalog', 'Foo.Bar');
        $this->viewHelper->_set('translator', $mockTranslator);
        $this->viewHelper->_call('getTranslatedLabel', 'value1', 'label1');
    }

    /**
     * @test
     * @expectedException \Neos\FluidAdaptor\Core\ViewHelper\Exception
     */
    public function getTranslatedLabelThrowsExceptionForInvalidLocales()
    {
        $this->arguments['translate'] = array('locale' => 'invalid-locale');
        $this->injectDependenciesIntoViewHelper($this->viewHelper);

        $this->viewHelper->_call('getTranslatedLabel', 'value1', 'label1');
    }

    /**
     * @test
     * @expectedException \Neos\FluidAdaptor\Core\ViewHelper\Exception
     */
    public function getTranslatedLabelThrowsExceptionForUnknownTranslateBy()
    {
        $this->arguments['translate'] = array('by' => 'foo');
        $this->injectDependenciesIntoViewHelper($this->viewHelper);

        $this->viewHelper->_call('getTranslatedLabel', 'value1', 'label1');
    }

    public function getTranslatedLabelDataProvider()
    {
        return [

            ## translate by id

            # using value
            ['by' => 'id', 'using' => 'value', 'translatedId' => 'Translated id', 'translatedLabel' => 'Translated label', 'expectedResult' => 'Translated id'],
            ['by' => 'id', 'using' => 'value', 'translatedId' => 'Translated id', 'translatedLabel' => null, 'expectedResult' => 'Translated id'],
            ['by' => 'id', 'using' => 'value', 'translatedId' => null, 'translatedLabel' => 'Translated label', 'expectedResult' => 'Some label'],
            ['by' => 'id', 'using' => 'value', 'translatedId' => null, 'translatedLabel' => null, 'expectedResult' => 'Some label'],

            # using label
            ['by' => 'id', 'using' => 'label', 'translatedId' => 'Translated id', 'translatedLabel' => 'Translated label', 'expectedResult' => 'Translated id'],
            ['by' => 'id', 'using' => 'label', 'translatedId' => 'Translated id', 'translatedLabel' => null, 'expectedResult' => 'Translated id'],
            ['by' => 'id', 'using' => 'label', 'translatedId' => null, 'translatedLabel' => 'Translated label', 'expectedResult' => 'Some label'],
            ['by' => 'id', 'using' => 'label', 'translatedId' => null, 'translatedLabel' => null, 'expectedResult' => 'Some label'],

            ## translate by label

            # using value
            ['by' => 'label', 'using' => 'value', 'translatedId' => 'Translated id', 'translatedLabel' => 'Translated label', 'expectedResult' => 'Translated label'],
            ['by' => 'label', 'using' => 'value', 'translatedId' => 'Translated id', 'translatedLabel' => null, 'expectedResult' => 'someValue'],
            ['by' => 'label', 'using' => 'value', 'translatedId' => null, 'translatedLabel' => 'Translated label', 'expectedResult' => 'Translated label'],
            ['by' => 'label', 'using' => 'value', 'translatedId' => null, 'translatedLabel' => null, 'expectedResult' => 'someValue'],

            # using label
            ['by' => 'label', 'using' => 'label', 'translatedId' => 'Translated id', 'translatedLabel' => 'Translated label', 'expectedResult' => 'Translated label'],
            ['by' => 'label', 'using' => 'label', 'translatedId' => 'Translated id', 'translatedLabel' => null, 'expectedResult' => 'Some label'],
            ['by' => 'label', 'using' => 'label', 'translatedId' => null, 'translatedLabel' => 'Translated label', 'expectedResult' => 'Translated label'],
            ['by' => 'label', 'using' => 'label', 'translatedId' => null, 'translatedLabel' => null, 'expectedResult' => 'Some label'],
        ];
    }

    /**
     * @test
     * @dataProvider getTranslatedLabelDataProvider
     * @param string $by
     * @param string $using
     * @param string $translatedId
     * @param string $translatedLabel
     * @param string $expectedResult
     */
    public function getTranslatedLabelTests($by, $using, $translatedId, $translatedLabel, $expectedResult)
    {
        $this->arguments['translate'] = ['by' => $by, 'using' => $using, 'prefix' => 'somePrefix.'];
        $this->injectDependenciesIntoViewHelper($this->viewHelper);

        $mockTranslator = $this->createMock(\Neos\Flow\I18n\Translator::class);
        if ($by === 'label') {
            $mockTranslator->expects($this->once())->method('translateByOriginalLabel')->will($this->returnCallback(function ($label) use ($translatedLabel) {
                return $translatedLabel !== null ? $translatedLabel : $label;
            }));
        } else {
            $mockTranslator->expects($this->once())->method('translateById')->will($this->returnValue($translatedId));
        }
        $this->inject($this->viewHelper, 'translator', $mockTranslator);

        $actualResult = $this->viewHelper->_call('getTranslatedLabel', 'someValue', 'Some label');
        $this->assertSame($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function optionsContainPrependedItemWithEmptyValueIfPrependOptionLabelIsSet()
    {
        $this->tagBuilder->expects($this->once())->method('addAttribute')->with('name', 'myName');
        $this->viewHelper->expects($this->once())->method('registerFieldNameForFormTokenGeneration')->with('myName');
        $this->tagBuilder->expects($this->once())->method('setContent')->with('<option value="">please choose</option>' . chr(10) . '<option value="value1">label1</option>' . chr(10) . '<option value="value2">label2</option>' . chr(10) . '<option value="value3">label3</option>' . chr(10));
        $this->tagBuilder->expects($this->once())->method('render');
        $this->arguments['options'] = array(
            'value1' => 'label1',
            'value2' => 'label2',
            'value3' => 'label3'
        );
        $this->arguments['name'] = 'myName';
        $this->arguments['prependOptionLabel'] = 'please choose';
        $this->injectDependenciesIntoViewHelper($this->viewHelper);
        $this->viewHelper->initialize();
        $this->viewHelper->render();
    }

    /**
     * @test
     */
    public function optionsContainPrependedItemWithCorrectValueIfPrependOptionLabelAndPrependOptionValueAreSet()
    {
        $this->tagBuilder->expects($this->once())->method('addAttribute')->with('name', 'myName');
        $this->viewHelper->expects($this->once())->method('registerFieldNameForFormTokenGeneration')->with('myName');
        $this->tagBuilder->expects($this->once())->method('setContent')->with('<option value="-1">please choose</option>' . chr(10) . '<option value="value1">label1</option>' . chr(10) . '<option value="value2">label2</option>' . chr(10) . '<option value="value3">label3</option>' . chr(10));
        $this->tagBuilder->expects($this->once())->method('render');
        $this->arguments['options'] = array(
            'value1' => 'label1',
            'value2' => 'label2',
            'value3' => 'label3'
        );
        $this->arguments['name'] = 'myName';
        $this->arguments['prependOptionLabel'] = 'please choose';
        $this->arguments['prependOptionValue'] = '-1';
        $this->injectDependenciesIntoViewHelper($this->viewHelper);
        $this->viewHelper->initialize();
        $this->viewHelper->render();
    }

    /**
     * @test
     */
    public function prependedOptionLabelIsTranslatedIfTranslateArgumentIsSet()
    {
        $this->tagBuilder->expects($this->once())->method('addAttribute')->with('name', 'myName');
        $this->viewHelper->expects($this->once())->method('registerFieldNameForFormTokenGeneration')->with('myName');
        $this->tagBuilder->expects($this->once())->method('setContent')->with('<option value="">translated label</option>' . chr(10));
        $this->tagBuilder->expects($this->once())->method('render');
        $this->arguments['options'] = array();
        $this->arguments['name'] = 'myName';
        $this->arguments['prependOptionLabel'] = 'select';
        $this->arguments['translate'] = array('by' => 'id', 'using' => 'label');

        $mockTranslator = $this->createMock(\Neos\Flow\I18n\Translator::class);
        $mockTranslator->expects($this->at(0))->method('translateById')->with('select', array(), null, null, 'Main', '')->will($this->returnValue('translated label'));
        $this->viewHelper->_set('translator', $mockTranslator);

        $this->injectDependenciesIntoViewHelper($this->viewHelper);
        $this->viewHelper->initialize();
        $this->viewHelper->render();
    }
}
