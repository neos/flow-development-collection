<?php
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
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Utility\ObjectAccess;
use Neos\Flow\Tests\UnitTestCase;
use Neos\Eel\FlowQuery\Operations;

/**
 * FlowQuery test
 */
class FlowQueryTest extends UnitTestCase
{
    /**
     * @var PersistenceManagerInterface
     */
    protected $mockPersistenceManager;

    /**
     * @test
     */
    public function constructWithFlowQueryIsIdempotent()
    {
        $flowQuery = new FlowQuery(['a', 'b', 'c']);
        $wrappedQuery = new FlowQuery($flowQuery);

        self::assertEquals($flowQuery->getContext(), $wrappedQuery->getContext());
    }

    /**
     * @test
     */
    public function firstReturnsFirstObject()
    {
        $myObject = new \stdClass();
        $myObject2 = new \stdClass();

        $query = $this->createFlowQuery([$myObject, $myObject2]);
        self::assertInstanceOf(FlowQuery::class, $query->first());
        self::assertSame([$myObject], $query->first()->get());
        self::assertSame([$myObject], iterator_to_array($query->first()));
    }

    /**
     * @test
     */
    public function lastReturnsLastObject()
    {
        $myObject = new \stdClass();
        $myObject2 = new \stdClass();

        $query = $this->createFlowQuery([$myObject, $myObject2]);
        self::assertInstanceOf(FlowQuery::class, $query->last());
        self::assertSame([$myObject2], $query->last()->get());
        self::assertSame([$myObject2], iterator_to_array($query->last()));
    }

    /**
     * @test
     */
    public function sliceReturnsSlicedObject()
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

    /**
     * @test
     */
    public function filterOperationFiltersArrays()
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
     * @return array
     */
    public function dataProviderForFilter()
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

        return [
            'Property existance test works' => [
                'sourceObjects' => [$myObject, $myObject2],
                'filter' => '[myProperty]',
                'expectedResult' => [$myObject]
            ],
            'Multiple filters are combined with AND together' => [
                'sourceObjects' => [$myObject, $myObject2, $myObject3],
                'filter' => '[myProperty][myProperty2]',
                'expectedResult' => [$myObject]
            ],
            'Multiple filters can be ORed together using comma' => [
                'sourceObjects' => [$myObject, $myObject2, $myObject3, $myObject4],
                'filter' => '[myProperty2], [name]',
                'expectedResult' => [$myObject, $myObject4]
            ],
            'Exact matches are supported' => [
                'sourceObjects' => [$myObject, $myObject2, $myObject3, $myObject4],
                'filter' => '[myProperty=asdf]',
                'expectedResult' => [$myObject]
            ],
            'Exact match of property path is supported' => [
                'sourceObjects' => [$myObject, $myObject2, $myObject3, $myObject4, $myObject8],
                'filter' => '[resource.fileExtension=pdf]',
                'expectedResult' => [$myObject8]
            ],
            'Boolean matches' => [
                'sourceObjects' => [$myObject, $myObject2, $myObject3, $myObject4, $myObject5, $myObject6],
                'filter' => '[isHidden=true]',
                'expectedResult' => [$myObject5]
            ],
            'Integer matches' => [
                'sourceObjects' => [$myObject, $myObject2, $myObject3, $myObject4, $myObject5, $myObject6],
                'filter' => '[aNumber = 42]',
                'expectedResult' => [$myObject6]
            ],

            'Instanceof test works (1)' => [
                'sourceObjects' => [$myObject],
                'filter' => '[instanceof foo]',
                'expectedResult' => []
            ],
            'Instanceof test works (2)' => [
                'sourceObjects' => [$myObject],
                'filter' => '[  instanceof \stdClass  ]',
                'expectedResult' => [$myObject]
            ],
            'Instanceof test works (with test for object)' => [
                'sourceObjects' => [$myObject],
                'filter' => '[  instanceof object  ]',
                'expectedResult' => [$myObject]
            ],
            'Instanceof test works (with test for string)' => [
                'sourceObjects' => ['myString'],
                'filter' => '[  instanceof string  ]',
                'expectedResult' => ['myString']
            ],

            'Instanceof test works (with test for integer)' => [
                'sourceObjects' => [42, '42', 400, 'foo'],
                'filter' => '[  instanceof integer  ]',
                'expectedResult' => [42, 400]
            ],

            'Instanceof test works (with test for integer 2)' => [
                'sourceObjects' => [42, '42', 400, 'foo'],
                'filter' => '[  instanceof int  ]',
                'expectedResult' => [42, 400]
            ],

            'Instanceof test works (with test for boolean)' => [
                'sourceObjects' => [false, '', true],
                'filter' => '[  instanceof boolean  ]',
                'expectedResult' => [false, true]
            ],

            'Instanceof test works (with test for float)' => [
                'sourceObjects' => [false, 42, 42.5, true],
                'filter' => '[  instanceof float  ]',
                'expectedResult' => [42.5]
            ],

            'Instanceof test works (with test for array)' => [
                'sourceObjects' => [false, 42, 42.5, true, ['foo']],
                'filter' => '[  instanceof array  ]',
                'expectedResult' => [['foo']]
            ],

            'Instanceof test works on attributes' => [
                'sourceObjects' => [$myObject, $myObject2, $myObject3, $myObject4, $myObject5, $myObject6],
                'filter' => '[ isHidden instanceof boolean ]',
                'expectedResult' => [$myObject5]
            ],

            'Notinstanceof test works (1)' => [
                'sourceObjects' => [$myObject],
                'filter' => '[!instanceof foo]',
                'expectedResult' => [$myObject]
            ],
            'Notinstanceof test works (2)' => [
                'sourceObjects' => [$myObject],
                'filter' => '[  !instanceof \stdClass  ]',
                'expectedResult' => []
            ],
            'Notinstanceof test works (with test for object)' => [
                'sourceObjects' => [$myObject],
                'filter' => '[  !instanceof object  ]',
                'expectedResult' => []
            ],
            'Notinstanceof test works (with test for string)' => [
                'sourceObjects' => ['myString'],
                'filter' => '[  !instanceof string  ]',
                'expectedResult' => []
            ],

            'Notinstanceof test works (with test for integer)' => [
                'sourceObjects' => [42, '42', 400, 'foo'],
                'filter' => '[  !instanceof integer  ]',
                'expectedResult' => ['42', 'foo']
            ],

            'Notinstanceof test works (with test for integer 2)' => [
                'sourceObjects' => [42, '42', 400, 'foo'],
                'filter' => '[  !instanceof int  ]',
                'expectedResult' => ['42', 'foo']
            ],

            'Notinstanceof test works (with test for boolean)' => [
                'sourceObjects' => [false, '', true],
                'filter' => '[  !instanceof boolean  ]',
                'expectedResult' => ['']
            ],

            'Notinstanceof test works (with test for float)' => [
                'sourceObjects' => [false, 42, 42.5, true],
                'filter' => '[  !instanceof float  ]',
                'expectedResult' => [false, 42, true]
            ],

            'Notinstanceof test works (with test for array)' => [
                'sourceObjects' => [false, 42, 42.5, true, ['foo']],
                'filter' => '[  !instanceof array  ]',
                'expectedResult' => [false, 42, 42.5, true]
            ],

            'Notinstanceof test works on attributes' => [
                'sourceObjects' => [$myObject, $myObject2, $myObject3, $myObject4, $myObject5, $myObject6],
                'filter' => '[ isHidden !instanceof boolean ]',
                'expectedResult' => [$myObject, $myObject2, $myObject3, $myObject4, $myObject6]
            ],

            'Begin query match' => [
                'sourceObjects' => [$myObject, $myObject2, $myObject3, $myObject4],
                'filter' => '[ myProperty ^= as ]',
                'expectedResult' => [$myObject]
            ],

            'End query match (1)' => [
                'sourceObjects' => [$myObject, $myObject2, $myObject3, $myObject4],
                'filter' => '[ myProperty $= df ]',
                'expectedResult' => [$myObject]
            ],
            'End query match (2)' => [
                'sourceObjects' => [$myObject, $myObject2, $myObject3, $myObject4],
                'filter' => '[ myProperty $= a ]',
                'expectedResult' => [$myObject3]
            ],

            'In-Between Query Match' => [
                'sourceObjects' => [$myObject, $myObject2, $myObject3, $myObject4],
                'filter' => '[ myProperty *= sd ]',
                'expectedResult' => [$myObject]
            ],

            'Identifier match' => [
                'sourceObjects' => [$myObject, $myObject2, $myObject3, $myObject4],
                'filter' => '#object-identifier-A1-B2',
                'expectedResult' => [$myObject2]
            ],

            'Not equals query match' => [
                'sourceObjects' => [$myObject, $myObject2, $myObject3, $myObject4],
                'filter' => '[ myProperty != asdf ]',
                'expectedResult' => [$myObject2, $myObject3, $myObject4]
            ],

            'Less than query match' => [
                'sourceObjects' => [$myObject6, $myObject7],
                'filter' => '[ aNumber < 50 ]',
                'expectedResult' => [$myObject6]
            ],

            'Less than or equal to query match' => [
                'sourceObjects' => [$myObject6, $myObject7],
                'filter' => '[ aNumber <= 42 ]',
                'expectedResult' => [$myObject6]
            ],

            'Greater than query match' => [
                'sourceObjects' => [$myObject6, $myObject7],
                'filter' => '[ aNumber > 50 ]',
                'expectedResult' => [$myObject7]
            ],

            'Greater than or equal to query match' => [
                'sourceObjects' => [$myObject6, $myObject7],
                'filter' => '[ aNumber >= 42 ]',
                'expectedResult' => [$myObject6, $myObject7]
            ]
        ];
    }

    /**
     * @dataProvider dataProviderForFilter
     * @test
     */
    public function filterCanFilterObjects($sourceObjects, $filterString, $expected)
    {
        $query = $this->createFlowQuery($sourceObjects);
        $filter = $query->filter($filterString);
        self::assertInstanceOf(FlowQuery::class, $filter);
        self::assertSame($expected, iterator_to_array($filter));
    }

    /**
     * @dataProvider dataProviderForFilter
     * @test
     */
    public function isCanFilterObjects($sourceObjects, $filterString, $expectedResultArray)
    {
        $query = $this->createFlowQuery($sourceObjects);
        self::assertSame(count($expectedResultArray) > 0, $query->is($filterString));
    }

    /**
     * @dataProvider dataProviderForFilter
     * @test
     */
    public function countReturnsCorrectNumber($sourceObjects, $filterString, $expectedResultArray)
    {
        $query = $this->createFlowQuery($sourceObjects);
        self::assertSame(count($expectedResultArray), $query->filter($filterString)->count());
        self::assertSame(count($sourceObjects), $query->count());
        self::assertSame(count($sourceObjects), count($query));
    }

    /**
     * @test
     */
    public function filterOperationFiltersNumbersCorrectly()
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
     * @return array
     */
    public function dataProviderForChildrenAndFilterAndProperty()
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

        return [
            'children() on empty array always returns empty flowquery object' => [
                'sourceObjects' => [],
                'expressions' => [
                    '$query->children("foo[bar]")',
                    '$query->children("foo")',
                    '$query->children("[instanceof Something]")',
                    '$query->children()'
                ],
                'expectedResult' => []
            ],
            'children() with property name filter returns all corresponding child objects' => [
                'sourceObjects' => [$person1, $person2, $person3, $person4],
                'expressions' => [
                    '$query->children("address")',
                    '$query->children()->filter("address")',
                ],
                'expectedResult' => [$address1_1, $address2_1, $address3_1]
            ],

            'children() with property name and attribute filter returns all corresponding child objects' => [
                'sourceObjects' => [$person1, $person2, $person3, $person4],
                'expressions' => [
                    '$query->children("address[country=Germany]")',
                    '$query->children("address")->filter("[country=Germany]")',
                    '$query->children()->filter("address[country=Germany]")',
                ],
                'expectedResult' => [$address2_1, $address3_1]
            ],
            'property() with property name returns object accessor on first object' => [
                'sourceObjects' => [$person1, $person2, $person3, $person4],
                'expressions' => [
                    '$query->property("address")'
                ],
                'expectedResult' => $address1_1,
                'isFinal' => true
            ],
            'property() with property name works with property paths' => [
                'sourceObjects' => [$person1, $person2, $person3, $person4],
                'expressions' => [
                    '$query->property("address.street")'
                ],
                'expectedResult' => 'SomeCopenhagenStreet',
                'isFinal' => true
            ]
            // TODO: children without filter removes elements which do not have target property set
            // TODO: duplicate objects are removed
        ];
    }

    /**
     * @dataProvider dataProviderForChildrenAndFilterAndProperty
     * @test
     */
    public function childrenAndFilterAndPropertyWorks($sourceObjects, array $expressions, $expectedResult, $isFinal = false)
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
     * @return array
     */
    public function dataProviderForErrorQueries()
    {
        return [
            ['$query->children()'],
            ['$query->children("")'],
            ['$query->children("[foo]")'],
            ['$query->filter("foo")'],
            ['$query->children()->filter()'],
            ['$query->children()->filter("")'],
            ['$query->children("")->filter()'],
            ['$query->children("")->filter("")'],
            ['$query->children()->filter("[foo]")'],
            ['$query->children("foo")->filter("foo")'],
            ['$query->children("[foo]")->filter("foo")'], // TODO should we allow this, implicitely turning it around?
            ['$query->children("[foo]")->filter("[foo]")'],
            ['$query->children("foo")->filter("foo[foo]")'],
            ['$query->children("foo[foo]")->filter("foo[foo]")'],
        ];
    }

    /**
     * @dataProvider dataProviderForErrorQueries
     * @test
     */
    public function errorQueriesThrowError($expression)
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
    protected function createFlowQuery(array $elements)
    {
        $flowQuery = $this->getAccessibleMock(FlowQuery::class, ['dummy'], [$elements]);

        // Set up mock persistence manager to return dummy object identifiers
        $this->mockPersistenceManager = $this->createMock(PersistenceManagerInterface::class);
        $this->mockPersistenceManager->expects(self::any())->method('getIdentifierByObject')->will(self::returnCallBack(function ($object) {
            if (isset($object->__identity)) {
                return $object->__identity;
            }
        }));

        $mockPersistenceManager = $this->mockPersistenceManager;
        $objectManager = $this->createMock(ObjectManagerInterface::class);
        $objectManager->expects(self::any())->method('get')->will(self::returnCallBack(function ($className) use ($mockPersistenceManager) {
            $instance = new $className;
            // Special case to inject the mock persistence manager into the filter operation
            if ($className === Operations\Object\FilterOperation::class) {
                ObjectAccess::setProperty($instance, 'persistenceManager', $mockPersistenceManager, true);
            }
            return $instance;
        }));

        $operationResolver = $this->getAccessibleMock(OperationResolver::class, ['dummy']);
        $operationResolver->_set('objectManager', $objectManager);

        $operationResolver->_set('finalOperationNames', [
            'count' => 'count',
            'get' => 'get',
            'is' => 'is',
            'property' => 'property'
        ]);

        $operationResolver->_set('operations', [
            'count' => [300 => Operations\CountOperation::class],
            'first' => [300 => Operations\FirstOperation::class],
            'last' => [300 => Operations\LastOperation::class],
            'slice' => [300 => Operations\SliceOperation::class],
            'get' => [300 => Operations\GetOperation::class],
            'is' => [300 => Operations\IsOperation::class],
            'filter' => [300 => Operations\Object\FilterOperation::class],
            'children' => [300 => Operations\Object\ChildrenOperation::class],
            'property' => [300 => Operations\Object\PropertyOperation::class]
        ]);

        $flowQuery->_set('operationResolver', $operationResolver);
        return $flowQuery;
    }
}
