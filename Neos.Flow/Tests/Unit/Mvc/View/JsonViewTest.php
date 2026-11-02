<?php

declare(strict_types=1);

namespace Neos\Flow\Tests\Unit\Mvc\View;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */
use Neos\Flow\Mvc;
use Neos\Flow\Mvc\ActionResponse;
use Neos\Flow\Mvc\Controller\ControllerContext;
use Neos\Flow\Mvc\View\JsonView;
use Neos\Flow\Persistence\Doctrine\PersistenceManager;
use Neos\Flow\Tests\Unit\Mvc\View\Fixtures\NestedTestObject;
use Neos\Flow\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

/**
 * Testcase for the JSON view
 */
final class JsonViewTest extends UnitTestCase
{
    /**
     * @var Mvc\View\JsonView
     */
    protected $view;

    /**
     * @var Mvc\ActionResponse
     */
    protected $response;

    /**
     * Sets up this test case
     * @return void
     */
    protected function setUp(): void
    {
        $this->view = $this->getMockBuilder(JsonView::class)->onlyMethods([])->getMock();
        $controllerContext = $this->createMock(ControllerContext::class);
        $this->response = new ActionResponse();
        $controllerContext->method('getResponse')->willReturn(($this->response));
        $this->view->setControllerContext($controllerContext);
    }

    /**
     * data provider for testTransformValue()
     * @return array
     */
    public static function jsonViewTestData()
    {
        $output = [];

        $object = new \stdClass();
        $object->value1 = 'foo';
        $object->value2 = 1;
        $configuration = [];
        $expected = ['value1' => 'foo', 'value2' => 1];
        $output[] = [$object, $configuration, $expected, 'all direct child properties should be serialized'];

        $configuration = ['_only' => ['value1']];
        $expected = ['value1' => 'foo'];
        $output[] = [$object, $configuration, $expected, 'if "only" properties are specified, only these should be serialized'];

        $configuration = ['_exclude' => ['value1']];
        $expected = ['value2' => 1];
        $output[] = [$object, $configuration, $expected, 'if "exclude" properties are specified, they should not be serialized'];

        $object = new \stdClass();
        $object->value1 = new \stdClass();
        $object->value1->subvalue1 = 'Foo';
        $object->value2 = 1;
        $configuration = [];
        $expected = ['value2' => 1];
        $output[] = [$object, $configuration, $expected, 'by default, sub objects of objects should not be serialized.'];

        $object = new \stdClass();
        $object->value1 = ['subarray' => 'value'];
        $object->value2 = 1;
        $configuration = [];
        $expected = ['value2' => 1];
        $output[] = [$object, $configuration, $expected, 'by default, sub arrays of objects should not be serialized.'];

        $object = ['foo' => 'bar', 1 => 'baz', 'deep' => ['test' => 'value']];
        $configuration = [];
        $expected = ['foo' => 'bar', 1 => 'baz', 'deep' => ['test' => 'value']];
        $output[] = [$object, $configuration, $expected, 'associative arrays should be serialized deeply'];

        $object = ['foo', 'bar'];
        $configuration = [];
        $expected = ['foo', 'bar'];
        $output[] = [$object, $configuration, $expected, 'numeric arrays should be serialized'];

        $nestedObject = new \stdClass();
        $nestedObject->value1 = 'foo';
        $object = [$nestedObject];
        $configuration = [];
        $expected = [['value1' => 'foo']];
        $output[] = [$object, $configuration, $expected, 'array of objects should be serialized'];

        $properties = ['foo' => 'bar', 'prohibited' => 'xxx'];
        // Mock built in the test method (data providers must be static in PHPUnit 11).
        $object = ['__mock' => NestedTestObject::class, '__properties' => $properties];
        $configuration = [
            '_only' => ['name', 'path', 'properties'],
            '_descend' => [
                 'properties' => [
                      '_exclude' => ['prohibited']
                 ]
            ]
        ];
        $expected = [
            'name' => 'name',
            'path' => 'path',
            'properties' => ['foo' => 'bar']
        ];
        $output[] = [$object, $configuration, $expected, 'descending into arrays should be possible'];

        $nestedObject = new \stdClass();
        $nestedObject->value1 = 'foo';
        $value = new \SplObjectStorage();
        $value->attach($nestedObject);
        $configuration = [];
        $expected = [['value1' => 'foo']];
        $output[] = [$value, $configuration, $expected, 'SplObjectStorage with objects should be serialized'];

        $dateTimeObject = new \DateTime('2011-02-03T03:15:23', new \DateTimeZone('UTC'));
        $configuration = [];
        $expected = '2011-02-03T03:15:23+00:00';
        $output[] = [$dateTimeObject, $configuration, $expected, 'DateTime object in UTC time zone could not be serialized.'];

        $dateTimeObject = new \DateTime('2013-08-15T15:25:30', new \DateTimeZone('America/Los_Angeles'));
        $configuration = [];
        $expected = '2013-08-15T15:25:30-07:00';
        $output[] = [$dateTimeObject, $configuration, $expected, 'DateTime object in America/Los_Angeles time zone could not be serialized.'];
        return $output;
    }

    #[DataProvider('jsonViewTestData')]
    #[Test]
    public function testTransformValue($object, $configuration, $expected, $description)
    {
        if (is_array($object) && isset($object['__mock'])) {
            $mock = $this->createMock($object['__mock']);
            $mock->method('getName')->willReturn('name');
            $mock->method('getPath')->willReturn('path');
            $mock->method('getProperties')->willReturn($object['__properties']);
            $mock->expects($this->never())->method('getOther');
            $object = $mock;
        }

        $jsonView = $this->getAccessibleMock(JsonView::class, [], [], '');

        $actual = $jsonView->_call('transformValue', $object, $configuration);

        self::assertEquals($expected, $actual, $description);
    }

    /**
     * data provider for testTransformValueWithObjectIdentifierExposure()
     * @return array
     */
    public static function objectIdentifierExposureTestData()
    {
        $output = [];

        $dummyIdentifier = 'e4f40dfc-8c6e-4414-a5b1-6fd3c5cf7a53';

        $object = new \stdClass();
        $object->value1 = new \stdClass();
        $configuration = [
            '_descend' => [
                 'value1' => [
                      '_exposeObjectIdentifier' => true
                 ]
            ]
        ];

        $expected = ['value1' => ['__identity' => $dummyIdentifier]];
        $output[] = [$object, $configuration, $expected, $dummyIdentifier, 'boolean true should result in __identity key'];

        $configuration['_descend']['value1']['_exposedObjectIdentifierKey'] = 'guid';
        $expected = ['value1' => ['guid' => $dummyIdentifier]];
        $output[] = [$object, $configuration, $expected, $dummyIdentifier, 'string value should result in string-equal key'];

        return $output;
    }

    #[DataProvider('objectIdentifierExposureTestData')]
    #[Test]
    public function testTransformValueWithObjectIdentifierExposure($object, $configuration, $expected, $dummyIdentifier, $description)
    {
        $persistenceManagerMock = $this->getMockBuilder(PersistenceManager::class)->onlyMethods(['getIdentifierByObject'])->getMock();
        $jsonView = $this->getAccessibleMock(JsonView::class, [], [], '', false);
        $jsonView->_set('persistenceManager', $persistenceManagerMock);

        $persistenceManagerMock->expects($this->once())->method('getIdentifierByObject')->with($object->value1)->willReturn(($dummyIdentifier));

        $actual = $jsonView->_call('transformValue', $object, $configuration);

        self::assertEquals($expected, $actual, $description);
    }

    /**
     * A data provider
     */
    public static function exposeClassNameSettingsAndResults(): \Iterator
    {
        $className = 'DummyClass' . md5(uniqid((string)mt_rand(), true));
        $namespace = 'Neos\Flow\Tests\Unit\Mvc\View\\' . $className;
        yield [
            JsonView::EXPOSE_CLASSNAME_FULLY_QUALIFIED,
            $className,
            $namespace,
            ['value1' => ['__class' => $namespace . '\\' . $className]]
        ];
        yield [
            JsonView::EXPOSE_CLASSNAME_UNQUALIFIED,
            $className,
            $namespace,
            ['value1' => ['__class' => $className]]
        ];
        yield [
            null,
            $className,
            $namespace,
            ['value1' => []]
        ];
    }

    #[DataProvider('exposeClassNameSettingsAndResults')]
    #[Test]
    public function viewExposesClassNameFullyIfConfiguredSo($exposeClassNameSetting, $className, $namespace, $expected)
    {
        $fullyQualifiedClassName = $namespace . '\\' . $className;
        if (class_exists($fullyQualifiedClassName) === false) {
            eval('namespace ' . $namespace . '; class ' . $className . ' {}');
        }

        $object = new \stdClass();
        $object->value1 = new $fullyQualifiedClassName();
        $configuration = [
            '_descend' => [
                 'value1' => [
                      '_exposeClassName' => $exposeClassNameSetting
                 ]
            ]
        ];

        $jsonView = $this->getAccessibleMock(JsonView::class, [], [], '', false);
        $actual = $jsonView->_call('transformValue', $object, $configuration);
        self::assertEquals($expected, $actual);
    }

    /**
     * @test_disabled
     */
    public function renderSetsContentTypeHeader()
    {
        $this->response->expects($this->once())->method('setHeader')->with('Content-Type', 'application/json');

        $this->view->render()->getBody()->getContents();
    }

    #[Test]
    public function renderReturnsJsonRepresentationOfAssignedObject()
    {
        $object = new \stdClass();
        $object->foo = 'Foo';
        $this->view->assign('value', $object);

        $expectedResult = '{"foo":"Foo"}';
        $actualResult = $this->view->render()->getBody()->getContents();
        self::assertEquals($expectedResult, $actualResult);
    }

    #[Test]
    public function renderReturnsJsonRepresentationOfAssignedArray()
    {
        $array = ['foo' => 'Foo', 'bar' => 'Bar'];
        $this->view->assign('value', $array);

        $expectedResult = '{"foo":"Foo","bar":"Bar"}';
        $actualResult = $this->view->render()->getBody()->getContents();
        self::assertEquals($expectedResult, $actualResult);
    }

    #[Test]
    public function renderReturnsJsonRepresentationOfAssignedSimpleValue()
    {
        $value = 'Foo';
        $this->view->assign('value', $value);

        $expectedResult = '"Foo"';
        $actualResult = $this->view->render()->getBody()->getContents();
        self::assertEquals($expectedResult, $actualResult);
    }

    #[Test]
    public function renderReturnsNullIfNameOfAssignedVariableIsNotEqualToValue()
    {
        $value = 'Foo';
        $this->view->assign('foo', $value);

        $expectedResult = 'null';
        $actualResult = $this->view->render()->getBody()->getContents();
        self::assertEquals($expectedResult, $actualResult);
    }

    #[Test]
    public function renderOnlyRendersVariableWithTheNameValue()
    {
        $this->view
            ->assign('value', 'Value')
            ->assign('someOtherVariable', 'Foo');

        $expectedResult = '"Value"';
        $actualResult = $this->view->render()->getBody()->getContents();
        self::assertEquals($expectedResult, $actualResult);
    }

    #[Test]
    public function setVariablesToRenderOverridesValueToRender()
    {
        $value = 'Foo';
        $this->view->assign('foo', $value);
        $this->view->setVariablesToRender(['foo']);

        $expectedResult = '"Foo"';
        $actualResult = $this->view->render()->getBody()->getContents();
        self::assertEquals($expectedResult, $actualResult);
    }

    #[Test]
    public function renderRendersMultipleValuesIfTheyAreSpecifiedAsVariablesToRender()
    {
        $this->view
            ->assign('value', 'Value1')
            ->assign('secondValue', 'Value2')
            ->assign('someOtherVariable', 'Value3');
        $this->view->setVariablesToRender(['value', 'secondValue']);

        $expectedResult = '{"value":"Value1","secondValue":"Value2"}';
        $actualResult = $this->view->render()->getBody()->getContents();
        self::assertEquals($expectedResult, $actualResult);
    }

    #[Test]
    public function renderCanRenderMultipleComplexObjects()
    {
        $array = ['foo' => ['bar' => 'Baz']];
        $object = new \stdClass();
        $object->foo = 'Foo';

        $this->view
            ->assign('array', $array)
            ->assign('object', $object)
            ->assign('someOtherVariable', 'Value3');
        $this->view->setVariablesToRender(['array', 'object']);

        $expectedResult = '{"array":{"foo":{"bar":"Baz"}},"object":{"foo":"Foo"}}';
        $actualResult = $this->view->render()->getBody()->getContents();
        self::assertEquals($expectedResult, $actualResult);
    }

    #[Test]
    public function renderCanRenderPlainArray()
    {
        $array = [['name' => 'Foo', 'secret' => true], ['name' => 'Bar', 'secret' => true]];

        $this->view->assign('value', $array);
        $this->view->setConfiguration([
            'value' => [
                '_descendAll' => [
                    '_only' => ['name']
                ]
            ]
        ]);

        $expectedResult = '[{"name":"Foo"},{"name":"Bar"}]';
        $actualResult = $this->view->render()->getBody()->getContents();
        self::assertEquals($expectedResult, $actualResult);
    }

    #[Test]
    public function descendAllKeepsArrayIndexes()
    {
        $array = [['name' => 'Foo', 'secret' => true], ['name' => 'Bar', 'secret' => true]];

        $this->view->assign('value', $array);
        $this->view->setConfiguration([
            'value' => [
                '_descendAll' => [
                    '_descendAll' => []
                ]
            ]
        ]);

        $expectedResult = '[{"name":"Foo","secret":true},{"name":"Bar","secret":true}]';
        $actualResult = $this->view->render()->getBody()->getContents();
        self::assertEquals($expectedResult, $actualResult);
    }

    #[Test]
    public function renderTransformsJsonSerializableValues()
    {
        $value = $this->getMockBuilder('JsonSerializable')->onlyMethods(['jsonSerialize'])->getMock();
        $value->method('jsonSerialize')->willReturn((['name' => 'Foo', 'age' => 42]));

        $this->view->assign('value', $value);
        $this->view->setConfiguration([
            'value' => [
                '_only' => ['name']
            ]
        ]);

        $expectedResult = '{"name":"Foo"}';
        $actualResult = $this->view->render()->getBody()->getContents();
        self::assertEquals($expectedResult, $actualResult);
    }

    #[Test]
    public function viewAcceptsJsonEncodingOptions()
    {
        $array = ['foo' => ['bar' => 'Baz', 'foo' => '1']];

        $this->view->setOption('jsonEncodingOptions', JSON_PRETTY_PRINT);
        $this->view->assign('array', $array);
        $this->view->setVariablesToRender(['array']);

        $expectedResult = json_encode($array, JSON_PRETTY_PRINT);

        $actualResult = $this->view->render()->getBody()->getContents();
        self::assertEquals($expectedResult, $actualResult);

        $unexpectedResult = json_encode($array);
        self::assertNotEquals($unexpectedResult, $actualResult);
    }

    #[Test]
    public function viewObeysDateTimeFormatOption()
    {
        $array = ['foo' => new \DateTime('2021-05-02T13:00:00+0000')];

        $this->view->setOption('datetimeFormat', 'Y-m-d H:i:s T');
        $this->view->assign('array', $array);
        $this->view->setVariablesToRender(['array']);

        $expectedResult = json_encode(['foo' => '2021-05-02 13:00:00 GMT+0000']);

        $actualResult = $this->view->render()->getBody()->getContents();
        $this->assertEquals($expectedResult, $actualResult);
    }
}
