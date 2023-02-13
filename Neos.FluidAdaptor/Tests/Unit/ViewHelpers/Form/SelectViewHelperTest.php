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
use Neos\FluidAdaptor\Core\ViewHelper\Exception;
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

    protected function setUp(): void
    {
        parent::setUp();
        $this->arguments['name'] = '';
        $this->arguments['sortByOptionLabel'] = false;
        $this->viewHelper = $this->getAccessibleMock(\Neos\FluidAdaptor\ViewHelpers\Form\SelectViewHelper::class, ['setErrorClassAttribute', 'registerFieldNameForFormTokenGeneration']);
    }

    /**
     * @test
     */
    public function selectCorrectlySetsTagName()
    {
        $this->tagBuilder->expects(self::atLeastOnce())->method('setTagName')->with('select');

        $this->arguments['options'] = [];
        $this->injectDependenciesIntoViewHelper($this->viewHelper);

        $this->viewHelper->initialize();
        $this->viewHelper->render();
    }

    /**
     * @test
     */
    public function selectCreatesExpectedOptions()
    {
        $this->tagBuilder->expects(self::once())->method('addAttribute')->with('name', 'myName');
        $this->viewHelper->expects(self::once())->method('registerFieldNameForFormTokenGeneration')->with('myName');
        $this->tagBuilder->expects(self::once())->method('setContent')->with('<option value="value1">label1</option>' . chr(10) . '<option value="value2" selected="selected">label2</option>' . chr(10));
        $this->tagBuilder->expects(self::once())->method('render');

        $this->arguments['options'] = [
            'value1' => 'label1',
            'value2' => 'label2'
        ];
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
        $this->tagBuilder->expects(self::once())->method('addAttribute')->with('name', 'myName');
        $this->viewHelper->expects(self::once())->method('registerFieldNameForFormTokenGeneration')->with('myName');
        $this->tagBuilder->expects(self::once())->method('setContent')->with('<option value=""></option>' . chr(10));
        $this->tagBuilder->expects(self::once())->method('render');

        $this->arguments['options'] = [];
        $this->arguments['value'] = 'value2';
        $this->arguments['name'] = 'myName';
        $this->injectDependenciesIntoViewHelper($this->viewHelper);

        $this->viewHelper->initialize();
        $this->viewHelper->render();
    }

    /**
     * @test
     */
    public function selectCreatesExpectedOptionsWithArraysAndOptionValueFieldAndOptionLabelFieldSet()
    {
        $this->tagBuilder
            ->expects(static::once())
            ->method('setContent')
            ->with(
                '<option value="2"></option>' . chr(10)
                    . '<option value="-1">Bar</option>' . chr(10)
                    . '<option value="">Baz</option>' . chr(10)
                    . '<option value="1">Foo</option>' . chr(10)
            )
        ;

        $this->arguments['optionValueField'] = 'uid';
        $this->arguments['optionLabelField'] = 'title';
        $this->arguments['sortByOptionLabel'] = true;
        $this->arguments['options'] = [
            [
                'uid' => 1,
                'title' => 'Foo',
            ],
            [
                'uid' => -1,
                'title' => 'Bar',
            ],
            [
                'title' => 'Baz',
            ],
            [
                'uid' => '2',
            ],
        ];

        $this->injectDependenciesIntoViewHelper($this->viewHelper);

        $this->viewHelper->initialize();
        $this->viewHelper->render();
    }

    /**
     * @test
     */
    public function selectCreatesExpectedOptionsWithStdClassesAndOptionValueFieldAndOptionLabelFieldSet()
    {
        $this->tagBuilder
            ->expects(static::once())
            ->method('setContent')
            ->with(
                '<option value="2"></option>' . chr(10)
                    . '<option value="-1">Bar</option>' . chr(10)
                    . '<option value="">Baz</option>' . chr(10)
                    . '<option value="1">Foo</option>' . chr(10)
            )
        ;

        $obj1 = new \stdClass();
        $obj1->uid = 1;
        $obj1->title = 'Foo';

        $obj2 = new \stdClass();
        $obj2->uid = -1;
        $obj2->title = 'Bar';

        $obj3 = new \stdClass();
        $obj3->title = 'Baz';

        $obj4 = new \stdClass();
        $obj4->uid = 2;

        $this->arguments['optionValueField'] = 'uid';
        $this->arguments['optionLabelField'] = 'title';
        $this->arguments['sortByOptionLabel'] = true;
        $this->arguments['options'] = [$obj1, $obj2, $obj3, $obj4];

        $this->injectDependenciesIntoViewHelper($this->viewHelper);

        $this->viewHelper->initialize();
        $this->viewHelper->render();
    }

    /**
     * @test
     */
    public function selectCreatesExpectedOptionsWithArrayObjectsAndOptionValueFieldAndOptionLabelFieldSet()
    {
        $this->tagBuilder
            ->expects(static::once())
            ->method('setContent')
            ->with(
                '<option value="2"></option>' . chr(10)
                    . '<option value="-1">Bar</option>' . chr(10)
                    . '<option value="">Baz</option>' . chr(10)
                    . '<option value="1">Foo</option>' . chr(10)
            )
        ;

        $this->arguments['optionValueField'] = 'uid';
        $this->arguments['optionLabelField'] = 'title';
        $this->arguments['sortByOptionLabel'] = true;
        $this->arguments['options'] = new \ArrayObject(
            [
                [
                    'uid' => 1,
                    'title' => 'Foo',
                ],
                [
                    'uid' => -1,
                    'title' => 'Bar',
                ],
                [
                    'title' => 'Baz',
                ],
                [
                    'uid' => '2',
                ],
            ]
        );

        $this->injectDependenciesIntoViewHelper($this->viewHelper);

        $this->viewHelper->initialize();
        $this->viewHelper->render();
    }

    /**
     * @test
     */
    public function orderOfOptionsIsNotAlteredByDefault()
    {
        $this->tagBuilder->expects(self::once())->method('addAttribute')->with('name', 'myName');
        $this->viewHelper->expects(self::once())->method('registerFieldNameForFormTokenGeneration')->with('myName');
        $this->tagBuilder->expects(self::once())->method('setContent')->with('<option value="value3">label3</option>' . chr(10) . '<option value="value1">label1</option>' . chr(10) . '<option value="value2" selected="selected">label2</option>' . chr(10));
        $this->tagBuilder->expects(self::once())->method('render');

        $this->arguments['options'] = [
            'value3' => 'label3',
            'value1' => 'label1',
            'value2' => 'label2'
        ];

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
        $this->tagBuilder->expects(self::once())->method('addAttribute')->with('name', 'myName');
        $this->viewHelper->expects(self::once())->method('registerFieldNameForFormTokenGeneration')->with('myName');
        $this->tagBuilder->expects(self::once())->method('setContent')->with('<option value="value1">label1</option>' . chr(10) . '<option value="value2" selected="selected">label2</option>' . chr(10) . '<option value="value3">label3</option>' . chr(10));
        $this->tagBuilder->expects(self::once())->method('render');

        $this->arguments['options'] = [
            'value3' => 'label3',
            'value1' => 'label1',
            'value2' => 'label2'
        ];

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

        $this->arguments['options'] = [
            'value1' => 'label1',
            'value2' => 'label2',
            'value3' => 'label3'
        ];

        $this->arguments['value'] = ['value3', 'value1'];
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
        self::assertSame($expected, $result);
    }

    /**
     * @test
     */
    public function multipleSelectCreatesExpectedOptionsInObjectAccessorMode()
    {
        $this->tagBuilder = new TagBuilder();

        $user = new \Neos\FluidAdaptor\ViewHelpers\Fixtures\UserDomainClass(1, 'Sebastian', 'Düvel');

        $this->viewHelperVariableContainerData = [
            \Neos\FluidAdaptor\ViewHelpers\FormViewHelper::class => [
                'formObjectName' => 'someFormObjectName',
                'formObject' => $user,
            ]
        ];

        $this->arguments['options'] = [
            'value1' => 'label1',
            'value2' => 'label2',
            'value3' => 'label3'
        ];
        $this->arguments['property'] = 'interests';
        $this->arguments['multiple'] = 'multiple';
        $this->arguments['selectAllByDefault'] = null;

        /** @var PersistenceManagerInterface|\PHPUnit\Framework\MockObject\MockObject $mockPersistenceManager */
        $mockPersistenceManager = $this->createMock(\Neos\Flow\Persistence\PersistenceManagerInterface::class);
        $mockPersistenceManager->expects(self::any())->method('getIdentifierByObject')->with($user->getInterests())->will(self::returnValue(null));
        $this->viewHelper->injectPersistenceManager($mockPersistenceManager);

        $this->injectDependenciesIntoViewHelper($this->viewHelper);

        $this->viewHelper->initializeArguments();
        $this->viewHelper->initialize();
        $result = $this->viewHelper->render();
        $expected = '<select multiple="multiple" name="someFormObjectName[interests][]"><option value="value1" selected="selected">label1</option>' . chr(10) .
            '<option value="value2">label2</option>' . chr(10) .
            '<option value="value3" selected="selected">label3</option>' . chr(10) .
            '</select>';
        self::assertSame($expected, $result);
    }

    /**
     * @test
     */
    public function selectOnDomainObjectsCreatesExpectedOptions()
    {
        $mockPersistenceManager = $this->createMock(\Neos\Flow\Persistence\PersistenceManagerInterface::class);
        $mockPersistenceManager->expects(self::any())->method('getIdentifierByObject')->will(self::returnValue(2));
        $this->viewHelper->injectPersistenceManager($mockPersistenceManager);

        $this->tagBuilder->expects(self::once())->method('addAttribute')->with('name', 'myName[__identity]');
        $this->viewHelper->expects(self::once())->method('registerFieldNameForFormTokenGeneration')->with('myName[__identity]');
        $this->tagBuilder->expects(self::once())->method('setContent')->with('<option value="1">Ingmar</option>' . chr(10) . '<option value="2" selected="selected">Sebastian</option>' . chr(10) . '<option value="3">Robert</option>' . chr(10));
        $this->tagBuilder->expects(self::once())->method('render');

        $user_is = new \Neos\FluidAdaptor\ViewHelpers\Fixtures\UserDomainClass(1, 'Ingmar', 'Schlecht');
        $user_sk = new \Neos\FluidAdaptor\ViewHelpers\Fixtures\UserDomainClass(2, 'Sebastian', 'Kurfuerst');
        $user_rl = new \Neos\FluidAdaptor\ViewHelpers\Fixtures\UserDomainClass(3, 'Robert', 'Lemke');

        $this->arguments['options'] = [
            $user_is,
            $user_sk,
            $user_rl
        ];

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
        $this->viewHelper->expects(self::exactly(3))->method('registerFieldNameForFormTokenGeneration')->with('myName[]');

        $user_is = new \Neos\FluidAdaptor\ViewHelpers\Fixtures\UserDomainClass(1, 'Ingmar', 'Schlecht');
        $user_sk = new \Neos\FluidAdaptor\ViewHelpers\Fixtures\UserDomainClass(2, 'Sebastian', 'Kurfuerst');
        $user_rl = new \Neos\FluidAdaptor\ViewHelpers\Fixtures\UserDomainClass(3, 'Robert', 'Lemke');

        $this->arguments['options'] = [
            $user_is,
            $user_sk,
            $user_rl
        ];
        $this->arguments['value'] = [$user_rl, $user_is];
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
        self::assertSame($expected, $actual);
    }

    /**
     * @test
     */
    public function multipleSelectOnDomainObjectsCreatesExpectedOptionsWithoutOptionValueField()
    {
        $mockPersistenceManager = $this->createMock(\Neos\Flow\Persistence\PersistenceManagerInterface::class);
        $mockPersistenceManager->expects(self::any())->method('getIdentifierByObject')->will(self::returnCallBack(
            function ($object) {
                return $object->getId();
            }
        ));
        $this->viewHelper->injectPersistenceManager($mockPersistenceManager);

        $this->tagBuilder = new TagBuilder();
        $this->viewHelper->expects(self::exactly(3))->method('registerFieldNameForFormTokenGeneration')->with('myName[]');

        $user_is = new \Neos\FluidAdaptor\ViewHelpers\Fixtures\UserDomainClass(1, 'Ingmar', 'Schlecht');
        $user_sk = new \Neos\FluidAdaptor\ViewHelpers\Fixtures\UserDomainClass(2, 'Sebastian', 'Kurfuerst');
        $user_rl = new \Neos\FluidAdaptor\ViewHelpers\Fixtures\UserDomainClass(3, 'Robert', 'Lemke');

        $this->arguments['options'] = [$user_is,$user_sk,$user_rl];
        $this->arguments['value'] = [$user_rl, $user_is];
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
        self::assertSame($expected, $actual);
    }

    /**
     * @test
     */
    public function selectWithoutFurtherConfigurationOnDomainObjectsUsesUuidForValueAndLabel()
    {
        $mockPersistenceManager = $this->createMock(\Neos\Flow\Persistence\PersistenceManagerInterface::class);
        $mockPersistenceManager->expects(self::any())->method('getIdentifierByObject')->will(self::returnValue('fakeUUID'));
        $this->viewHelper->injectPersistenceManager($mockPersistenceManager);

        $this->tagBuilder->expects(self::once())->method('addAttribute')->with('name', 'myName');
        $this->viewHelper->expects(self::once())->method('registerFieldNameForFormTokenGeneration')->with('myName');
        $this->tagBuilder->expects(self::once())->method('setContent')->with('<option value="fakeUUID">fakeUUID</option>' . chr(10));
        $this->tagBuilder->expects(self::once())->method('render');

        $user = new \Neos\FluidAdaptor\ViewHelpers\Fixtures\UserDomainClass(1, 'Ingmar', 'Schlecht');

        $this->arguments['options'] = [
            $user
        ];
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
        $mockPersistenceManager->expects(self::any())->method('getIdentifierByObject')->will(self::returnValue('fakeUUID'));
        $this->viewHelper->injectPersistenceManager($mockPersistenceManager);

        $this->tagBuilder->expects(self::once())->method('addAttribute')->with('name', 'myName');
        $this->viewHelper->expects(self::once())->method('registerFieldNameForFormTokenGeneration')->with('myName');
        $this->tagBuilder->expects(self::once())->method('setContent')->with('<option value="fakeUUID">toStringResult</option>' . chr(10));
        $this->tagBuilder->expects(self::once())->method('render');

        $user = $this->getMockBuilder(\Neos\FluidAdaptor\ViewHelpers\Fixtures\UserDomainClass::class)->setMethods(['__toString'])->setConstructorArgs([1, 'Ingmar', 'Schlecht'])->getMock();
        $user->expects(self::atLeastOnce())->method('__toString')->will(self::returnValue('toStringResult'));

        $this->arguments['options'] = [
            $user
        ];
        $this->arguments['name'] = 'myName';
        $this->injectDependenciesIntoViewHelper($this->viewHelper);

        $this->viewHelper->initialize();
        $this->viewHelper->render();
    }

    /**
     * @test
     */
    public function selectOnDomainObjectsThrowsExceptionIfNoValueCanBeFound()
    {
        $this->expectException(Exception::class);
        $mockPersistenceManager = $this->createMock(\Neos\Flow\Persistence\PersistenceManagerInterface::class);
        $mockPersistenceManager->expects(self::any())->method('getIdentifierByObject')->will(self::returnValue(null));
        $this->viewHelper->injectPersistenceManager($mockPersistenceManager);

        $user = new \Neos\FluidAdaptor\ViewHelpers\Fixtures\UserDomainClass(1, 'Ingmar', 'Schlecht');

        $this->arguments['options'] = [
            $user
        ];
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
        $this->arguments['options'] = [];

        $this->injectDependenciesIntoViewHelper($this->viewHelper);

        $this->viewHelper->expects(self::once())->method('setErrorClassAttribute');
        $this->viewHelper->render();
    }

    /**
     * @test
     */
    public function allOptionsAreSelectedIfSelectAllIsTrue()
    {
        $this->tagBuilder->expects(self::once())->method('setContent')->with('<option value="value1" selected="selected">label1</option>' . chr(10) . '<option value="value2" selected="selected">label2</option>' . chr(10) . '<option value="value3" selected="selected">label3</option>' . chr(10));

        $this->arguments['options'] = [
            'value1' => 'label1',
            'value2' => 'label2',
            'value3' => 'label3'
        ];
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
        $this->tagBuilder->expects(self::once())->method('setContent')->with('<option value="value1" selected="selected">label1</option>' . chr(10) . '<option value="value2" selected="selected">label2</option>' . chr(10) . '<option value="value3">label3</option>' . chr(10));

        $this->arguments['options'] = [
            'value1' => 'label1',
            'value2' => 'label2',
            'value3' => 'label3'
        ];
        $this->arguments['value'] = ['value2', 'value1'];
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
        $this->arguments['options'] = ['foo' => 'bar'];
        $this->arguments['translate'] = ['by' => 'id'];
        $viewHelper = $this->getAccessibleMock(\Neos\FluidAdaptor\ViewHelpers\Form\SelectViewHelper::class, ['getTranslatedLabel', 'setErrorClassAttribute', 'registerFieldNameForFormTokenGeneration']);
        $this->injectDependenciesIntoViewHelper($viewHelper);

        $viewHelper->expects(self::once())->method('getTranslatedLabel')->with('foo', 'bar');
        $viewHelper->render();
    }

    /**
     * @test
     */
    public function translateByIdAskForTranslationOfValueById()
    {
        $this->arguments['translate'] = ['by' => 'id'];
        $this->injectDependenciesIntoViewHelper($this->viewHelper);

        $mockTranslator = $this->createMock(\Neos\Flow\I18n\Translator::class);
        $mockTranslator->expects(self::once())->method('translateById')->with('value1', [], null, null, 'Main', '');
        $this->viewHelper->_set('translator', $mockTranslator);
        $this->viewHelper->_call('getTranslatedLabel', 'value1', 'label1');
    }

    /**
     * @test
     */
    public function translateByLabelAskForTranslationOfLabelByLabel()
    {
        $this->arguments['translate'] = ['by' => 'label'];
        $this->injectDependenciesIntoViewHelper($this->viewHelper);

        $mockTranslator = $this->createMock(\Neos\Flow\I18n\Translator::class);
        $mockTranslator->expects(self::once())->method('translateByOriginalLabel')->with('label1', [], null, null, 'Main', '');
        $this->viewHelper->_set('translator', $mockTranslator);
        $this->viewHelper->_call('getTranslatedLabel', 'value1', 'label1');
    }

    /**
     * @test
     */
    public function translateByLabelUsingValueUsesValue()
    {
        $this->arguments['translate'] = ['by' => 'label', 'using' => 'value'];
        $this->injectDependenciesIntoViewHelper($this->viewHelper);

        $mockTranslator = $this->createMock(\Neos\Flow\I18n\Translator::class);
        $mockTranslator->expects(self::once())->method('translateByOriginalLabel')->with('value1', [], null, null, 'Main', '');
        $this->viewHelper->_set('translator', $mockTranslator);
        $this->viewHelper->_call('getTranslatedLabel', 'value1', 'label1');
    }

    /**
     * @test
     */
    public function translateByIdUsingLabelUsesLabel()
    {
        $this->arguments['translate'] = ['by' => 'id', 'using' => 'label'];
        $this->injectDependenciesIntoViewHelper($this->viewHelper);

        $mockTranslator = $this->createMock(\Neos\Flow\I18n\Translator::class);
        $mockTranslator->expects(self::once())->method('translateById')->with('label1', [], null, null, 'Main', '');
        $this->viewHelper->_set('translator', $mockTranslator);
        $this->viewHelper->_call('getTranslatedLabel', 'value1', 'label1');
    }

    /**
     * @test
     */
    public function translateOptionsAreObserved()
    {
        $this->arguments['translate'] = ['by' => 'id', 'using' => 'label', 'locale' => 'dk', 'source' => 'WeirdMessageCatalog', 'package' => 'Foo.Bar', 'prefix' => 'somePrefix.'];
        $this->injectDependenciesIntoViewHelper($this->viewHelper);

        $mockTranslator = $this->createMock(\Neos\Flow\I18n\Translator::class);
        $mockTranslator->expects(self::once())->method('translateById')->with('somePrefix.label1', [], null, new \Neos\Flow\I18n\Locale('dk'), 'WeirdMessageCatalog', 'Foo.Bar');
        $this->viewHelper->_set('translator', $mockTranslator);
        $this->viewHelper->_call('getTranslatedLabel', 'value1', 'label1');
    }

    /**
     * @test
     */
    public function getTranslatedLabelThrowsExceptionForInvalidLocales()
    {
        $this->expectException(Exception::class);
        $this->arguments['translate'] = ['locale' => 'invalid-locale'];
        $this->injectDependenciesIntoViewHelper($this->viewHelper);

        $this->viewHelper->_call('getTranslatedLabel', 'value1', 'label1');
    }

    /**
     * @test
     */
    public function getTranslatedLabelThrowsExceptionForUnknownTranslateBy()
    {
        $this->expectException(Exception::class);
        $this->arguments['translate'] = ['by' => 'foo'];
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
            $mockTranslator->expects(self::once())->method('translateByOriginalLabel')->will(self::returnCallBack(function ($label) use ($translatedLabel) {
                return $translatedLabel !== null ? $translatedLabel : $label;
            }));
        } else {
            $mockTranslator->expects(self::once())->method('translateById')->will(self::returnValue($translatedId));
        }
        $this->inject($this->viewHelper, 'translator', $mockTranslator);

        $actualResult = $this->viewHelper->_call('getTranslatedLabel', 'someValue', 'Some label');
        self::assertSame($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function optionsContainPrependedItemWithEmptyValueIfPrependOptionLabelIsSet()
    {
        $this->tagBuilder->expects(self::once())->method('addAttribute')->with('name', 'myName');
        $this->viewHelper->expects(self::once())->method('registerFieldNameForFormTokenGeneration')->with('myName');
        $this->tagBuilder->expects(self::once())->method('setContent')->with('<option value="">please choose</option>' . chr(10) . '<option value="value1">label1</option>' . chr(10) . '<option value="value2">label2</option>' . chr(10) . '<option value="value3">label3</option>' . chr(10));
        $this->tagBuilder->expects(self::once())->method('render');
        $this->arguments['options'] = [
            'value1' => 'label1',
            'value2' => 'label2',
            'value3' => 'label3'
        ];
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
        $this->tagBuilder->expects(self::once())->method('addAttribute')->with('name', 'myName');
        $this->viewHelper->expects(self::once())->method('registerFieldNameForFormTokenGeneration')->with('myName');
        $this->tagBuilder->expects(self::once())->method('setContent')->with('<option value="-1">please choose</option>' . chr(10) . '<option value="value1">label1</option>' . chr(10) . '<option value="value2">label2</option>' . chr(10) . '<option value="value3">label3</option>' . chr(10));
        $this->tagBuilder->expects(self::once())->method('render');
        $this->arguments['options'] = [
            'value1' => 'label1',
            'value2' => 'label2',
            'value3' => 'label3'
        ];
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
        $this->tagBuilder->expects(self::once())->method('addAttribute')->with('name', 'myName');
        $this->viewHelper->expects(self::once())->method('registerFieldNameForFormTokenGeneration')->with('myName');
        $this->tagBuilder->expects(self::once())->method('setContent')->with('<option value="">translated label</option>' . chr(10));
        $this->tagBuilder->expects(self::once())->method('render');
        $this->arguments['options'] = [];
        $this->arguments['name'] = 'myName';
        $this->arguments['prependOptionLabel'] = 'select';
        $this->arguments['translate'] = ['by' => 'id', 'using' => 'label'];

        $mockTranslator = $this->createMock(\Neos\Flow\I18n\Translator::class);
        $mockTranslator->expects(self::once())->method('translateById')->with('select', [], null, null, 'Main', '')->will(self::returnValue('translated label'));
        $this->viewHelper->_set('translator', $mockTranslator);

        $this->injectDependenciesIntoViewHelper($this->viewHelper);
        $this->viewHelper->initialize();
        $this->viewHelper->render();
    }
}
