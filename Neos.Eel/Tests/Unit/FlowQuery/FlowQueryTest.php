<?php

declare(strict_types=1);

namespace Neos\Eel\Tests\Unit\FlowQuery;

/*
 * This file is part of the Neos.Eel package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */
use Neos\Eel\FlowQuery\FizzleException;
use Neos\Eel\FlowQuery\FlowQuery;
use Neos\Eel\FlowQuery\OperationResolver;
use Neos\Eel\FlowQuery\Operations\CountOperation;
use Neos\Eel\FlowQuery\Operations\FirstOperation;
use Neos\Eel\FlowQuery\Operations\GetOperation;
use Neos\Eel\FlowQuery\Operations\IsOperation;
use Neos\Eel\FlowQuery\Operations\LastOperation;
use Neos\Eel\FlowQuery\Operations\Object\ChildrenOperation;
use Neos\Eel\FlowQuery\Operations\Object\FilterOperation;
use Neos\Eel\FlowQuery\Operations\Object\PropertyOperation;
use Neos\Eel\FlowQuery\Operations\SliceOperation;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Flow\Tests\UnitTestCase;
use Neos\Utility\ObjectAccess;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

/**
 * FlowQuery test
 */
final class FlowQueryTest extends UnitTestCase
{
    /**
     * @var PersistenceManagerInterface
     */
    protected $mockPersistenceManager;

    #[Test]
    public function constructWithFlowQueryIsIdempotent(): void
    {
        $flowQuery = new FlowQuery(['a', 'b', 'c']);
        $wrappedQuery = new FlowQuery($flowQuery);

        self::assertEquals($flowQuery->getContext(), $wrappedQuery->getContext());
    }

    #[Test]
    public function firstReturnsFirstObject(): void
    {
        $myObject = new \stdClass();
        $myObject2 = new \stdClass();

        $query = $this->createFlowQuery([$myObject, $myObject2]);
        self::assertInstanceOf(FlowQuery::class, $query->first());
        self::assertSame([$myObject], $query->first()->get());
        self::assertSame([$myObject], iterator_to_array($query->first()));
    }

    #[Test]
    public function lastReturnsLastObject(): void
    {
        $myObject = new \stdClass();
        $myObject2 = new \stdClass();

        $query = $this->createFlowQuery([$myObject, $myObject2]);
        self::assertInstanceOf(FlowQuery::class, $query->last());
        self::assertSame([$myObject2], $query->last()->get());
        self::assertSame([$myObject2], iterator_to_array($query->last()));
    }

    #[Test]
    public function sliceReturnsSlicedObject(): void
    {
        $myObject = new \stdClass();
        $myObject2 = new \stdClass();
        $myObject3 = new \stdClass();

        $query = $this->createFlowQuery([$myObject, $myObject2, $myObject3]);
        self::assertInstanceOf(FlowQuery::class, $query->slice());
        self::assertSame([$myObject, $myObject2, $myObject3], $query->slice()->get());
        self::assertSame([$myObject, $myObject2, $myObject3], iterator_to_array($query->slice()));
        self::assertSame([$myObject, $myObject2], $query->slice(0, 2)->get());
        self::assertSame([$myObject, $myObject2], iterator_to_array($query->slice(0, 2)));
        self::assertSame([$myObject3], $query->slice(2)->get());
        self::assertSame([$myObject3], iterator_to_array($query->slice(2)));
    }

    #[Test]
    public function filterOperationFiltersArrays(): void
    {
        $myObject = new \stdClass();
        $myObject->arrayProperty = ['foo','bar','baz'];
        $myObject2 = new \stdClass();
        $myObject2->arrayProperty = ['foo','zang','zong'];
        $myObject3 = new \stdClass();
        $myObject3->arrayProperty = ['zing','zang','zong'];

        $query = $this->createFlowQuery([$myObject, $myObject2, $myObject3]);


        self::assertInstanceOf(FlowQuery::class, $query->filter('[arrayProperty *= bar]'));
        self::assertSame([$myObject], $query->filter('[arrayProperty *= bar]')->get());

        self::assertInstanceOf(FlowQuery::class, $query->filter('[arrayProperty *= foo]'));
        self::assertSame([$myObject, $myObject2], $query->filter('[arrayProperty *= foo]')->get());

        self::assertInstanceOf(FlowQuery::class, $query->filter('[arrayProperty *= ding]'));
        self::assertSame([], $query->filter('[arrayProperty *= ding]')->get());

        self::assertInstanceOf(FlowQuery::class, $query->filter('[arrayProperty *= fo]'));
        self::assertSame([], $query->filter('[arrayProperty *= fo]')->get());


        self::assertInstanceOf(FlowQuery::class, $query->filter('[arrayProperty *=~ bAr]'));
        self::assertSame([$myObject], $query->filter('[arrayProperty *=~ bAr]')->get());

        self::assertInstanceOf(FlowQuery::class, $query->filter('[arrayProperty *=~ fOo]'));
        self::assertSame([$myObject, $myObject2], $query->filter('[arrayProperty *=~ fOo]')->get());

        self::assertInstanceOf(FlowQuery::class, $query->filter('[arrayProperty *=~ dIng]'));
        self::assertSame([], $query->filter('[arrayProperty *=~ dIng]')->get());

        self::assertInstanceOf(FlowQuery::class, $query->filter('[arrayProperty *=~ fO]'));
        self::assertSame([], $query->filter('[arrayProperty *=~ fO]')->get());


        self::assertInstanceOf(FlowQuery::class, $query->filter('[arrayProperty ^= zing]'));
        self::assertSame([$myObject3], $query->filter('[arrayProperty ^= zing]')->get());

        self::assertInstanceOf(FlowQuery::class, $query->filter('[arrayProperty ^= foo]'));
        self::assertSame([$myObject, $myObject2], $query->filter('[arrayProperty ^= foo]')->get());

        self::assertInstanceOf(FlowQuery::class, $query->filter('[arrayProperty ^= ding]'));
        self::assertSame([], $query->filter('[arrayProperty ^= ding]')->get());

        self::assertInstanceOf(FlowQuery::class, $query->filter('[arrayProperty ^= zi]'));
        self::assertSame([], $query->filter('[arrayProperty ^= zi]')->get());

        self::assertInstanceOf(FlowQuery::class, $query->filter('[arrayProperty ^= bar]'));
        self::assertSame([], $query->filter('[arrayProperty ^= bar]')->get());


        self::assertInstanceOf(FlowQuery::class, $query->filter('[arrayProperty ^=~ zIng]'));
        self::assertSame([$myObject3], $query->filter('[arrayProperty ^=~ zIng]')->get());

        self::assertInstanceOf(FlowQuery::class, $query->filter('[arrayProperty ^=~ fOo]'));
        self::assertSame([$myObject, $myObject2], $query->filter('[arrayProperty ^=~ fOo]')->get());

        self::assertInstanceOf(FlowQuery::class, $query->filter('[arrayProperty ^=~ dIng]'));
        self::assertSame([], $query->filter('[arrayProperty ^=~ dIng]')->get());

        self::assertInstanceOf(FlowQuery::class, $query->filter('[arrayProperty ^=~ zI]'));
        self::assertSame([], $query->filter('[arrayProperty ^=~ zI]')->get());

        self::assertInstanceOf(FlowQuery::class, $query->filter('[arrayProperty ^=~ bAr]'));
        self::assertSame([], $query->filter('[arrayProperty ^=~ bAr]')->get());


        self::assertInstanceOf(FlowQuery::class, $query->filter('[arrayProperty $= baz]'));
        self::assertSame([$myObject], $query->filter('[arrayProperty $= baz]')->get());

        self::assertInstanceOf(FlowQuery::class, $query->filter('[arrayProperty $= zong]'));
        self::assertSame([$myObject2, $myObject3], $query->filter('[arrayProperty $= zong]')->get());

        self::assertInstanceOf(FlowQuery::class, $query->filter('[arrayProperty $= ding]'));
        self::assertSame([], $query->filter('[arrayProperty $= ding]')->get());

        self::assertInstanceOf(FlowQuery::class, $query->filter('[arrayProperty $= az]'));
        self::assertSame([], $query->filter('[arrayProperty $= az]')->get());

        self::assertInstanceOf(FlowQuery::class, $query->filter('[arrayProperty $= bar]'));
        self::assertSame([], $query->filter('[arrayProperty $= bar]')->get());


        self::assertInstanceOf(FlowQuery::class, $query->filter('[arrayProperty $=~ bAz]'));
        self::assertSame([$myObject], $query->filter('[arrayProperty $=~ bAz]')->get());

        self::assertInstanceOf(FlowQuery::class, $query->filter('[arrayProperty $=~ zOng]'));
        self::assertSame([$myObject2, $myObject3], $query->filter('[arrayProperty $=~ zOng]')->get());

        self::assertInstanceOf(FlowQuery::class, $query->filter('[arrayProperty $=~ dIng]'));
        self::assertSame([], $query->filter('[arrayProperty $=~ dIng]')->get());

        self::assertInstanceOf(FlowQuery::class, $query->filter('[arrayProperty $=~ aZ]'));
        self::assertSame([], $query->filter('[arrayProperty $=~ aZ]')->get());

        self::assertInstanceOf(FlowQuery::class, $query->filter('[arrayProperty $=~ bAr]'));
        self::assertSame([], $query->filter('[arrayProperty $=~ bAr]')->get());
    }

    /**
     * @return \Iterator<(int | string), mixed>
     */
    public static function dataProviderForFilter(): \Iterator
    {
        $myObject = new \stdClass();
        $myObject->myProperty = 'asdf';
        $myObject->myProperty2 = 'asdf';

        $myObject2 = new \stdClass();
        $myObject2->__identity = 'object-identifier-A1-B2';

        $myObject3 = new \stdClass();
        $myObject3->myProperty = 'aaa';

        $myObject4 = new \stdClass();
        $myObject4->name = 'Robert';

        $myObject5 = new \stdClass();
        $myObject5->isHidden = true;

        $myObject6 = new \stdClass();
        $myObject6->aNumber = 42;

        $myObject7 = new \stdClass();
        $myObject7->aNumber = 142;

        $myObject8 = new \stdClass();
        $myObject8->resource = new \stdClass();
        $myObject8->resource->fileExtension = "pdf";
        yield 'Property existance test works' => [
            'sourceObjects' => [$myObject, $myObject2],
            'filter' => '[myProperty]',
            'expectedResult' => [$myObject]
        ];
        yield 'Multiple filters are combined with AND together' => [
            'sourceObjects' => [$myObject, $myObject2, $myObject3],
            'filter' => '[myProperty][myProperty2]',
            'expectedResult' => [$myObject]
        ];
        yield 'Multiple filters can be ORed together using comma' => [
            'sourceObjects' => [$myObject, $myObject2, $myObject3, $myObject4],
            'filter' => '[myProperty2], [name]',
            'expectedResult' => [$myObject, $myObject4]
        ];
        yield 'Exact matches are supported' => [
            'sourceObjects' => [$myObject, $myObject2, $myObject3, $myObject4],
            'filter' => '[myProperty=asdf]',
            'expectedResult' => [$myObject]
        ];
        yield 'Exact match of property path is supported' => [
            'sourceObjects' => [$myObject, $myObject2, $myObject3, $myObject4, $myObject8],
            'filter' => '[resource.fileExtension=pdf]',
            'expectedResult' => [$myObject8]
        ];
        yield 'Boolean matches' => [
            'sourceObjects' => [$myObject, $myObject2, $myObject3, $myObject4, $myObject5, $myObject6],
            'filter' => '[isHidden=true]',
            'expectedResult' => [$myObject5]
        ];
        yield 'Integer matches' => [
            'sourceObjects' => [$myObject, $myObject2, $myObject3, $myObject4, $myObject5, $myObject6],
            'filter' => '[aNumber = 42]',
            'expectedResult' => [$myObject6]
        ];
        yield 'Instanceof test works (1)' => [
            'sourceObjects' => [$myObject],
            'filter' => '[instanceof foo]',
            'expectedResult' => []
        ];
        yield 'Instanceof test works (2)' => [
            'sourceObjects' => [$myObject],
            'filter' => '[  instanceof \stdClass  ]',
            'expectedResult' => [$myObject]
        ];
        yield 'Instanceof test works (with test for object)' => [
            'sourceObjects' => [$myObject],
            'filter' => '[  instanceof object  ]',
            'expectedResult' => [$myObject]
        ];
        yield 'Instanceof test works (with test for string)' => [
            'sourceObjects' => ['myString'],
            'filter' => '[  instanceof string  ]',
            'expectedResult' => ['myString']
        ];
        yield 'Instanceof test works (with test for integer)' => [
            'sourceObjects' => [42, '42', 400, 'foo'],
            'filter' => '[  instanceof integer  ]',
            'expectedResult' => [42, 400]
        ];
        yield 'Instanceof test works (with test for integer 2)' => [
            'sourceObjects' => [42, '42', 400, 'foo'],
            'filter' => '[  instanceof int  ]',
            'expectedResult' => [42, 400]
        ];
        yield 'Instanceof test works (with test for boolean)' => [
            'sourceObjects' => [false, '', true],
            'filter' => '[  instanceof boolean  ]',
            'expectedResult' => [false, true]
        ];
        yield 'Instanceof test works (with test for float)' => [
            'sourceObjects' => [false, 42, 42.5, true],
            'filter' => '[  instanceof float  ]',
            'expectedResult' => [42.5]
        ];
        yield 'Instanceof test works (with test for array)' => [
            'sourceObjects' => [false, 42, 42.5, true, ['foo']],
            'filter' => '[  instanceof array  ]',
            'expectedResult' => [['foo']]
        ];
        yield 'Instanceof test works on attributes' => [
            'sourceObjects' => [$myObject, $myObject2, $myObject3, $myObject4, $myObject5, $myObject6],
            'filter' => '[ isHidden instanceof boolean ]',
            'expectedResult' => [$myObject5]
        ];
        yield 'Notinstanceof test works (1)' => [
            'sourceObjects' => [$myObject],
            'filter' => '[!instanceof foo]',
            'expectedResult' => [$myObject]
        ];
        yield 'Notinstanceof test works (2)' => [
            'sourceObjects' => [$myObject],
            'filter' => '[  !instanceof \stdClass  ]',
            'expectedResult' => []
        ];
        yield 'Notinstanceof test works (with test for object)' => [
            'sourceObjects' => [$myObject],
            'filter' => '[  !instanceof object  ]',
            'expectedResult' => []
        ];
        yield 'Notinstanceof test works (with test for string)' => [
            'sourceObjects' => ['myString'],
            'filter' => '[  !instanceof string  ]',
            'expectedResult' => []
        ];
        yield 'Notinstanceof test works (with test for integer)' => [
            'sourceObjects' => [42, '42', 400, 'foo'],
            'filter' => '[  !instanceof integer  ]',
            'expectedResult' => ['42', 'foo']
        ];
        yield 'Notinstanceof test works (with test for integer 2)' => [
            'sourceObjects' => [42, '42', 400, 'foo'],
            'filter' => '[  !instanceof int  ]',
            'expectedResult' => ['42', 'foo']
        ];
        yield 'Notinstanceof test works (with test for boolean)' => [
            'sourceObjects' => [false, '', true],
            'filter' => '[  !instanceof boolean  ]',
            'expectedResult' => ['']
        ];
        yield 'Notinstanceof test works (with test for float)' => [
            'sourceObjects' => [false, 42, 42.5, true],
            'filter' => '[  !instanceof float  ]',
            'expectedResult' => [false, 42, true]
        ];
        yield 'Notinstanceof test works (with test for array)' => [
            'sourceObjects' => [false, 42, 42.5, true, ['foo']],
            'filter' => '[  !instanceof array  ]',
            'expectedResult' => [false, 42, 42.5, true]
        ];
        yield 'Notinstanceof test works on attributes' => [
            'sourceObjects' => [$myObject, $myObject2, $myObject3, $myObject4, $myObject5, $myObject6],
            'filter' => '[ isHidden !instanceof boolean ]',
            'expectedResult' => [$myObject, $myObject2, $myObject3, $myObject4, $myObject6]
        ];
        yield 'Begin query match' => [
            'sourceObjects' => [$myObject, $myObject2, $myObject3, $myObject4],
            'filter' => '[ myProperty ^= as ]',
            'expectedResult' => [$myObject]
        ];
        yield 'End query match (1)' => [
            'sourceObjects' => [$myObject, $myObject2, $myObject3, $myObject4],
            'filter' => '[ myProperty $= df ]',
            'expectedResult' => [$myObject]
        ];
        yield 'End query match (2)' => [
            'sourceObjects' => [$myObject, $myObject2, $myObject3, $myObject4],
            'filter' => '[ myProperty $= a ]',
            'expectedResult' => [$myObject3]
        ];
        yield 'In-Between Query Match' => [
            'sourceObjects' => [$myObject, $myObject2, $myObject3, $myObject4],
            'filter' => '[ myProperty *= sd ]',
            'expectedResult' => [$myObject]
        ];
        yield 'Identifier match' => [
            'sourceObjects' => [$myObject, $myObject2, $myObject3, $myObject4],
            'filter' => '#object-identifier-A1-B2',
            'expectedResult' => [$myObject2]
        ];
        yield 'Not equals query match' => [
            'sourceObjects' => [$myObject, $myObject2, $myObject3, $myObject4],
            'filter' => '[ myProperty != asdf ]',
            'expectedResult' => [$myObject2, $myObject3, $myObject4]
        ];
        yield 'Less than query match' => [
            'sourceObjects' => [$myObject6, $myObject7],
            'filter' => '[ aNumber < 50 ]',
            'expectedResult' => [$myObject6]
        ];
        yield 'Less than or equal to query match' => [
            'sourceObjects' => [$myObject6, $myObject7],
            'filter' => '[ aNumber <= 42 ]',
            'expectedResult' => [$myObject6]
        ];
        yield 'Greater than query match' => [
            'sourceObjects' => [$myObject6, $myObject7],
            'filter' => '[ aNumber > 50 ]',
            'expectedResult' => [$myObject7]
        ];
        yield 'Greater than or equal to query match' => [
            'sourceObjects' => [$myObject6, $myObject7],
            'filter' => '[ aNumber >= 42 ]',
            'expectedResult' => [$myObject6, $myObject7]
        ];
    }

    #[DataProvider('dataProviderForFilter')]
    #[Test]
    public function filterCanFilterObjects($sourceObjects, $filter, $expectedResult): void
    {
        $query = $this->createFlowQuery($sourceObjects);
        $filterObject = $query->filter($filter);
        self::assertInstanceOf(FlowQuery::class, $filterObject);
        self::assertSame($expectedResult, iterator_to_array($filterObject));
    }

    #[DataProvider('dataProviderForFilter')]
    #[Test]
    public function isCanFilterObjects($sourceObjects, $filter, $expectedResult): void
    {
        $query = $this->createFlowQuery($sourceObjects);
        self::assertSame(count($expectedResult) > 0, $query->is($filter));
    }

    #[DataProvider('dataProviderForFilter')]
    #[Test]
    public function countReturnsCorrectNumber($sourceObjects, $filter, $expectedResult): void
    {
        $query = $this->createFlowQuery($sourceObjects);
        self::assertSame(count($expectedResult), $query->filter($filter)->count());
        self::assertCount(count($sourceObjects), $query);
        self::assertCount(count($sourceObjects), $query);
    }

    #[Test]
    public function filterOperationFiltersNumbersCorrectly(): void
    {
        $myObject = new \stdClass();
        $myObject->stringProperty = '1foo bar baz2';
        $myObject2 = new \stdClass();
        $myObject2->stringProperty = "1zing zang zong";
        $myObject3 = new \stdClass();
        $myObject3->stringProperty = "fing', 'fan33g', 'fong";
        $query = $this->createFlowQuery([$myObject, $myObject2, $myObject3]);

        self::assertInstanceOf(FlowQuery::class, $query->filter('[stringProperty $= 2]'));
        self::assertSame([$myObject], $query->filter('[stringProperty $= 2]')->get());

        self::assertInstanceOf(FlowQuery::class, $query->filter('[stringProperty *= 33]'));
        self::assertSame([$myObject3], $query->filter('[stringProperty *= 33]')->get());

        self::assertInstanceOf(FlowQuery::class, $query->filter('[stringProperty *= "n33g"]'));
        self::assertSame([$myObject3], $query->filter('[stringProperty *= "n33g"]')->get());

        self::assertInstanceOf(FlowQuery::class, $query->filter('[stringProperty $= "2"]'));
        self::assertSame([$myObject], $query->filter('[stringProperty $= "2"]')->get());

        self::assertInstanceOf(FlowQuery::class, $query->filter('[stringProperty *= 2]'));
        self::assertSame([$myObject], $query->filter('[stringProperty *= 2]')->get());
    }

    /**
     * @return \Iterator<(int | string), mixed>
     */
    public static function dataProviderForChildrenAndFilterAndProperty(): \Iterator
    {
        $person1 = new \stdClass();
        $person1->name = 'Kasper Skaarhoj';
        $address1_1 = new \stdClass();
        $address1_1->street = 'SomeCopenhagenStreet';
        $address1_1->city = 'Kopenhagen';
        $address1_1->country = 'Denmark';
        $person1->address = $address1_1;

        $person2 = new \stdClass();
        $person2->name = 'Robert Lemke';
        $address2_1 = new \stdClass();
        $address2_1->street = 'SomeLübeckStreet';
        $address2_1->city = 'Lübeck';
        $address2_1->country = 'Germany';
        $person2->address = $address2_1;

        $person3 = new \stdClass();
        $person3->name = 'Sebastian Kurfuerst';
        $address3_1 = new \stdClass();
        $address3_1->street = 'SomeDresdenStreet';
        $address3_1->city = 'Dresden';
        $address3_1->country = 'Germany';
        $person3->address = $address3_1;

        $person4 = new \stdClass();
        $person4->name = 'Somebody without address';
        yield 'children() on empty array always returns empty flowquery object' => [
            'sourceObjects' => [],
            'expressions' => [
                '$query->children("foo[bar]")',
                '$query->children("foo")',
                '$query->children("[instanceof Something]")',
                '$query->children()'
            ],
            'expectedResult' => []
        ];
        yield 'children() with property name filter returns all corresponding child objects' => [
            'sourceObjects' => [$person1, $person2, $person3, $person4],
            'expressions' => [
                '$query->children("address")',
                '$query->children()->filter("address")',
            ],
            'expectedResult' => [$address1_1, $address2_1, $address3_1]
        ];
        yield 'children() with property name and attribute filter returns all corresponding child objects' => [
            'sourceObjects' => [$person1, $person2, $person3, $person4],
            'expressions' => [
                '$query->children("address[country=Germany]")',
                '$query->children("address")->filter("[country=Germany]")',
                '$query->children()->filter("address[country=Germany]")',
            ],
            'expectedResult' => [$address2_1, $address3_1]
        ];
        yield 'property() with property name returns object accessor on first object' => [
            'sourceObjects' => [$person1, $person2, $person3, $person4],
            'expressions' => [
                '$query->property("address")'
            ],
            'expectedResult' => $address1_1,
            'isFinal' => true
        ];
        yield 'property() with property name works with property paths' => [
            'sourceObjects' => [$person1, $person2, $person3, $person4],
            'expressions' => [
                '$query->property("address.street")'
            ],
            'expectedResult' => 'SomeCopenhagenStreet',
            'isFinal' => true
        ];
    }

    #[DataProvider('dataProviderForChildrenAndFilterAndProperty')]
    #[Test]
    public function childrenAndFilterAndPropertyWorks($sourceObjects, array $expressions, $expectedResult, $isFinal = false): void
    {
        $query = $this->createFlowQuery($sourceObjects);
        foreach ($expressions as $expression) {
            eval('$result = ' . $expression . ';');
            if (!$isFinal) {
                self::assertInstanceOf(FlowQuery::class, $result);
                $result = iterator_to_array($result);
            }
            self::assertSame($expectedResult, $result, 'Expression "' . $expression . '" did not match expected result');
        }
    }

    /**
     * @return \Iterator<(int | string), mixed>
     */
    public static function dataProviderForErrorQueries(): \Iterator
    {
        yield ['$query->children()'];
        yield ['$query->children("")'];
        yield ['$query->children("[foo]")'];
        yield ['$query->filter("foo")'];
        yield ['$query->children()->filter()'];
        yield ['$query->children()->filter("")'];
        yield ['$query->children("")->filter()'];
        yield ['$query->children("")->filter("")'];
        yield ['$query->children()->filter("[foo]")'];
        yield ['$query->children("foo")->filter("foo")'];
        yield ['$query->children("[foo]")->filter("foo")'];
        // TODO should we allow this, implicitely turning it around?
        yield ['$query->children("[foo]")->filter("[foo]")'];
        yield ['$query->children("foo")->filter("foo[foo]")'];
        yield ['$query->children("foo[foo]")->filter("foo[foo]")'];
    }

    #[DataProvider('dataProviderForErrorQueries')]
    #[Test]
    public function errorQueriesThrowError($expression): void
    {
        $this->expectException(FizzleException::class);

        $x = new \stdClass();
        $x->foo = new \stdClass();
        $x->foo->foo = 'asdf';
        $query = $this->createFlowQuery([$x]);
        eval('$result = ' . $expression . ';');
        self::assertInstanceOf(FlowQuery::class, $result);
        $result->getIterator(); // Throws exception
    }

    /**
     * @param array $elements
     * @return FlowQuery
     */
    protected function createFlowQuery(array $elements): FlowQuery
    {
        $flowQuery = $this->getAccessibleMock(FlowQuery::class, [], [$elements]);

        // Set up mock persistence manager to return dummy object identifiers
        $this->mockPersistenceManager = $this->createMock(PersistenceManagerInterface::class);
        $this->mockPersistenceManager->method('getIdentifierByObject')->willReturnCallBack(function ($object) {
            if (isset($object->__identity)) {
                return $object->__identity;
            }
        });

        $mockPersistenceManager = $this->mockPersistenceManager;
        $objectManager = $this->createMock(ObjectManagerInterface::class);
        $objectManager->method('get')->willReturnCallBack(function ($className) use ($mockPersistenceManager) {
            $instance = new $className();
            // Special case to inject the mock persistence manager into the filter operation
            if ($className === FilterOperation::class) {
                ObjectAccess::setProperty($instance, 'persistenceManager', $mockPersistenceManager, true);
            }
            return $instance;
        });

        $operationResolver = $this->getAccessibleMock(OperationResolver::class, []);
        $operationResolver->_set('objectManager', $objectManager);

        $operationResolver->_set('finalOperationNames', [
            'count' => 'count',
            'get' => 'get',
            'is' => 'is',
            'property' => 'property'
        ]);

        $operationResolver->_set('operations', [
            'count' => [300 => CountOperation::class],
            'first' => [300 => FirstOperation::class],
            'last' => [300 => LastOperation::class],
            'slice' => [300 => SliceOperation::class],
            'get' => [300 => GetOperation::class],
            'is' => [300 => IsOperation::class],
            'filter' => [300 => FilterOperation::class],
            'children' => [300 => ChildrenOperation::class],
            'property' => [300 => PropertyOperation::class]
        ]);

        $flowQuery->_set('operationResolver', $operationResolver);
        return $flowQuery;
    }
}
