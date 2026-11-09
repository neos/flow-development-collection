<?php

declare(strict_types=1);

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
use Neos\Flow\I18n\Locale;
use Neos\Flow\I18n\Translator;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\FluidAdaptor\Core\ViewHelper\Exception;
use Neos\FluidAdaptor\Tests\Unit\ViewHelpers\ViewHelperBaseTestcase;
use Neos\FluidAdaptor\ViewHelpers\Fixtures\UserDomainClass;
use Neos\FluidAdaptor\ViewHelpers\Form\SelectViewHelper;
use Neos\FluidAdaptor\ViewHelpers\FormViewHelper;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use TYPO3Fluid\Fluid\Core\ViewHelper\TagBuilder;

require_once(__DIR__ . '/Fixtures/EmptySyntaxTreeNode.php');
require_once(__DIR__ . '/Fixtures/Fixture_UserDomainClass.php');
require_once(__DIR__ . '/../ViewHelperBaseTestcase.php');

/**
 * Test for the "Select" Form view helper
 */
final class SelectViewHelperTest extends ViewHelperBaseTestcase
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
        $this->viewHelper = $this->getAccessibleMock(SelectViewHelper::class, ['setErrorClassAttribute', 'registerFieldNameForFormTokenGeneration']);
    }

    #[Test]
    public function selectCorrectlySetsTagName()
    {
        $this->tagBuilder->expects($this->atLeastOnce())->method('setTagName')->with('select');

        $this->arguments['options'] = [];
        $this->injectDependenciesIntoViewHelper($this->viewHelper);

        $this->viewHelper->initialize();
        $this->viewHelper->render();
    }

    #[Test]
    public function selectCreatesExpectedOptions()
    {
        $this->tagBuilder->expects($this->once())->method('addAttribute')->with('name', 'myName');
        $this->viewHelper->expects($this->once())->method('registerFieldNameForFormTokenGeneration')->with('myName');
        $this->tagBuilder->expects($this->once())->method('setContent')->with('<option value="value1">label1</option>' . chr(10) . '<option value="value2" selected="selected">label2</option>' . chr(10));
        $this->tagBuilder->expects($this->once())->method('render');

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

    #[Test]
    public function anEmptyOptionTagIsRenderedIfOptionsArrayIsEmptyToAssureXhtmlCompatibility()
    {
        $this->tagBuilder->expects($this->once())->method('addAttribute')->with('name', 'myName');
        $this->viewHelper->expects($this->once())->method('registerFieldNameForFormTokenGeneration')->with('myName');
        $this->tagBuilder->expects($this->once())->method('setContent')->with('<option value=""></option>' . chr(10));
        $this->tagBuilder->expects($this->once())->method('render');

        $this->arguments['options'] = [];
        $this->arguments['value'] = 'value2';
        $this->arguments['name'] = 'myName';
        $this->injectDependenciesIntoViewHelper($this->viewHelper);

        $this->viewHelper->initialize();
        $this->viewHelper->render();
    }

    #[Test]
    public function selectCreatesExpectedOptionsWithArraysAndOptionValueFieldAndOptionLabelFieldSet()
    {
        $this->tagBuilder
            ->expects($this->once())
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

    #[Test]
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

    #[Test]
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

    #[Test]
    public function orderOfOptionsIsNotAlteredByDefault()
    {
        $this->tagBuilder->expects($this->once())->method('addAttribute')->with('name', 'myName');
        $this->viewHelper->expects($this->once())->method('registerFieldNameForFormTokenGeneration')->with('myName');
        $this->tagBuilder->expects($this->once())->method('setContent')->with('<option value="value3">label3</option>' . chr(10) . '<option value="value1">label1</option>' . chr(10) . '<option value="value2" selected="selected">label2</option>' . chr(10));
        $this->tagBuilder->expects($this->once())->method('render');

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

    #[Test]
    public function optionsAreSortedByLabelIfSortByOptionLabelIsSet()
    {
        $this->tagBuilder->expects($this->once())->method('addAttribute')->with('name', 'myName');
        $this->viewHelper->expects($this->once())->method('registerFieldNameForFormTokenGeneration')->with('myName');
        $this->tagBuilder->expects($this->once())->method('setContent')->with('<option value="value1">label1</option>' . chr(10) . '<option value="value2" selected="selected">label2</option>' . chr(10) . '<option value="value3">label3</option>' . chr(10));
        $this->tagBuilder->expects($this->once())->method('render');

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

    #[Test]
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

    #[Test]
    public function multipleSelectCreatesExpectedOptionsInObjectAccessorMode()
    {
        $this->tagBuilder = new TagBuilder();

        $user = new UserDomainClass(1, 'Sebastian', 'Düvel');

        $this->viewHelperVariableContainerData = [
            FormViewHelper::class => [
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

        /** @var PersistenceManagerInterface|MockObject $mockPersistenceManager */
        $mockPersistenceManager = $this->createMock(PersistenceManagerInterface::class);
        $mockPersistenceManager->method('getIdentifierByObject')->with($user->getInterests())->willReturn((null));
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

    #[Test]
    public function selectOnDomainObjectsCreatesExpectedOptions()
    {
        $mockPersistenceManager = $this->createMock(PersistenceManagerInterface::class);
        $mockPersistenceManager->method('getIdentifierByObject')->willReturn((2));
        $this->viewHelper->injectPersistenceManager($mockPersistenceManager);

        $this->tagBuilder->expects($this->once())->method('addAttribute')->with('name', 'myName[__identity]');
        $this->viewHelper->expects($this->once())->method('registerFieldNameForFormTokenGeneration')->with('myName[__identity]');
        $this->tagBuilder->expects($this->once())->method('setContent')->with('<option value="1">Ingmar</option>' . chr(10) . '<option value="2" selected="selected">Sebastian</option>' . chr(10) . '<option value="3">Robert</option>' . chr(10));
        $this->tagBuilder->expects($this->once())->method('render');

        $user_is = new UserDomainClass(1, 'Ingmar', 'Schlecht');
        $user_sk = new UserDomainClass(2, 'Sebastian', 'Kurfuerst');
        $user_rl = new UserDomainClass(3, 'Robert', 'Lemke');

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

    #[Test]
    public function multipleSelectOnDomainObjectsCreatesExpectedOptions()
    {
        $this->tagBuilder = new TagBuilder();
        $this->viewHelper->expects($this->exactly(3))->method('registerFieldNameForFormTokenGeneration')->with('myName[]');

        $user_is = new UserDomainClass(1, 'Ingmar', 'Schlecht');
        $user_sk = new UserDomainClass(2, 'Sebastian', 'Kurfuerst');
        $user_rl = new UserDomainClass(3, 'Robert', 'Lemke');

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

    #[Test]
    public function multipleSelectOnDomainObjectsCreatesExpectedOptionsWithoutOptionValueField()
    {
        $mockPersistenceManager = $this->createMock(PersistenceManagerInterface::class);
        $mockPersistenceManager->method('getIdentifierByObject')->willReturnCallback(function ($object) {
            return $object->getId();
        });
        $this->viewHelper->injectPersistenceManager($mockPersistenceManager);

        $this->tagBuilder = new TagBuilder();
        $this->viewHelper->expects($this->exactly(3))->method('registerFieldNameForFormTokenGeneration')->with('myName[]');

        $user_is = new UserDomainClass(1, 'Ingmar', 'Schlecht');
        $user_sk = new UserDomainClass(2, 'Sebastian', 'Kurfuerst');
        $user_rl = new UserDomainClass(3, 'Robert', 'Lemke');

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

    #[Test]
    public function selectWithoutFurtherConfigurationOnDomainObjectsUsesUuidForValueAndLabel()
    {
        $mockPersistenceManager = $this->createMock(PersistenceManagerInterface::class);
        $mockPersistenceManager->method('getIdentifierByObject')->willReturn(('fakeUUID'));
        $this->viewHelper->injectPersistenceManager($mockPersistenceManager);

        $this->tagBuilder->expects($this->once())->method('addAttribute')->with('name', 'myName');
        $this->viewHelper->expects($this->once())->method('registerFieldNameForFormTokenGeneration')->with('myName');
        $this->tagBuilder->expects($this->once())->method('setContent')->with('<option value="fakeUUID">fakeUUID</option>' . chr(10));
        $this->tagBuilder->expects($this->once())->method('render');

        // Plain domain object without __toString — the view helper should fall back to the UUID.
        $user = new class () {
            public int $id = 1;
            public string $firstName = 'Ingmar';
            public string $lastName = 'Schlecht';
        };

        $this->arguments['options'] = [
            $user
        ];
        $this->arguments['name'] = 'myName';
        $this->injectDependenciesIntoViewHelper($this->viewHelper);

        $this->viewHelper->initialize();
        $this->viewHelper->render();
    }

    #[Test]
    public function selectWithoutFurtherConfigurationOnDomainObjectsUsesToStringForLabelIfAvailable()
    {
        $mockPersistenceManager = $this->createMock(PersistenceManagerInterface::class);
        $mockPersistenceManager->method('getIdentifierByObject')->willReturn(('fakeUUID'));
        $this->viewHelper->injectPersistenceManager($mockPersistenceManager);

        $this->tagBuilder->expects($this->once())->method('addAttribute')->with('name', 'myName');
        $this->viewHelper->expects($this->once())->method('registerFieldNameForFormTokenGeneration')->with('myName');
        $this->tagBuilder->expects($this->once())->method('setContent')->with('<option value="fakeUUID">toStringResult</option>' . chr(10));
        $this->tagBuilder->expects($this->once())->method('render');

        $user = $this->getMockBuilder(UserDomainClass::class)->onlyMethods(['__toString'])->setConstructorArgs([1, 'Ingmar', 'Schlecht'])->getMock();
        $user->expects($this->atLeastOnce())->method('__toString')->willReturn(('toStringResult'));

        $this->arguments['options'] = [
            $user
        ];
        $this->arguments['name'] = 'myName';
        $this->injectDependenciesIntoViewHelper($this->viewHelper);

        $this->viewHelper->initialize();
        $this->viewHelper->render();
    }

    #[Test]
    public function selectOnDomainObjectsThrowsExceptionIfNoValueCanBeFound()
    {
        $this->expectException(Exception::class);
        $mockPersistenceManager = $this->createMock(PersistenceManagerInterface::class);
        $mockPersistenceManager->method('getIdentifierByObject')->willReturn((null));
        $this->viewHelper->injectPersistenceManager($mockPersistenceManager);

        // Object lacking __toString — combined with the null persistence identifier,
        // the view helper has no way to derive a value and must raise the exception.
        $user = new class () {
            public string $firstName = 'Ingmar';
            public string $lastName = 'Schlecht';
        };

        $this->arguments['options'] = [
            $user
        ];
        $this->arguments['name'] = 'myName';
        $this->injectDependenciesIntoViewHelper($this->viewHelper);

        $this->viewHelper->initialize();
        $this->viewHelper->render();
    }

    #[Test]
    public function renderCallsSetErrorClassAttribute()
    {
        $this->arguments['options'] = [];

        $this->injectDependenciesIntoViewHelper($this->viewHelper);

        $this->viewHelper->expects($this->once())->method('setErrorClassAttribute');
        $this->viewHelper->render();
    }

    #[Test]
    public function allOptionsAreSelectedIfSelectAllIsTrue()
    {
        $this->tagBuilder->expects($this->once())->method('setContent')->with('<option value="value1" selected="selected">label1</option>' . chr(10) . '<option value="value2" selected="selected">label2</option>' . chr(10) . '<option value="value3" selected="selected">label3</option>' . chr(10));

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

    #[Test]
    public function selectAllHasNoEffectIfValueIsSet()
    {
        $this->tagBuilder->expects($this->once())->method('setContent')->with('<option value="value1" selected="selected">label1</option>' . chr(10) . '<option value="value2" selected="selected">label2</option>' . chr(10) . '<option value="value3">label3</option>' . chr(10));

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

    #[Test]
    public function translateLabelIsCalledIfTranslateArgumentIsGiven()
    {
        $this->arguments['options'] = ['foo' => 'bar'];
        $this->arguments['translate'] = ['by' => 'id'];
        $viewHelper = $this->getAccessibleMock(SelectViewHelper::class, ['getTranslatedLabel', 'setErrorClassAttribute', 'registerFieldNameForFormTokenGeneration']);
        $this->injectDependenciesIntoViewHelper($viewHelper);

        $viewHelper->expects($this->once())->method('getTranslatedLabel')->with('foo', 'bar');
        $viewHelper->render();
    }

    #[Test]
    public function translateByIdAskForTranslationOfValueById()
    {
        $this->arguments['translate'] = ['by' => 'id'];
        $this->injectDependenciesIntoViewHelper($this->viewHelper);

        $mockTranslator = $this->createMock(Translator::class);
        $mockTranslator->expects($this->once())->method('translateById')->with('value1', [], null, null, 'Main', '');
        $this->viewHelper->_set('translator', $mockTranslator);
        $this->viewHelper->_call('getTranslatedLabel', 'value1', 'label1');
    }

    #[Test]
    public function translateByLabelAskForTranslationOfLabelByLabel()
    {
        $this->arguments['translate'] = ['by' => 'label'];
        $this->injectDependenciesIntoViewHelper($this->viewHelper);

        $mockTranslator = $this->createMock(Translator::class);
        $mockTranslator->expects($this->once())->method('translateByOriginalLabel')->with('label1', [], null, null, 'Main', '');
        $this->viewHelper->_set('translator', $mockTranslator);
        $this->viewHelper->_call('getTranslatedLabel', 'value1', 'label1');
    }

    #[Test]
    public function translateByLabelUsingValueUsesValue()
    {
        $this->arguments['translate'] = ['by' => 'label', 'using' => 'value'];
        $this->injectDependenciesIntoViewHelper($this->viewHelper);

        $mockTranslator = $this->createMock(Translator::class);
        $mockTranslator->expects($this->once())->method('translateByOriginalLabel')->with('value1', [], null, null, 'Main', '');
        $this->viewHelper->_set('translator', $mockTranslator);
        $this->viewHelper->_call('getTranslatedLabel', 'value1', 'label1');
    }

    #[Test]
    public function translateByIdUsingLabelUsesLabel()
    {
        $this->arguments['translate'] = ['by' => 'id', 'using' => 'label'];
        $this->injectDependenciesIntoViewHelper($this->viewHelper);

        $mockTranslator = $this->createMock(Translator::class);
        $mockTranslator->expects($this->once())->method('translateById')->with('label1', [], null, null, 'Main', '');
        $this->viewHelper->_set('translator', $mockTranslator);
        $this->viewHelper->_call('getTranslatedLabel', 'value1', 'label1');
    }

    #[Test]
    public function translateOptionsAreObserved()
    {
        $this->arguments['translate'] = ['by' => 'id', 'using' => 'label', 'locale' => 'dk', 'source' => 'WeirdMessageCatalog', 'package' => 'Foo.Bar', 'prefix' => 'somePrefix.'];
        $this->injectDependenciesIntoViewHelper($this->viewHelper);

        $mockTranslator = $this->createMock(Translator::class);
        $mockTranslator->expects($this->once())->method('translateById')->with('somePrefix.label1', [], null, new Locale('dk'), 'WeirdMessageCatalog', 'Foo.Bar');
        $this->viewHelper->_set('translator', $mockTranslator);
        $this->viewHelper->_call('getTranslatedLabel', 'value1', 'label1');
    }

    #[Test]
    public function getTranslatedLabelThrowsExceptionForInvalidLocales()
    {
        $this->expectException(Exception::class);
        $this->arguments['translate'] = ['locale' => 'invalid-locale'];
        $this->injectDependenciesIntoViewHelper($this->viewHelper);

        $this->viewHelper->_call('getTranslatedLabel', 'value1', 'label1');
    }

    #[Test]
    public function getTranslatedLabelThrowsExceptionForUnknownTranslateBy()
    {
        $this->expectException(Exception::class);
        $this->arguments['translate'] = ['by' => 'foo'];
        $this->injectDependenciesIntoViewHelper($this->viewHelper);

        $this->viewHelper->_call('getTranslatedLabel', 'value1', 'label1');
    }

    public static function getTranslatedLabelDataProvider(): \Iterator
    {
        ## translate by id
        # using value
        yield ['by' => 'id', 'using' => 'value', 'translatedId' => 'Translated id', 'translatedLabel' => 'Translated label', 'expectedResult' => 'Translated id'];
        yield ['by' => 'id', 'using' => 'value', 'translatedId' => 'Translated id', 'translatedLabel' => null, 'expectedResult' => 'Translated id'];
        yield ['by' => 'id', 'using' => 'value', 'translatedId' => null, 'translatedLabel' => 'Translated label', 'expectedResult' => 'Some label'];
        yield ['by' => 'id', 'using' => 'value', 'translatedId' => null, 'translatedLabel' => null, 'expectedResult' => 'Some label'];
        # using label
        yield ['by' => 'id', 'using' => 'label', 'translatedId' => 'Translated id', 'translatedLabel' => 'Translated label', 'expectedResult' => 'Translated id'];
        yield ['by' => 'id', 'using' => 'label', 'translatedId' => 'Translated id', 'translatedLabel' => null, 'expectedResult' => 'Translated id'];
        yield ['by' => 'id', 'using' => 'label', 'translatedId' => null, 'translatedLabel' => 'Translated label', 'expectedResult' => 'Some label'];
        yield ['by' => 'id', 'using' => 'label', 'translatedId' => null, 'translatedLabel' => null, 'expectedResult' => 'Some label'];
        ## translate by label
        # using value
        yield ['by' => 'label', 'using' => 'value', 'translatedId' => 'Translated id', 'translatedLabel' => 'Translated label', 'expectedResult' => 'Translated label'];
        yield ['by' => 'label', 'using' => 'value', 'translatedId' => 'Translated id', 'translatedLabel' => null, 'expectedResult' => 'someValue'];
        yield ['by' => 'label', 'using' => 'value', 'translatedId' => null, 'translatedLabel' => 'Translated label', 'expectedResult' => 'Translated label'];
        yield ['by' => 'label', 'using' => 'value', 'translatedId' => null, 'translatedLabel' => null, 'expectedResult' => 'someValue'];
        # using label
        yield ['by' => 'label', 'using' => 'label', 'translatedId' => 'Translated id', 'translatedLabel' => 'Translated label', 'expectedResult' => 'Translated label'];
        yield ['by' => 'label', 'using' => 'label', 'translatedId' => 'Translated id', 'translatedLabel' => null, 'expectedResult' => 'Some label'];
        yield ['by' => 'label', 'using' => 'label', 'translatedId' => null, 'translatedLabel' => 'Translated label', 'expectedResult' => 'Translated label'];
        yield ['by' => 'label', 'using' => 'label', 'translatedId' => null, 'translatedLabel' => null, 'expectedResult' => 'Some label'];
    }

    /**
     * @param string $by
     * @param string $using
     * @param string $translatedId
     * @param string $translatedLabel
     * @param string $expectedResult
     */
    #[DataProvider('getTranslatedLabelDataProvider')]
    #[Test]
    public function getTranslatedLabelTests($by, $using, $translatedId, $translatedLabel, $expectedResult)
    {
        $this->arguments['translate'] = ['by' => $by, 'using' => $using, 'prefix' => 'somePrefix.'];
        $this->injectDependenciesIntoViewHelper($this->viewHelper);

        $mockTranslator = $this->createMock(Translator::class);
        if ($by === 'label') {
            $mockTranslator->expects($this->once())->method('translateByOriginalLabel')->willReturnCallback(function ($label) use ($translatedLabel) {
                return $translatedLabel !== null ? $translatedLabel : $label;
            });
        } else {
            $mockTranslator->expects($this->once())->method('translateById')->willReturn(($translatedId));
        }
        $this->inject($this->viewHelper, 'translator', $mockTranslator);

        $actualResult = $this->viewHelper->_call('getTranslatedLabel', 'someValue', 'Some label');
        self::assertSame($expectedResult, $actualResult);
    }

    #[Test]
    public function optionsContainPrependedItemWithEmptyValueIfPrependOptionLabelIsSet()
    {
        $this->tagBuilder->expects($this->once())->method('addAttribute')->with('name', 'myName');
        $this->viewHelper->expects($this->once())->method('registerFieldNameForFormTokenGeneration')->with('myName');
        $this->tagBuilder->expects($this->once())->method('setContent')->with('<option value="">please choose</option>' . chr(10) . '<option value="value1">label1</option>' . chr(10) . '<option value="value2">label2</option>' . chr(10) . '<option value="value3">label3</option>' . chr(10));
        $this->tagBuilder->expects($this->once())->method('render');
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

    #[Test]
    public function optionsContainPrependedItemWithCorrectValueIfPrependOptionLabelAndPrependOptionValueAreSet()
    {
        $this->tagBuilder->expects($this->once())->method('addAttribute')->with('name', 'myName');
        $this->viewHelper->expects($this->once())->method('registerFieldNameForFormTokenGeneration')->with('myName');
        $this->tagBuilder->expects($this->once())->method('setContent')->with('<option value="-1">please choose</option>' . chr(10) . '<option value="value1">label1</option>' . chr(10) . '<option value="value2">label2</option>' . chr(10) . '<option value="value3">label3</option>' . chr(10));
        $this->tagBuilder->expects($this->once())->method('render');
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

    #[Test]
    public function prependedOptionLabelIsTranslatedIfTranslateArgumentIsSet()
    {
        $this->tagBuilder->expects($this->once())->method('addAttribute')->with('name', 'myName');
        $this->viewHelper->expects($this->once())->method('registerFieldNameForFormTokenGeneration')->with('myName');
        $this->tagBuilder->expects($this->once())->method('setContent')->with('<option value="">translated label</option>' . chr(10));
        $this->tagBuilder->expects($this->once())->method('render');
        $this->arguments['options'] = [];
        $this->arguments['name'] = 'myName';
        $this->arguments['prependOptionLabel'] = 'select';
        $this->arguments['translate'] = ['by' => 'id', 'using' => 'label'];

        $mockTranslator = $this->createMock(Translator::class);
        $mockTranslator->expects($this->once())->method('translateById')->with('select', [], null, null, 'Main', '')->willReturn(('translated label'));
        $this->viewHelper->_set('translator', $mockTranslator);

        $this->injectDependenciesIntoViewHelper($this->viewHelper);
        $this->viewHelper->initialize();
        $this->viewHelper->render();
    }
}
