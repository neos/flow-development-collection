<?php
namespace TYPO3\Eel\Tests\Unit\FlowQuery;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.Eel".             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * FlowQuery test
 */
class FlowQueryTest extends \TYPO3\Flow\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\Flow\Persistence\PersistenceManagerInterface
	 */
	protected $mockPersistenceManager;

	/**
	 * @test
	 */
	public function constructWithFlowQueryIsIdempotent() {
		$flowQuery = new \TYPO3\Eel\FlowQuery\FlowQuery(array('a', 'b', 'c'));
		$wrappedQuery = new \TYPO3\Eel\FlowQuery\FlowQuery($flowQuery);

		$this->assertEquals($flowQuery->getContext(), $wrappedQuery->getContext());
	}

	/**
	 * @test
	 */
	public function firstReturnsFirstObject() {
		$myObject = new \stdClass();
		$myObject2 = new \stdClass();

		$query = $this->createFlowQuery(array($myObject, $myObject2));
		$this->assertInstanceOf('TYPO3\Eel\FlowQuery\FlowQuery', $query->first());
		$this->assertSame(array($myObject), $query->first()->get());
		$this->assertSame(array($myObject), iterator_to_array($query->first()));
	}

	/**
	 * @test
	 */
	public function lastReturnsLastObject() {
		$myObject = new \stdClass();
		$myObject2 = new \stdClass();

		$query = $this->createFlowQuery(array($myObject, $myObject2));
		$this->assertInstanceOf('TYPO3\Eel\FlowQuery\FlowQuery', $query->last());
		$this->assertSame(array($myObject2), $query->last()->get());
		$this->assertSame(array($myObject2), iterator_to_array($query->last()));
	}

	/**
	 * @test
	 */
	public function sliceReturnsSlicedObject() {
		$myObject = new \stdClass();
		$myObject2 = new \stdClass();
		$myObject3 = new \stdClass();

		$query = $this->createFlowQuery(array($myObject, $myObject2, $myObject3));
		$this->assertInstanceOf('TYPO3\Eel\FlowQuery\FlowQuery', $query->slice());
		$this->assertSame(array($myObject, $myObject2, $myObject3), $query->slice()->get());
		$this->assertSame(array($myObject, $myObject2, $myObject3), iterator_to_array($query->slice()));
		$this->assertSame(array($myObject, $myObject2), $query->slice(0,2)->get());
		$this->assertSame(array($myObject, $myObject2), iterator_to_array($query->slice(0,2)));
		$this->assertSame(array($myObject3), $query->slice(2)->get());
		$this->assertSame(array($myObject3), iterator_to_array($query->slice(2)));
	}

	/**
	 * @return array
	 */
	public function dataProviderForFilter() {
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
		$myObject5->isHidden = TRUE;

		$myObject6 = new \stdClass();
		$myObject6->aNumber = 42;

		return array(
			'Property existance test works' => array(
				'sourceObjects' => array($myObject, $myObject2),
				'filter' => '[myProperty]',
				'expectedResult' => array($myObject)
			),
			'Multiple filters are combined with AND together' => array(
				'sourceObjects' => array($myObject, $myObject2, $myObject3),
				'filter' => '[myProperty][myProperty2]',
				'expectedResult' => array($myObject)
			),
			'Multiple filters can be ORed together using comma' => array(
				'sourceObjects' => array($myObject, $myObject2, $myObject3, $myObject4),
				'filter' => '[myProperty2], [name]',
				'expectedResult' => array($myObject, $myObject4)
			),
			'Exact matches are supported' => array(
				'sourceObjects' => array($myObject, $myObject2, $myObject3, $myObject4),
				'filter' => '[myProperty=asdf]',
				'expectedResult' => array($myObject)
			),
			'Boolean matches' => array(
				'sourceObjects' => array($myObject, $myObject2, $myObject3, $myObject4, $myObject5, $myObject6),
				'filter' => '[isHidden=true]',
				'expectedResult' => array($myObject5)
			),
			'Integer matches' => array(
				'sourceObjects' => array($myObject, $myObject2, $myObject3, $myObject4, $myObject5, $myObject6),
				'filter' => '[aNumber = 42]',
				'expectedResult' => array($myObject6)
			),

			'Instanceof test works (1)' => array(
				'sourceObjects' => array($myObject),
				'filter' => '[instanceof foo]',
				'expectedResult' => array()
			),
			'Instanceof test works (2)' => array(
				'sourceObjects' => array($myObject),
				'filter' => '[  instanceof \stdClass  ]',
				'expectedResult' => array($myObject)
			),
			'Instanceof test works (with test for object)' => array(
				'sourceObjects' => array($myObject),
				'filter' => '[  instanceof object  ]',
				'expectedResult' => array($myObject)
			),
			'Instanceof test works (with test for string)' => array(
				'sourceObjects' => array('myString'),
				'filter' => '[  instanceof string  ]',
				'expectedResult' => array('myString')
			),

			'Instanceof test works (with test for integer)' => array(
				'sourceObjects' => array(42, '42', 400, 'foo'),
				'filter' => '[  instanceof integer  ]',
				'expectedResult' => array(42, 400)
			),

			'Instanceof test works (with test for integer 2)' => array(
				'sourceObjects' => array(42, '42', 400, 'foo'),
				'filter' => '[  instanceof int  ]',
				'expectedResult' => array(42, 400)
			),

			'Instanceof test works (with test for boolean)' => array(
				'sourceObjects' => array(FALSE, '', TRUE),
				'filter' => '[  instanceof boolean  ]',
				'expectedResult' => array(FALSE, TRUE)
			),

			'Instanceof test works (with test for float)' => array(
				'sourceObjects' => array(FALSE, 42, 42.5, TRUE),
				'filter' => '[  instanceof float  ]',
				'expectedResult' => array(42.5)
			),

			'Instanceof test works (with test for array)' => array(
				'sourceObjects' => array(FALSE, 42, 42.5, TRUE, array('foo')),
				'filter' => '[  instanceof array  ]',
				'expectedResult' => array(array('foo'))
			),

			'Instanceof test works on attributes' => array(
				'sourceObjects' => array($myObject, $myObject2, $myObject3, $myObject4, $myObject5, $myObject6),
				'filter' => '[ isHidden instanceof boolean ]',
				'expectedResult' => array($myObject5)
			),

			'Begin query match' => array(
				'sourceObjects' => array($myObject, $myObject2, $myObject3, $myObject4),
				'filter' => '[ myProperty ^= as ]',
				'expectedResult' => array($myObject)
			),

			'End query match (1)' => array(
				'sourceObjects' => array($myObject, $myObject2, $myObject3, $myObject4),
				'filter' => '[ myProperty $= df ]',
				'expectedResult' => array($myObject)
			),
			'End query match (2)' => array(
				'sourceObjects' => array($myObject, $myObject2, $myObject3, $myObject4),
				'filter' => '[ myProperty $= a ]',
				'expectedResult' => array($myObject3)
			),

			'In-Between Query Match' => array(
				'sourceObjects' => array($myObject, $myObject2, $myObject3, $myObject4),
				'filter' => '[ myProperty *= sd ]',
				'expectedResult' => array($myObject)
			),

			'Identifier match' => array(
				'sourceObjects' => array($myObject, $myObject2, $myObject3, $myObject4),
				'filter' => '#object-identifier-A1-B2',
				'expectedResult' => array($myObject2)
			),

			'Not equals query match' => array(
				'sourceObjects' => array($myObject, $myObject2, $myObject3, $myObject4),
				'filter' => '[ myProperty != asdf ]',
				'expectedResult' => array($myObject2, $myObject3, $myObject4)
			)
		);
	}

	/**
	 * @dataProvider dataProviderForFilter
	 * @test
	 */
	public function filterCanFilterObjects($sourceObjects, $filterString, $expected) {
		$query = $this->createFlowQuery($sourceObjects);
		$filter = $query->filter($filterString);
		$this->assertInstanceOf('TYPO3\Eel\FlowQuery\FlowQuery', $filter);
		$this->assertSame($expected, iterator_to_array($filter));
	}

	/**
	 * @dataProvider dataProviderForFilter
	 * @test
	 */
	public function isCanFilterObjects($sourceObjects, $filterString, $expectedResultArray) {
		$query = $this->createFlowQuery($sourceObjects);
		$this->assertSame(count($expectedResultArray) > 0, $query->is($filterString));
	}

	/**
	 * @dataProvider dataProviderForFilter
	 * @test
	 */
	public function countReturnsCorrectNumber($sourceObjects, $filterString, $expectedResultArray) {
		$query = $this->createFlowQuery($sourceObjects);
		$this->assertSame(count($expectedResultArray), $query->filter($filterString)->count());
		$this->assertSame(count($sourceObjects), $query->count());
		$this->assertSame(count($sourceObjects), count($query));
	}

	/**
	 * @return array
	 */
	public function dataProviderForChildrenAndFilterAndProperty() {
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

		return array(
			'children() on empty array always returns empty flowquery object' => array(
				'sourceObjects' => array(),
				'expressions' => array(
					'$query->children("foo[bar]")',
					'$query->children("foo")',
					'$query->children("[instanceof Something]")',
					'$query->children()'
				),
				'expectedResult' => array()
			),
			'children() with property name filter returns all corresponding child objects' => array(
				'sourceObjects' => array($person1, $person2, $person3, $person4),
				'expressions' => array(
					'$query->children("address")',
					'$query->children()->filter("address")',
				),
				'expectedResult' => array($address1_1, $address2_1, $address3_1)
			),

			'children() with property name and attribute filter returns all corresponding child objects' => array(
				'sourceObjects' => array($person1, $person2, $person3, $person4),
				'expressions' => array(
					'$query->children("address[country=Germany]")',
					'$query->children("address")->filter("[country=Germany]")',
					'$query->children()->filter("address[country=Germany]")',
				),
				'expectedResult' => array($address2_1, $address3_1)
			),
			'property() with property name returns object accessor on first object' => array(
				'sourceObjects' => array($person1, $person2, $person3, $person4),
				'expressions' => array(
					'$query->property("address")'
				),
				'expectedResult' => $address1_1,
				'isFinal' => TRUE
			),
			'property() with property name works with property paths' => array(
				'sourceObjects' => array($person1, $person2, $person3, $person4),
				'expressions' => array(
					'$query->property("address.street")'
				),
				'expectedResult' => 'SomeCopenhagenStreet',
				'isFinal' => TRUE
			)
			// TODO: children without filter removes elements which do not have target property set
			// TODO: duplicate objects are removed
		);
	}

	/**
	 * @dataProvider dataProviderForChildrenAndFilterAndProperty
	 * @test
	 */
	public function childrenAndFilterAndPropertyWorks($sourceObjects, array $expressions, $expectedResult, $isFinal = FALSE) {
		$query = $this->createFlowQuery($sourceObjects);
		foreach ($expressions as $expression) {
			eval('$result = ' . $expression . ';');
			if (!$isFinal) {
				$this->assertInstanceOf('TYPO3\Eel\FlowQuery\FlowQuery', $result);
				$result = iterator_to_array($result);
			}
			$this->assertSame($expectedResult, $result, 'Expression "' . $expression . '" did not match expected result');
		}
	}

	/**
	 * @return array
	 */
	public function dataProviderForErrorQueries() {
		return array(
			array('$query->children()'),
			array('$query->children("")'),

			array('$query->children("[foo]")'),
			array('$query->filter("foo")'),
			array('$query->children()->filter()'),
			array('$query->children()->filter("")'),
			array('$query->children("")->filter()'),
			array('$query->children("")->filter("")'),
			array('$query->children()->filter("[foo]")'),
			array('$query->children("foo")->filter("foo")'),
			array('$query->children("[foo]")->filter("foo")'), // TODO should we allow this, implicitely turning it around?
			array('$query->children("[foo]")->filter("[foo]")'),
			array('$query->children("foo")->filter("foo[foo]")'),
			array('$query->children("foo[foo]")->filter("foo[foo]")'),
		);
	}

	/**
	 * @dataProvider dataProviderForErrorQueries
	 * @test
	 * @expectedException \TYPO3\Eel\FlowQuery\FizzleException
	 */
	public function errorQueriesThrowError($expression) {
		$x = new \stdClass();
		$x->foo = new \stdClass();
		$x->foo->foo = 'asdf';
		$query = $this->createFlowQuery(array($x));
		eval('$result = ' . $expression . ';');
		$this->assertInstanceOf('TYPO3\Eel\FlowQuery\FlowQuery', $result);
		$result->getIterator(); // Throws exception
	}

	/**
	 * @param array $elements
	 * @return \TYPO3\Eel\FlowQuery\FlowQuery
	 */
	protected function createFlowQuery(array $elements) {
		$flowQuery = $this->getAccessibleMock('TYPO3\Eel\FlowQuery\FlowQuery', array('dummy'), array($elements));

			// Set up mock persistence manager to return dummy object identifiers
		$this->mockPersistenceManager = $this->getMock('TYPO3\Flow\Persistence\PersistenceManagerInterface');
		$this->mockPersistenceManager->expects($this->any())->method('getIdentifierByObject')->will($this->returnCallback(function($object) {
			if (isset($object->__identity)) {
				return $object->__identity;
			}
		}));

		$mockPersistenceManager = $this->mockPersistenceManager;
		$objectManager = $this->getMock('TYPO3\Flow\Object\ObjectManagerInterface');
		$objectManager->expects($this->any())->method('get')->will($this->returnCallback(function($className) use($mockPersistenceManager) {
			$instance = new $className;
			// Special case to inject the mock persistence manager into the filter operation
			if ($className === 'TYPO3\Eel\FlowQuery\Operations\Object\FilterOperation') {
				\TYPO3\Flow\Reflection\ObjectAccess::setProperty($instance, 'persistenceManager', $mockPersistenceManager, TRUE);
			}
			return $instance;
		}));

		$operationResolver = $this->getAccessibleMock('TYPO3\Eel\FlowQuery\OperationResolver', array('dummy'));
		$operationResolver->_set('objectManager', $objectManager);

		$operationResolver->_set('finalOperationNames', array(
			'count' => 'count',
			'get' => 'get',
			'is' => 'is',
			'property' => 'property'
		));

		$operationResolver->_set('operations', array(
			'count' => array(300 => 'TYPO3\Eel\FlowQuery\Operations\CountOperation'),
			'first' => array(300 => 'TYPO3\Eel\FlowQuery\Operations\FirstOperation'),
			'last' => array(300 => 'TYPO3\Eel\FlowQuery\Operations\LastOperation'),
			'slice' => array(300 => 'TYPO3\Eel\FlowQuery\Operations\SliceOperation'),
			'get' => array(300 => 'TYPO3\Eel\FlowQuery\Operations\GetOperation'),
			'is' => array(300 => 'TYPO3\Eel\FlowQuery\Operations\IsOperation'),
			'filter' => array(300 => 'TYPO3\Eel\FlowQuery\Operations\Object\FilterOperation'),
			'children' => array(300 => 'TYPO3\Eel\FlowQuery\Operations\Object\ChildrenOperation'),
			'property' => array(300 => 'TYPO3\Eel\FlowQuery\Operations\Object\PropertyOperation')
		));

		$flowQuery->_set('operationResolver', $operationResolver);
		return $flowQuery;
	}

}
