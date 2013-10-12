<?php
namespace TYPO3\Flow\Tests\Unit\Mvc\Routing;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */
use TYPO3\Flow\Mvc\Routing\IdentityRoutePart;
use TYPO3\Flow\Mvc\Routing\ObjectPathMapping;
use TYPO3\Flow\Mvc\Routing\ObjectPathMappingRepository;
use TYPO3\Flow\Persistence\PersistenceManagerInterface;
use TYPO3\Flow\Reflection\ClassSchema;
use TYPO3\Flow\Reflection\ReflectionService;
use TYPO3\Flow\Tests\UnitTestCase;

/**
 * Testcase for the MVC Web Routing IdentityRoutePart Class
 */
class IdentityRoutePartTest extends UnitTestCase {

	/**
	 * @var IdentityRoutePart
	 */
	protected $identityRoutePart;

	/**
	 * @var PersistenceManagerInterface
	 */
	protected $mockPersistenceManager;

	/**
	 * @var ReflectionService
	 */
	protected $mockReflectionService;

	/**
	 * @var ClassSchema
	 */
	protected $mockClassSchema;

	/**
	 * @var ObjectPathMappingRepository
	 */
	protected $mockObjectPathMappingRepository;

	/**
	 * Sets up this test case
	 */
	public function setUp() {
		$this->identityRoutePart = $this->getAccessibleMock('TYPO3\Flow\Mvc\Routing\IdentityRoutePart', array('createPathSegmentForObject'));

		$this->mockPersistenceManager = $this->getMock('TYPO3\Flow\Persistence\PersistenceManagerInterface');
		$this->identityRoutePart->_set('persistenceManager', $this->mockPersistenceManager);

		$this->mockReflectionService = $this->getMock('TYPO3\Flow\Reflection\ReflectionService');
		$this->mockClassSchema = $this->getMock('TYPO3\Flow\Reflection\ClassSchema', array(), array(), '', FALSE);
		$this->mockReflectionService->expects($this->any())->method('getClassSchema')->will($this->returnValue($this->mockClassSchema));
		$this->identityRoutePart->_set('reflectionService', $this->mockReflectionService);

		$this->mockObjectPathMappingRepository = $this->getMock('TYPO3\Flow\Mvc\Routing\ObjectPathMappingRepository');
		$this->identityRoutePart->_set('objectPathMappingRepository', $this->mockObjectPathMappingRepository);
	}

	/**
	 * @test
	 */
	public function getUriPatternReturnsTheSpecifiedUriPatternIfItsNotEmpty() {
		$this->identityRoutePart->setUriPattern('SomeUriPattern');
		$this->assertSame('SomeUriPattern', $this->identityRoutePart->getUriPattern());
	}

	/**
	 * @test
	 */
	public function getUriPatternReturnsAnEmptyStringIfObjectTypeHasNotIdentityPropertiesAndNoPatternWasSpecified() {
		$this->mockClassSchema->expects($this->once())->method('getIdentityProperties')->will($this->returnValue(array()));

		$this->identityRoutePart->setObjectType('SomeObjectType');
		$this->assertSame('', $this->identityRoutePart->getUriPattern());
	}

	/**
	 * @test
	 */
	public function getUriPatternReturnsBasedOnTheIdentityPropertiesOfTheObjectTypeIfNoPatternWasSpecified() {
		$this->mockClassSchema->expects($this->once())->method('getIdentityProperties')->will($this->returnValue(array('property1' => 'string', 'property2' => 'integer', 'property3' => 'DateTime')));
		$this->identityRoutePart->setObjectType('SomeObjectType');
		$this->assertSame('{property1}/{property2}/{property3}', $this->identityRoutePart->getUriPattern());
	}

	/**
	 * @test
	 */
	public function matchValueReturnsFalseIfTheGivenValueIsEmptyOrNull() {
		$this->assertFalse($this->identityRoutePart->_call('matchValue', ''));
		$this->assertFalse($this->identityRoutePart->_call('matchValue', NULL));
	}

	/**
	 * @test
	 */
	public function matchValueReturnsFalseIfNoObjectPathMappingCouldBeFound() {
		$this->mockObjectPathMappingRepository->expects($this->once())->method('findOneByObjectTypeUriPatternAndPathSegment')->with('SomeObjectType', 'SomeUriPattern', 'TheRoutePath', FALSE)->will($this->returnValue(NULL));
		$this->identityRoutePart->setObjectType('SomeObjectType');
		$this->identityRoutePart->setUriPattern('SomeUriPattern');
		$this->assertFalse($this->identityRoutePart->_call('matchValue', 'TheRoutePath'));
	}

	/**
	 * @test
	 */
	public function matchValueSetsTheIdentifierOfTheObjectPathMappingAndReturnsTrueIfAMatchingObjectPathMappingWasFound() {
		$mockObjectPathMapping = $this->getMock('TYPO3\Flow\Mvc\Routing\ObjectPathMapping');
		$mockObjectPathMapping->expects($this->once())->method('getIdentifier')->will($this->returnValue('TheIdentifier'));
		$this->mockObjectPathMappingRepository->expects($this->once())->method('findOneByObjectTypeUriPatternAndPathSegment')->with('SomeObjectType', 'SomeUriPattern', 'TheRoutePath', FALSE)->will($this->returnValue($mockObjectPathMapping));
		$this->identityRoutePart->setObjectType('SomeObjectType');
		$this->identityRoutePart->setUriPattern('SomeUriPattern');

		$this->assertTrue($this->identityRoutePart->_call('matchValue', 'TheRoutePath'));
		$expectedResult = array('__identity' => 'TheIdentifier');
		$actualResult = $this->identityRoutePart->getValue();
		$this->assertSame($expectedResult, $actualResult);
	}

	/**
	 * @test
	 */
	public function matchValueSetsTheRouteValueToTheUrlDecodedPathSegmentIfNoUriPatternIsSpecified() {
		$this->mockPersistenceManager->expects($this->once())->method('getObjectByIdentifier')->with('The Identifier', 'stdClass')->will($this->returnValue(new \stdClass()));

		$this->mockObjectPathMappingRepository->expects($this->never())->method('findOneByObjectTypeUriPatternAndPathSegment');

		$this->identityRoutePart->setObjectType('stdClass');

		$this->assertTrue($this->identityRoutePart->_call('matchValue', 'The+Identifier'));
		$expectedResult = array('__identity' => 'The Identifier');
		$actualResult = $this->identityRoutePart->getValue();
		$this->assertSame($expectedResult, $actualResult);
	}

	/**
	 * @test
	 */
	public function matchValueSetsCaseSensitiveFlagIfLowerCaseIsFalse() {
		$this->mockObjectPathMappingRepository->expects($this->once())->method('findOneByObjectTypeUriPatternAndPathSegment')->with('SomeObjectType', 'SomeUriPattern', 'TheRoutePath', TRUE);
		$this->identityRoutePart->setObjectType('SomeObjectType');
		$this->identityRoutePart->setUriPattern('SomeUriPattern');
		$this->identityRoutePart->setLowerCase(FALSE);

		$this->identityRoutePart->_call('matchValue', 'TheRoutePath');
	}

	/**
	 * @test
	 */
	public function findValueToMatchReturnsAnEmptyStringIfTheRoutePathIsEmpty() {
		$this->assertSame('', $this->identityRoutePart->_call('findValueToMatch', NULL));
		$this->assertSame('', $this->identityRoutePart->_call('findValueToMatch', ''));
		$this->assertSame('', $this->identityRoutePart->_call('findValueToMatch', '/'));
	}

	/**
	 * @test
	 */
	public function findValueToMatchReturnsAnEmptyStringIfTheSpecifiedSplitStringCantBeFoundInTheRoutePath() {
		$this->identityRoutePart->setUriPattern('');
		$this->identityRoutePart->setSplitString('SplitStringThatIsNotInTheCurrentRoutePath');
		$this->assertSame('', $this->identityRoutePart->_call('findValueToMatch', 'The/Complete/RoutPath'));
	}

	/**
	 * @test
	 */
	public function findValueToMatchReturnsAnEmptyStringIfTheCalculatedUriPatternIsEmpty() {
		$this->identityRoutePart->setUriPattern('');
		$this->identityRoutePart->setSplitString('TheSplitString');
		$this->assertSame('', $this->identityRoutePart->_call('findValueToMatch', 'First/Part/Of/The/Complete/RoutPath/TheSplitString/SomeThingElse'));
	}

	/**
	 * data provider for findValueToMatchTests()
	 * @return array
	 */
	public function findValueToMatchProvider() {
		return array(
			array('staticPattern/Foo', 'staticPattern', '/Foo', 'staticPattern'),
			array('staticPattern/Foo', 'staticPattern', 'NonExistingSplitString', ''),
			array('The/Route/Path', '{property1}/{property2}', '/Path', 'The/Route'),
			array('static/dynamic/splitString', 'static/{property1}', '/splitString', 'static/dynamic'),
			array('dynamic/exceeding/splitString', '{property1}', '/splitString', ''),
			array('dynamic1static1dynamic2/static2splitString', '{property1}static1{property2}/static2', 'splitString', 'dynamic1static1dynamic2/static2'),
			array('static1dynamic1dynamic2/static2splitString', 'static1{property1}{property2}/static2', 'splitString', 'static1dynamic1dynamic2/static2'),
			array('foo/bar/baz', '{foo}/{bar}', '/', 'foo/bar'),
			array('foo/bar/baz', '{foo}/{bar}', '/baz', 'foo/bar'),
			array('foo/bar/notTheSplitString', '{foo}/{bar}', '/splitString', ''),
		);
	}

	/**
	 * @test
	 * @dataProvider findValueToMatchProvider
	 * @param string $routePath
	 * @param string $uriPattern
	 * @param string $splitString
	 * @param string $expectedResult
	 * @return void
	 */
	public function findValueToMatchTests($routePath, $uriPattern, $splitString, $expectedResult) {
		$this->identityRoutePart->setUriPattern($uriPattern);
		$this->identityRoutePart->setSplitString($splitString);
		$this->assertSame($expectedResult, $this->identityRoutePart->_call('findValueToMatch', $routePath));
	}

	/**
	 * @test
	 */
	public function resolveValueAcceptsIdentityArrays() {
		$value = array('__identity' => 'SomeIdentifier');
		$mockObjectPathMapping = $this->getMock('TYPO3\Flow\Mvc\Routing\ObjectPathMapping');
		$mockObjectPathMapping->expects($this->once())->method('getPathSegment')->will($this->returnValue('ThePathSegment'));
		$this->mockPersistenceManager->expects($this->never())->method('getIdentifierByObject');
		$this->mockObjectPathMappingRepository->expects($this->once())->method('findOneByObjectTypeUriPatternAndIdentifier')->with('stdClass', 'SomeUriPattern', 'SomeIdentifier')->will($this->returnValue($mockObjectPathMapping));

		$this->identityRoutePart->setObjectType('stdClass');
		$this->identityRoutePart->setUriPattern('SomeUriPattern');
		$this->assertTrue($this->identityRoutePart->_call('resolveValue', $value));
		$this->assertSame('thepathsegment', $this->identityRoutePart->getValue());
	}

	/**
	 * @test
	 */
	public function resolveValueSetsTheRouteValueToTheUrlEncodedIdentifierIfNoUriPatternIsSpecified() {
		$value = array('__identity' => 'Some Identifier');
		$this->mockObjectPathMappingRepository->expects($this->never())->method('findOneByObjectTypeUriPatternAndIdentifier');

		$this->identityRoutePart->setObjectType('stdClass');

		$this->identityRoutePart->_call('resolveValue', $value);
		$this->assertSame('Some+Identifier', $this->identityRoutePart->getValue());
	}

	/**
	 * @test
	 */
	public function resolveValueConvertsCaseOfResolvedPathSegmentIfLowerCaseIsTrue() {
		$value = array('__identity' => 'SomeIdentifier');
		$mockObjectPathMapping = $this->getMock('TYPO3\Flow\Mvc\Routing\ObjectPathMapping');
		$mockObjectPathMapping->expects($this->once())->method('getPathSegment')->will($this->returnValue('ThePathSegment'));
		$this->mockObjectPathMappingRepository->expects($this->once())->method('findOneByObjectTypeUriPatternAndIdentifier')->with('stdClass', 'SomeUriPattern', 'SomeIdentifier')->will($this->returnValue($mockObjectPathMapping));

		$this->identityRoutePart->setObjectType('stdClass');
		$this->identityRoutePart->setUriPattern('SomeUriPattern');
		$this->identityRoutePart->setLowerCase(TRUE);

		$this->identityRoutePart->_call('resolveValue', $value);
		$this->assertSame('thepathsegment', $this->identityRoutePart->getValue());
	}

	/**
	 * @test
	 */
	public function resolveValueKeepsCaseOfResolvedPathSegmentIfLowerCaseIsTrue() {
		$value = array('__identity' => 'SomeIdentifier');
		$mockObjectPathMapping = $this->getMock('TYPO3\Flow\Mvc\Routing\ObjectPathMapping');
		$mockObjectPathMapping->expects($this->once())->method('getPathSegment')->will($this->returnValue('ThePathSegment'));
		$this->mockObjectPathMappingRepository->expects($this->once())->method('findOneByObjectTypeUriPatternAndIdentifier')->with('stdClass', 'SomeUriPattern', 'SomeIdentifier')->will($this->returnValue($mockObjectPathMapping));

		$this->identityRoutePart->setObjectType('stdClass');
		$this->identityRoutePart->setUriPattern('SomeUriPattern');
		$this->identityRoutePart->setLowerCase(FALSE);

		$this->identityRoutePart->_call('resolveValue', $value);
		$this->assertSame('ThePathSegment', $this->identityRoutePart->getValue());
	}

	/**
	 * @test
	 */
	public function resolveValueReturnsFalseIfTheGivenValueIsNotOfTheSpecifiedType() {
		$this->identityRoutePart->setObjectType('SomeObjectType');
		$this->assertFalse($this->identityRoutePart->_call('resolveValue', new \stdClass()));
	}

	/**
	 * @test
	 */
	public function resolveValueSetsTheValueToThePathSegmentOfTheObjectPathMappingAndReturnsTrueIfAMatchingObjectPathMappingWasFound() {
		$object = new \stdClass();
		$mockObjectPathMapping = $this->getMock('TYPO3\Flow\Mvc\Routing\ObjectPathMapping');
		$mockObjectPathMapping->expects($this->once())->method('getPathSegment')->will($this->returnValue('ThePathSegment'));
		$this->mockPersistenceManager->expects($this->once())->method('getIdentifierByObject')->with($object)->will($this->returnValue('TheIdentifier'));
		$this->mockObjectPathMappingRepository->expects($this->once())->method('findOneByObjectTypeUriPatternAndIdentifier')->with('stdClass', 'SomeUriPattern', 'TheIdentifier')->will($this->returnValue($mockObjectPathMapping));

		$this->identityRoutePart->setObjectType('stdClass');
		$this->identityRoutePart->setUriPattern('SomeUriPattern');
		$this->assertTrue($this->identityRoutePart->_call('resolveValue', $object));
		$this->assertSame('thepathsegment', $this->identityRoutePart->getValue());
	}

	/**
	 * @test
	 */
	public function resolveValueCreatesAndStoresANewObjectPathMappingIfNoMatchingObjectPathMappingWasFound() {
		$object = new \stdClass();
		$this->mockPersistenceManager->expects($this->atLeastOnce())->method('getIdentifierByObject')->with($object)->will($this->returnValue('TheIdentifier'));
		$this->mockPersistenceManager->expects($this->atLeastOnce())->method('getObjectByIdentifier')->with('TheIdentifier')->will($this->returnValue($object));
		$this->mockObjectPathMappingRepository->expects($this->once())->method('findOneByObjectTypeUriPatternAndIdentifier')->with('stdClass', 'SomeUriPattern', 'TheIdentifier')->will($this->returnValue(NULL));

		$this->identityRoutePart->expects($this->once())->method('createPathSegmentForObject')->with($object)->will($this->returnValue('The/Path/Segment'));
		$this->mockObjectPathMappingRepository->expects($this->once())->method('findOneByObjectTypeUriPatternAndPathSegment')->with('stdClass', 'SomeUriPattern', 'The/Path/Segment', FALSE)->will($this->returnValue(NULL));

		$expectedObjectPathMapping = new ObjectPathMapping();
		$expectedObjectPathMapping->setObjectType('stdClass');
		$expectedObjectPathMapping->setUriPattern('SomeUriPattern');
		$expectedObjectPathMapping->setPathSegment('The/Path/Segment');
		$expectedObjectPathMapping->setIdentifier('TheIdentifier');
		$this->mockObjectPathMappingRepository->expects($this->once())->method('add')->with($expectedObjectPathMapping);
		$this->mockPersistenceManager->expects($this->once())->method('persistAll');

		$this->identityRoutePart->setObjectType('stdClass');
		$this->identityRoutePart->setUriPattern('SomeUriPattern');
		$this->assertTrue($this->identityRoutePart->_call('resolveValue', $object));
		$this->assertSame('the/path/segment', $this->identityRoutePart->getValue());
	}

	/**
	 * @test
	 */
	public function resolveValueAppendsCounterIfNoMatchingObjectPathMappingWasFoundAndCreatedPathSegmentIsNotUnique() {
		$object = new \stdClass();
		$this->mockPersistenceManager->expects($this->atLeastOnce())->method('getIdentifierByObject')->with($object)->will($this->returnValue('TheIdentifier'));
		$this->mockPersistenceManager->expects($this->atLeastOnce())->method('getObjectByIdentifier')->with('TheIdentifier')->will($this->returnValue($object));
		$this->mockObjectPathMappingRepository->expects($this->once())->method('findOneByObjectTypeUriPatternAndIdentifier')->with('stdClass', 'SomeUriPattern', 'TheIdentifier')->will($this->returnValue(NULL));

		$existingObjectPathMapping = new ObjectPathMapping();
		$existingObjectPathMapping->setObjectType('stdClass');
		$existingObjectPathMapping->setUriPattern('SomeUriPattern');
		$existingObjectPathMapping->setPathSegment('The/Path/Segment');
		$existingObjectPathMapping->setIdentifier('AnotherIdentifier');

		$this->identityRoutePart->expects($this->once())->method('createPathSegmentForObject')->with($object)->will($this->returnValue('The/Path/Segment'));
		$this->mockObjectPathMappingRepository->expects($this->at(1))->method('findOneByObjectTypeUriPatternAndPathSegment')->with('stdClass', 'SomeUriPattern', 'The/Path/Segment', FALSE)->will($this->returnValue($existingObjectPathMapping));
		$this->mockObjectPathMappingRepository->expects($this->at(2))->method('findOneByObjectTypeUriPatternAndPathSegment')->with('stdClass', 'SomeUriPattern', 'The/Path/Segment-1', FALSE)->will($this->returnValue($existingObjectPathMapping));
		$this->mockObjectPathMappingRepository->expects($this->at(3))->method('findOneByObjectTypeUriPatternAndPathSegment')->with('stdClass', 'SomeUriPattern', 'The/Path/Segment-2', FALSE)->will($this->returnValue(NULL));

		$expectedObjectPathMapping = new ObjectPathMapping();
		$expectedObjectPathMapping->setObjectType('stdClass');
		$expectedObjectPathMapping->setUriPattern('SomeUriPattern');
		$expectedObjectPathMapping->setPathSegment('The/Path/Segment-2');
		$expectedObjectPathMapping->setIdentifier('TheIdentifier');
		$this->mockObjectPathMappingRepository->expects($this->once())->method('add')->with($expectedObjectPathMapping);
		$this->mockPersistenceManager->expects($this->once())->method('persistAll');

		$this->identityRoutePart->setObjectType('stdClass');
		$this->identityRoutePart->setUriPattern('SomeUriPattern');
		$this->assertTrue($this->identityRoutePart->_call('resolveValue', $object));
		$this->assertSame('the/path/segment-2', $this->identityRoutePart->getValue());
	}

	/**
	 * @test
	 */
	public function resolveValueSetsCaseSensitiveFlagIfLowerCaseIsFalse() {
		$object = new \stdClass();
		$this->mockPersistenceManager->expects($this->atLeastOnce())->method('getIdentifierByObject')->with($object)->will($this->returnValue('TheIdentifier'));
		$this->mockPersistenceManager->expects($this->atLeastOnce())->method('getObjectByIdentifier')->with('TheIdentifier')->will($this->returnValue($object));
		$this->mockObjectPathMappingRepository->expects($this->once())->method('findOneByObjectTypeUriPatternAndIdentifier')->with('stdClass', 'SomeUriPattern', 'TheIdentifier')->will($this->returnValue(NULL));

		$existingObjectPathMapping = new ObjectPathMapping();
		$existingObjectPathMapping->setObjectType('stdClass');
		$existingObjectPathMapping->setUriPattern('SomeUriPattern');
		$existingObjectPathMapping->setPathSegment('The/Path/Segment');
		$existingObjectPathMapping->setIdentifier('AnotherIdentifier');

		$this->identityRoutePart->expects($this->once())->method('createPathSegmentForObject')->with($object)->will($this->returnValue('The/Path/Segment'));
		$this->mockObjectPathMappingRepository->expects($this->at(1))->method('findOneByObjectTypeUriPatternAndPathSegment')->with('stdClass', 'SomeUriPattern', 'The/Path/Segment', TRUE)->will($this->returnValue($existingObjectPathMapping));
		$this->mockObjectPathMappingRepository->expects($this->at(2))->method('findOneByObjectTypeUriPatternAndPathSegment')->with('stdClass', 'SomeUriPattern', 'The/Path/Segment-1', TRUE)->will($this->returnValue(NULL));

		$expectedObjectPathMapping = new ObjectPathMapping();
		$expectedObjectPathMapping->setObjectType('stdClass');
		$expectedObjectPathMapping->setUriPattern('SomeUriPattern');
		$expectedObjectPathMapping->setPathSegment('The/Path/Segment-1');
		$expectedObjectPathMapping->setIdentifier('TheIdentifier');
		$this->mockObjectPathMappingRepository->expects($this->once())->method('add')->with($expectedObjectPathMapping);
		$this->mockPersistenceManager->expects($this->once())->method('persistAll');

		$this->identityRoutePart->setObjectType('stdClass');
		$this->identityRoutePart->setUriPattern('SomeUriPattern');
		$this->identityRoutePart->setLowerCase(FALSE);
		$this->assertTrue($this->identityRoutePart->_call('resolveValue', $object));
		$this->assertSame('The/Path/Segment-1', $this->identityRoutePart->getValue());
	}

	/**
	 * @test
	 */
	public function resolveValueAppendsCounterIfCreatedPathSegmentIsEmpty() {
		$object = new \stdClass();
		$this->mockPersistenceManager->expects($this->atLeastOnce())->method('getIdentifierByObject')->with($object)->will($this->returnValue('TheIdentifier'));
		$this->mockPersistenceManager->expects($this->atLeastOnce())->method('getObjectByIdentifier')->with('TheIdentifier')->will($this->returnValue($object));
		$this->mockObjectPathMappingRepository->expects($this->once())->method('findOneByObjectTypeUriPatternAndIdentifier')->with('stdClass', 'SomeUriPattern', 'TheIdentifier')->will($this->returnValue(NULL));

		$this->identityRoutePart->expects($this->once())->method('createPathSegmentForObject')->with($object)->will($this->returnValue(''));
		$this->mockObjectPathMappingRepository->expects($this->once())->method('findOneByObjectTypeUriPatternAndPathSegment')->with('stdClass', 'SomeUriPattern', '-1', FALSE)->will($this->returnValue(NULL));

		$expectedObjectPathMapping = new ObjectPathMapping();
		$expectedObjectPathMapping->setObjectType('stdClass');
		$expectedObjectPathMapping->setUriPattern('SomeUriPattern');
		$expectedObjectPathMapping->setPathSegment('-1');
		$expectedObjectPathMapping->setIdentifier('TheIdentifier');
		$this->mockObjectPathMappingRepository->expects($this->once())->method('add')->with($expectedObjectPathMapping);
		$this->mockPersistenceManager->expects($this->once())->method('persistAll');

		$this->identityRoutePart->setObjectType('stdClass');
		$this->identityRoutePart->setUriPattern('SomeUriPattern');
		$this->assertTrue($this->identityRoutePart->_call('resolveValue', $object));
		$this->assertSame('-1', $this->identityRoutePart->getValue());
	}

	/**
	 * @test
	 * @expectedException \TYPO3\Flow\Mvc\Exception\InfiniteLoopException
	 */
	public function resolveValueThrowsInfiniteLoopExceptionIfNoUniquePathSegmentCantBeFound() {
		$object = new \stdClass();
		$this->mockPersistenceManager->expects($this->atLeastOnce())->method('getIdentifierByObject')->with($object)->will($this->returnValue('TheIdentifier'));
		$this->mockPersistenceManager->expects($this->atLeastOnce())->method('getObjectByIdentifier')->with('TheIdentifier')->will($this->returnValue($object));
		$this->mockObjectPathMappingRepository->expects($this->once())->method('findOneByObjectTypeUriPatternAndIdentifier')->with('stdClass', 'SomeUriPattern', 'TheIdentifier')->will($this->returnValue(NULL));

		$existingObjectPathMapping = new ObjectPathMapping();
		$existingObjectPathMapping->setObjectType('stdClass');
		$existingObjectPathMapping->setUriPattern('SomeUriPattern');
		$existingObjectPathMapping->setPathSegment('The/Path/Segment');
		$existingObjectPathMapping->setIdentifier('AnotherIdentifier');

		$this->identityRoutePart->expects($this->once())->method('createPathSegmentForObject')->with($object)->will($this->returnValue('The/Path/Segment'));
		$this->mockObjectPathMappingRepository->expects($this->atLeastOnce())->method('findOneByObjectTypeUriPatternAndPathSegment')->will($this->returnValue($existingObjectPathMapping));

		$this->identityRoutePart->setObjectType('stdClass');
		$this->identityRoutePart->setUriPattern('SomeUriPattern');
		$this->identityRoutePart->_call('resolveValue', $object);
	}

	/**
	 * data provider for createPathSegmentForObjectTests()
	 * @return array
	 */
	public function createPathSegmentForObjectProvider() {
		$object = new \stdClass();
		$object->property1 = 'Property1Value';
		$object->property2 = 'Property2Välüe';
		$object->dateProperty = new \DateTime('1980-12-13');
		$subObject = new \stdClass();
		$subObject->subObjectProperty = 'SubObjectPropertyValue';
		$object->subObject = $subObject;
		return array(
			array($object, '{property1}', 'Property1Value'),
			array($object, '{property2}', 'Property2Vaeluee'),
			array($object, '{property1}{property2}', 'Property1ValueProperty2Vaeluee'),
			array($object, '{property1}/static{property2}', 'Property1Value/staticProperty2Vaeluee'),
			array($object, 'stäticValüe1/staticValue2{property2}staticValue3{property1}staticValue4', 'stäticValüe1/staticValue2Property2VaelueestaticValue3Property1ValuestaticValue4'),
			array($object, '{nonExistingProperty}', ''),
			array($object, '{dateProperty}', '1980-12-13'),
			array($object, '{dateProperty:y}', '80'),
			array($object, '{dateProperty:Y}/{dateProperty:m}/{dateProperty:d}', '1980/12/13'),
			array($object, '{subObject.subObjectProperty}', 'SubObjectPropertyValue'),
		);
	}

	/**
	 * @test
	 * @dataProvider createPathSegmentForObjectProvider
	 * @param object $object
	 * @param string $uriPattern
	 * @param string $expectedResult
	 * @return void
	 */
	public function createPathSegmentForObjectTests($object, $uriPattern, $expectedResult) {
		$identityRoutePart = $this->getAccessibleMock('TYPO3\Flow\Mvc\Routing\IdentityRoutePart', array('dummy'));
		$identityRoutePart->setUriPattern($uriPattern);
		$actualResult = $identityRoutePart->_call('createPathSegmentForObject', $object);
		$this->assertSame($expectedResult, $actualResult);
	}

	/**
	 * @test
	 * @expectedException \TYPO3\Flow\Mvc\Exception\InvalidUriPatternException
	 */
	public function createPathSegmentForObjectThrowsInvalidUriPatterExceptionIfItSpecifiedPropertiesContainObjects() {
		$identityRoutePart = $this->getAccessibleMock('TYPO3\Flow\Mvc\Routing\IdentityRoutePart', array('dummy'));
		$object = new \stdClass();
		$object->objectProperty = new \stdClass();
		$identityRoutePart->setUriPattern('{objectProperty}');
		$identityRoutePart->_call('createPathSegmentForObject', $object);
	}
}
