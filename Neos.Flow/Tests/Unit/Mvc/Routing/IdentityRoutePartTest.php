<?php

declare(strict_types=1);

namespace Neos\Flow\Tests\Unit\Mvc\Routing;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */
use Neos\Flow\Mvc\Exception\InfiniteLoopException;
use Neos\Flow\Mvc\Exception\InvalidUriPatternException;
use Neos\Flow\Mvc\Routing\IdentityRoutePart;
use Neos\Flow\Mvc\Routing\ObjectPathMapping;
use Neos\Flow\Mvc\Routing\ObjectPathMappingRepository;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Flow\Reflection\ClassSchema;
use Neos\Flow\Reflection\ReflectionService;
use Neos\Flow\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Testcase for the MVC Web Routing IdentityRoutePart Class
 */
final class IdentityRoutePartTest extends UnitTestCase
{
    /**
     * @var IdentityRoutePart
     */
    protected $identityRoutePart;

    /**
     * @var PersistenceManagerInterface|MockObject
     */
    protected $mockPersistenceManager;

    /**
     * @var ClassSchema|MockObject
     */
    protected $mockClassSchema;

    /**
     * @var ObjectPathMappingRepository|MockObject
     */
    protected $mockObjectPathMappingRepository;

    /**
     * Sets up this test case
     */
    protected function setUp(): void
    {
        $this->identityRoutePart = $this->getAccessibleMock(IdentityRoutePart::class, ['createPathSegmentForObject']);

        $this->mockPersistenceManager = $this->createMock(PersistenceManagerInterface::class);
        $this->identityRoutePart->_set('persistenceManager', $this->mockPersistenceManager);

        $mockReflectionService = $this->createMock(ReflectionService::class);
        $this->mockClassSchema = $this->createMock(ClassSchema::class);
        $mockReflectionService->method('getClassSchema')->willReturn(($this->mockClassSchema));
        $this->identityRoutePart->_set('reflectionService', $mockReflectionService);

        $this->mockObjectPathMappingRepository = $this->createMock(ObjectPathMappingRepository::class);
        $this->identityRoutePart->_set('objectPathMappingRepository', $this->mockObjectPathMappingRepository);
    }

    #[Test]
    public function getUriPatternReturnsTheSpecifiedUriPatternIfItsNotEmpty()
    {
        $this->identityRoutePart->setUriPattern('SomeUriPattern');
        self::assertSame('SomeUriPattern', $this->identityRoutePart->getUriPattern());
    }

    #[Test]
    public function getUriPatternReturnsAnEmptyStringIfObjectTypeHasNotIdentityPropertiesAndNoPatternWasSpecified()
    {
        $this->mockClassSchema->expects($this->once())->method('getIdentityProperties')->willReturn(([]));

        $this->identityRoutePart->setObjectType('SomeObjectType');
        self::assertSame('', $this->identityRoutePart->getUriPattern());
    }

    #[Test]
    public function getUriPatternReturnsBasedOnTheIdentityPropertiesOfTheObjectTypeIfNoPatternWasSpecified()
    {
        $this->mockClassSchema->expects($this->once())->method('getIdentityProperties')->willReturn((['property1' => 'string', 'property2' => 'integer', 'property3' => 'DateTime']));
        $this->identityRoutePart->setObjectType('SomeObjectType');
        self::assertSame('{property1}/{property2}/{property3}', $this->identityRoutePart->getUriPattern());
    }

    #[Test]
    public function matchValueReturnsFalseIfTheGivenValueIsEmptyOrNull()
    {
        self::assertFalse($this->identityRoutePart->_call('matchValue', ''));
        self::assertFalse($this->identityRoutePart->_call('matchValue', null));
    }

    #[Test]
    public function matchValueReturnsFalseIfNoObjectPathMappingCouldBeFound()
    {
        $this->mockObjectPathMappingRepository->expects($this->once())->method('findOneByObjectTypeUriPatternAndPathSegment')->with('SomeObjectType', 'SomeUriPattern', 'TheRoutePath', false)->willReturn((null));
        $this->identityRoutePart->setObjectType('SomeObjectType');
        $this->identityRoutePart->setUriPattern('SomeUriPattern');
        self::assertFalse($this->identityRoutePart->_call('matchValue', 'TheRoutePath'));
    }

    #[Test]
    public function matchValueSetsTheIdentifierOfTheObjectPathMappingAndReturnsTrueIfAMatchingObjectPathMappingWasFound()
    {
        $mockObjectPathMapping = $this->createMock(ObjectPathMapping::class);
        $mockObjectPathMapping->expects($this->once())->method('getIdentifier')->willReturn(('TheIdentifier'));
        $this->mockObjectPathMappingRepository->expects($this->once())->method('findOneByObjectTypeUriPatternAndPathSegment')->with('SomeObjectType', 'SomeUriPattern', 'TheRoutePath', false)->willReturn(($mockObjectPathMapping));
        $this->identityRoutePart->setObjectType('SomeObjectType');
        $this->identityRoutePart->setUriPattern('SomeUriPattern');

        self::assertTrue($this->identityRoutePart->_call('matchValue', 'TheRoutePath'));
        $expectedResult = ['__identity' => 'TheIdentifier'];
        $actualResult = $this->identityRoutePart->getValue();
        self::assertSame($expectedResult, $actualResult);
    }

    #[Test]
    public function matchValueSetsTheRouteValueToTheUrlDecodedPathSegmentIfNoUriPatternIsSpecified()
    {
        $this->mockClassSchema->method('getIdentityProperties')->willReturn(([]));

        $this->mockPersistenceManager->expects($this->once())->method('getObjectByIdentifier')->with('The Identifier', 'stdClass')->willReturn((new \stdClass()));

        $this->mockObjectPathMappingRepository->expects($this->never())->method('findOneByObjectTypeUriPatternAndPathSegment');

        $this->identityRoutePart->setObjectType('stdClass');

        self::assertTrue($this->identityRoutePart->_call('matchValue', 'The%20Identifier'));
        $expectedResult = ['__identity' => 'The Identifier'];
        $actualResult = $this->identityRoutePart->getValue();
        self::assertSame($expectedResult, $actualResult);
    }

    #[Test]
    public function matchValueSetsCaseSensitiveFlagIfLowerCaseIsFalse()
    {
        $this->mockObjectPathMappingRepository->expects($this->once())->method('findOneByObjectTypeUriPatternAndPathSegment')->with('SomeObjectType', 'SomeUriPattern', 'TheRoutePath', true);
        $this->identityRoutePart->setObjectType('SomeObjectType');
        $this->identityRoutePart->setUriPattern('SomeUriPattern');
        $this->identityRoutePart->setLowerCase(false);

        $this->identityRoutePart->_call('matchValue', 'TheRoutePath');
    }

    #[Test]
    public function findValueToMatchReturnsAnEmptyStringIfTheRoutePathIsEmpty()
    {
        self::assertSame('', $this->identityRoutePart->_call('findValueToMatch', null));
        self::assertSame('', $this->identityRoutePart->_call('findValueToMatch', ''));
        self::assertSame('', $this->identityRoutePart->_call('findValueToMatch', '/'));
    }

    #[Test]
    public function findValueToMatchReturnsAnEmptyStringIfTheSpecifiedSplitStringCantBeFoundInTheRoutePath()
    {
        $this->identityRoutePart->setUriPattern('');
        $this->identityRoutePart->setSplitString('SplitStringThatIsNotInTheCurrentRoutePath');
        self::assertSame('', $this->identityRoutePart->_call('findValueToMatch', 'The/Complete/RoutPath'));
    }

    #[Test]
    public function findValueToMatchReturnsAnEmptyStringIfTheCalculatedUriPatternIsEmpty()
    {
        $this->identityRoutePart->setUriPattern('');
        $this->identityRoutePart->setSplitString('TheSplitString');
        self::assertSame('', $this->identityRoutePart->_call('findValueToMatch', 'First/Part/Of/The/Complete/RoutPath/TheSplitString/SomeThingElse'));
    }

    /**
     * data provider for findValueToMatchTests()
     * @return \Iterator<(int | string), mixed>
     */
    public static function findValueToMatchProvider(): \Iterator
    {
        yield ['staticPattern/Foo', 'staticPattern', '/Foo', 'staticPattern'];
        yield ['staticPattern/Foo', 'staticPattern', 'NonExistingSplitString', ''];
        yield ['The/Route/Path', '{property1}/{property2}', '/Path', 'The/Route'];
        yield ['static/dynamic/splitString', 'static/{property1}', '/splitString', 'static/dynamic'];
        yield ['dynamic/exceeding/splitString', '{property1}', '/splitString', ''];
        yield ['dynamic1static1dynamic2/static2splitString', '{property1}static1{property2}/static2', 'splitString', 'dynamic1static1dynamic2/static2'];
        yield ['static1dynamic1dynamic2/static2splitString', 'static1{property1}{property2}/static2', 'splitString', 'static1dynamic1dynamic2/static2'];
        yield ['foo/bar/baz', '{foo}/{bar}', '/', 'foo/bar'];
        yield ['foo/bar/baz', '{foo}/{bar}', '/baz', 'foo/bar'];
        yield ['foo/bar/notTheSplitString', '{foo}/{bar}', '/splitString', ''];
    }

    /**
     * @param string $routePath
     * @param string $uriPattern
     * @param string $splitString
     * @param string $expectedResult
     * @return void
     */
    #[DataProvider('findValueToMatchProvider')]
    #[Test]
    public function findValueToMatchTests($routePath, $uriPattern, $splitString, $expectedResult)
    {
        $this->identityRoutePart->setUriPattern($uriPattern);
        $this->identityRoutePart->setSplitString($splitString);
        self::assertSame($expectedResult, $this->identityRoutePart->_call('findValueToMatch', $routePath));
    }

    #[Test]
    public function resolveValueAcceptsIdentityArrays()
    {
        $value = ['__identity' => 'SomeIdentifier'];
        $mockObjectPathMapping = $this->createMock(ObjectPathMapping::class);
        $mockObjectPathMapping->expects($this->once())->method('getPathSegment')->willReturn(('ThePathSegment'));
        $this->mockPersistenceManager->expects($this->never())->method('getIdentifierByObject');
        $this->mockObjectPathMappingRepository->expects($this->once())->method('findOneByObjectTypeUriPatternAndIdentifier')->with('stdClass', 'SomeUriPattern', 'SomeIdentifier')->willReturn(($mockObjectPathMapping));

        $this->identityRoutePart->setObjectType('stdClass');
        $this->identityRoutePart->setUriPattern('SomeUriPattern');
        self::assertTrue($this->identityRoutePart->_call('resolveValue', $value));
        self::assertSame('thepathsegment', $this->identityRoutePart->getValue());
    }

    #[Test]
    public function resolveValueDoesNotAcceptObjectsWithMultiValueIdentifiers()
    {
        $value = new \stdClass();
        $this->mockPersistenceManager->expects($this->once())->method('getIdentifierByObject')->with($value)->willReturn((['foo' => 'Foo', 'bar' => 'Bar']));

        $this->identityRoutePart->setObjectType('stdClass');
        $this->identityRoutePart->setUriPattern('SomeUriPattern');
        self::assertFalse($this->identityRoutePart->_call('resolveValue', $value));
    }

    /**
     * Makes also sure that identity route parts are encoded via rawurlencode (which encodes spaces to "%20") and not
     * urlencode (which encodes spaces to "+"). According to RFC 3986 that is correct for path segments.
     */
    #[Test]
    public function resolveValueSetsTheRouteValueToTheUrlEncodedIdentifierIfNoUriPatternIsSpecified()
    {
        $this->mockClassSchema->method('getIdentityProperties')->willReturn(([]));

        $value = ['__identity' => 'Some Identifier'];
        $this->mockObjectPathMappingRepository->expects($this->never())->method('findOneByObjectTypeUriPatternAndIdentifier');

        $this->identityRoutePart->setObjectType('stdClass');

        $this->identityRoutePart->_call('resolveValue', $value);
        self::assertSame('Some%20Identifier', $this->identityRoutePart->getValue());
        self::assertNotSame('Some+Identifier', $this->identityRoutePart->getValue());
    }

    #[Test]
    public function resolveValueConvertsCaseOfResolvedPathSegmentIfLowerCaseIsTrue()
    {
        $value = ['__identity' => 'SomeIdentifier'];
        $mockObjectPathMapping = $this->createMock(ObjectPathMapping::class);
        $mockObjectPathMapping->expects($this->once())->method('getPathSegment')->willReturn(('ThePathSegment'));
        $this->mockObjectPathMappingRepository->expects($this->once())->method('findOneByObjectTypeUriPatternAndIdentifier')->with('stdClass', 'SomeUriPattern', 'SomeIdentifier')->willReturn(($mockObjectPathMapping));

        $this->identityRoutePart->setObjectType('stdClass');
        $this->identityRoutePart->setUriPattern('SomeUriPattern');
        $this->identityRoutePart->setLowerCase(true);

        $this->identityRoutePart->_call('resolveValue', $value);
        self::assertSame('thepathsegment', $this->identityRoutePart->getValue());
    }

    #[Test]
    public function resolveValueKeepsCaseOfResolvedPathSegmentIfLowerCaseIsTrue()
    {
        $value = ['__identity' => 'SomeIdentifier'];
        $mockObjectPathMapping = $this->createMock(ObjectPathMapping::class);
        $mockObjectPathMapping->expects($this->once())->method('getPathSegment')->willReturn(('ThePathSegment'));
        $this->mockObjectPathMappingRepository->expects($this->once())->method('findOneByObjectTypeUriPatternAndIdentifier')->with('stdClass', 'SomeUriPattern', 'SomeIdentifier')->willReturn(($mockObjectPathMapping));

        $this->identityRoutePart->setObjectType('stdClass');
        $this->identityRoutePart->setUriPattern('SomeUriPattern');
        $this->identityRoutePart->setLowerCase(false);

        $this->identityRoutePart->_call('resolveValue', $value);
        self::assertSame('ThePathSegment', $this->identityRoutePart->getValue());
    }

    #[Test]
    public function resolveValueReturnsFalseIfTheGivenValueIsNotOfTheSpecifiedType()
    {
        $this->identityRoutePart->setObjectType('SomeObjectType');
        self::assertFalse($this->identityRoutePart->_call('resolveValue', new \stdClass()));
    }

    #[Test]
    public function resolveValueSetsTheValueToThePathSegmentOfTheObjectPathMappingAndReturnsTrueIfAMatchingObjectPathMappingWasFound()
    {
        $object = new \stdClass();
        $mockObjectPathMapping = $this->createMock(ObjectPathMapping::class);
        $mockObjectPathMapping->expects($this->once())->method('getPathSegment')->willReturn(('ThePathSegment'));
        $this->mockPersistenceManager->expects($this->once())->method('getIdentifierByObject')->with($object)->willReturn(('TheIdentifier'));
        $this->mockObjectPathMappingRepository->expects($this->once())->method('findOneByObjectTypeUriPatternAndIdentifier')->with('stdClass', 'SomeUriPattern', 'TheIdentifier')->willReturn(($mockObjectPathMapping));

        $this->identityRoutePart->setObjectType('stdClass');
        $this->identityRoutePart->setUriPattern('SomeUriPattern');
        self::assertTrue($this->identityRoutePart->_call('resolveValue', $object));
        self::assertSame('thepathsegment', $this->identityRoutePart->getValue());
    }

    #[Test]
    public function resolveValueCreatesAndStoresANewObjectPathMappingIfNoMatchingObjectPathMappingWasFound()
    {
        $object = new \stdClass();
        $this->mockPersistenceManager->expects($this->atLeastOnce())->method('getIdentifierByObject')->with($object)->willReturn(('TheIdentifier'));
        $this->mockPersistenceManager->expects($this->atLeastOnce())->method('getObjectByIdentifier')->with('TheIdentifier')->willReturn(($object));
        $this->mockObjectPathMappingRepository->expects($this->once())->method('findOneByObjectTypeUriPatternAndIdentifier')->with('stdClass', 'SomeUriPattern', 'TheIdentifier')->willReturn((null));

        $this->identityRoutePart->expects($this->once())->method('createPathSegmentForObject')->with($object)->willReturn(('The/Path/Segment'));
        $this->mockObjectPathMappingRepository->expects($this->once())->method('findOneByObjectTypeUriPatternAndPathSegment')->with('stdClass', 'SomeUriPattern', 'The/Path/Segment', false)->willReturn((null));

        $expectedObjectPathMapping = new ObjectPathMapping();
        $expectedObjectPathMapping->setObjectType('stdClass');
        $expectedObjectPathMapping->setUriPattern('SomeUriPattern');
        $expectedObjectPathMapping->setPathSegment('The/Path/Segment');
        $expectedObjectPathMapping->setIdentifier('TheIdentifier');
        $this->mockObjectPathMappingRepository->expects($this->once())->method('add')->with($expectedObjectPathMapping);
        $this->mockObjectPathMappingRepository->expects($this->once())->method('persistEntities');

        $this->identityRoutePart->setObjectType('stdClass');
        $this->identityRoutePart->setUriPattern('SomeUriPattern');
        self::assertTrue($this->identityRoutePart->_call('resolveValue', $object));
        self::assertSame('the/path/segment', $this->identityRoutePart->getValue());
    }

    #[Test]
    public function resolveValueAppendsCounterIfNoMatchingObjectPathMappingWasFoundAndCreatedPathSegmentIsNotUnique()
    {
        $object = new \stdClass();
        $this->mockPersistenceManager->expects($this->atLeastOnce())->method('getIdentifierByObject')->with($object)->willReturn(('TheIdentifier'));
        $this->mockPersistenceManager->expects($this->atLeastOnce())->method('getObjectByIdentifier')->with('TheIdentifier')->willReturn(($object));
        $this->mockObjectPathMappingRepository->expects($this->once())->method('findOneByObjectTypeUriPatternAndIdentifier')->with('stdClass', 'SomeUriPattern', 'TheIdentifier')->willReturn((null));

        $existingObjectPathMapping = new ObjectPathMapping();
        $existingObjectPathMapping->setObjectType('stdClass');
        $existingObjectPathMapping->setUriPattern('SomeUriPattern');
        $existingObjectPathMapping->setPathSegment('The/Path/Segment');
        $existingObjectPathMapping->setIdentifier('AnotherIdentifier');

        $this->identityRoutePart->expects($this->once())->method('createPathSegmentForObject')->with($object)->willReturn(('The/Path/Segment'));
        $matcher = self::exactly(3);
        $this->mockObjectPathMappingRepository->expects($matcher)->method('findOneByObjectTypeUriPatternAndPathSegment')->willReturnCallback(function (...$parameters) use ($matcher, $existingObjectPathMapping) {
            if ($matcher->numberOfInvocations() === 1) {
                $this->assertSame('stdClass', $parameters[0]);
                $this->assertSame('SomeUriPattern', $parameters[1]);
                $this->assertSame('The/Path/Segment', $parameters[2]);
                $this->assertFalse($parameters[3]);
                return $existingObjectPathMapping;
            }
            if ($matcher->numberOfInvocations() === 2) {
                $this->assertSame('stdClass', $parameters[0]);
                $this->assertSame('SomeUriPattern', $parameters[1]);
                $this->assertSame('The/Path/Segment-1', $parameters[2]);
                $this->assertFalse($parameters[3]);
                return $existingObjectPathMapping;
            }
            if ($matcher->numberOfInvocations() === 3) {
                $this->assertSame('stdClass', $parameters[0]);
                $this->assertSame('SomeUriPattern', $parameters[1]);
                $this->assertSame('The/Path/Segment-2', $parameters[2]);
                $this->assertFalse($parameters[3]);
                return null;
            }
        });

        $expectedObjectPathMapping = new ObjectPathMapping();
        $expectedObjectPathMapping->setObjectType('stdClass');
        $expectedObjectPathMapping->setUriPattern('SomeUriPattern');
        $expectedObjectPathMapping->setPathSegment('The/Path/Segment-2');
        $expectedObjectPathMapping->setIdentifier('TheIdentifier');
        $this->mockObjectPathMappingRepository->expects($this->once())->method('add')->with($expectedObjectPathMapping);
        $this->mockObjectPathMappingRepository->expects($this->once())->method('persistEntities');

        $this->identityRoutePart->setObjectType('stdClass');
        $this->identityRoutePart->setUriPattern('SomeUriPattern');
        self::assertTrue($this->identityRoutePart->_call('resolveValue', $object));
        self::assertSame('the/path/segment-2', $this->identityRoutePart->getValue());
    }

    #[Test]
    public function resolveValueSetsCaseSensitiveFlagIfLowerCaseIsFalse()
    {
        $object = new \stdClass();
        $this->mockPersistenceManager->expects($this->atLeastOnce())->method('getIdentifierByObject')->with($object)->willReturn(('TheIdentifier'));
        $this->mockPersistenceManager->expects($this->atLeastOnce())->method('getObjectByIdentifier')->with('TheIdentifier')->willReturn(($object));
        $this->mockObjectPathMappingRepository->expects($this->once())->method('findOneByObjectTypeUriPatternAndIdentifier')->with('stdClass', 'SomeUriPattern', 'TheIdentifier')->willReturn((null));

        $existingObjectPathMapping = new ObjectPathMapping();
        $existingObjectPathMapping->setObjectType('stdClass');
        $existingObjectPathMapping->setUriPattern('SomeUriPattern');
        $existingObjectPathMapping->setPathSegment('The/Path/Segment');
        $existingObjectPathMapping->setIdentifier('AnotherIdentifier');

        $this->identityRoutePart->expects($this->once())->method('createPathSegmentForObject')->with($object)->willReturn(('The/Path/Segment'));
        $matcher = self::exactly(2);
        $this->mockObjectPathMappingRepository->expects($matcher)->method('findOneByObjectTypeUriPatternAndPathSegment')->willReturnCallback(function (...$parameters) use ($matcher, $existingObjectPathMapping) {
            if ($matcher->numberOfInvocations() === 1) {
                $this->assertSame('stdClass', $parameters[0]);
                $this->assertSame('SomeUriPattern', $parameters[1]);
                $this->assertSame('The/Path/Segment', $parameters[2]);
                $this->assertTrue($parameters[3]);
                return $existingObjectPathMapping;
            }
            if ($matcher->numberOfInvocations() === 2) {
                $this->assertSame('stdClass', $parameters[0]);
                $this->assertSame('SomeUriPattern', $parameters[1]);
                $this->assertSame('The/Path/Segment-1', $parameters[2]);
                $this->assertTrue($parameters[3]);
                return null;
            }
        });

        $expectedObjectPathMapping = new ObjectPathMapping();
        $expectedObjectPathMapping->setObjectType('stdClass');
        $expectedObjectPathMapping->setUriPattern('SomeUriPattern');
        $expectedObjectPathMapping->setPathSegment('The/Path/Segment-1');
        $expectedObjectPathMapping->setIdentifier('TheIdentifier');
        $this->mockObjectPathMappingRepository->expects($this->once())->method('add')->with($expectedObjectPathMapping);
        $this->mockObjectPathMappingRepository->expects($this->once())->method('persistEntities');

        $this->identityRoutePart->setObjectType('stdClass');
        $this->identityRoutePart->setUriPattern('SomeUriPattern');
        $this->identityRoutePart->setLowerCase(false);
        self::assertTrue($this->identityRoutePart->_call('resolveValue', $object));
        self::assertSame('The/Path/Segment-1', $this->identityRoutePart->getValue());
    }

    #[Test]
    public function resolveValueAppendsCounterIfCreatedPathSegmentIsEmpty()
    {
        $object = new \stdClass();
        $this->mockPersistenceManager->expects($this->atLeastOnce())->method('getIdentifierByObject')->with($object)->willReturn(('TheIdentifier'));
        $this->mockPersistenceManager->expects($this->atLeastOnce())->method('getObjectByIdentifier')->with('TheIdentifier')->willReturn(($object));
        $this->mockObjectPathMappingRepository->expects($this->once())->method('findOneByObjectTypeUriPatternAndIdentifier')->with('stdClass', 'SomeUriPattern', 'TheIdentifier')->willReturn((null));

        $this->identityRoutePart->expects($this->once())->method('createPathSegmentForObject')->with($object)->willReturn((''));
        $this->mockObjectPathMappingRepository->expects($this->once())->method('findOneByObjectTypeUriPatternAndPathSegment')->with('stdClass', 'SomeUriPattern', '-1', false)->willReturn((null));

        $expectedObjectPathMapping = new ObjectPathMapping();
        $expectedObjectPathMapping->setObjectType('stdClass');
        $expectedObjectPathMapping->setUriPattern('SomeUriPattern');
        $expectedObjectPathMapping->setPathSegment('-1');
        $expectedObjectPathMapping->setIdentifier('TheIdentifier');
        $this->mockObjectPathMappingRepository->expects($this->once())->method('add')->with($expectedObjectPathMapping);
        $this->mockObjectPathMappingRepository->expects($this->once())->method('persistEntities');

        $this->identityRoutePart->setObjectType('stdClass');
        $this->identityRoutePart->setUriPattern('SomeUriPattern');
        self::assertTrue($this->identityRoutePart->_call('resolveValue', $object));
        self::assertSame('-1', $this->identityRoutePart->getValue());
    }

    #[Test]
    public function resolveValueThrowsInfiniteLoopExceptionIfNoUniquePathSegmentCantBeFound()
    {
        $this->expectException(InfiniteLoopException::class);
        $object = new \stdClass();
        $this->mockPersistenceManager->expects($this->atLeastOnce())->method('getIdentifierByObject')->with($object)->willReturn(('TheIdentifier'));
        $this->mockPersistenceManager->expects($this->atLeastOnce())->method('getObjectByIdentifier')->with('TheIdentifier')->willReturn(($object));
        $this->mockObjectPathMappingRepository->expects($this->once())->method('findOneByObjectTypeUriPatternAndIdentifier')->with('stdClass', 'SomeUriPattern', 'TheIdentifier')->willReturn((null));

        $existingObjectPathMapping = new ObjectPathMapping();
        $existingObjectPathMapping->setObjectType('stdClass');
        $existingObjectPathMapping->setUriPattern('SomeUriPattern');
        $existingObjectPathMapping->setPathSegment('The/Path/Segment');
        $existingObjectPathMapping->setIdentifier('AnotherIdentifier');

        $this->identityRoutePart->expects($this->once())->method('createPathSegmentForObject')->with($object)->willReturn(('The/Path/Segment'));
        $this->mockObjectPathMappingRepository->expects($this->atLeastOnce())->method('findOneByObjectTypeUriPatternAndPathSegment')->willReturn(($existingObjectPathMapping));

        $this->identityRoutePart->setObjectType('stdClass');
        $this->identityRoutePart->setUriPattern('SomeUriPattern');
        $this->identityRoutePart->_call('resolveValue', $object);
    }

    /**
     * data provider for createPathSegmentForObjectTests()
     * @return \Iterator<(int | string), mixed>
     */
    public static function createPathSegmentForObjectProvider(): \Iterator
    {
        $object = new \stdClass();
        $object->property1 = 'Property1Value';
        $object->property2 = 'Property2Välüe';
        $object->dateProperty = new \DateTime('1980-12-13');
        $subObject = new \stdClass();
        $subObject->subObjectProperty = 'SubObjectPropertyValue';
        $object->subObject = $subObject;
        yield [$object, '{property1}', 'Property1Value'];
        yield [$object, '{property2}', 'Property2Vaeluee'];
        yield [$object, '{property1}{property2}', 'Property1ValueProperty2Vaeluee'];
        yield [$object, '{property1}/static{property2}', 'Property1Value/staticProperty2Vaeluee'];
        yield [$object, 'stäticValüe1/staticValue2{property2}staticValue3{property1}staticValue4', 'stäticValüe1/staticValue2Property2VaelueestaticValue3Property1ValuestaticValue4'];
        yield [$object, '{nonExistingProperty}', ''];
        yield [$object, '{dateProperty}', '1980-12-13'];
        yield [$object, '{dateProperty:y}', '80'];
        yield [$object, '{dateProperty:Y}/{dateProperty:m}/{dateProperty:d}', '1980/12/13'];
        yield [$object, '{subObject.subObjectProperty}', 'SubObjectPropertyValue'];
    }

    /**
     * @param object $object
     * @param string $uriPattern
     * @param string $expectedResult
     * @return void
     */
    #[DataProvider('createPathSegmentForObjectProvider')]
    #[Test]
    public function createPathSegmentForObjectTests($object, $uriPattern, $expectedResult)
    {
        $identityRoutePart = $this->getAccessibleMock(IdentityRoutePart::class, []);
        $identityRoutePart->setUriPattern($uriPattern);
        $actualResult = $identityRoutePart->_call('createPathSegmentForObject', $object);
        self::assertSame($expectedResult, $actualResult);
    }

    #[Test]
    public function createPathSegmentForObjectThrowsInvalidUriPatterExceptionIfItSpecifiedPropertiesContainObjects()
    {
        $this->expectException(InvalidUriPatternException::class);
        $identityRoutePart = $this->getAccessibleMock(IdentityRoutePart::class, []);
        $object = new \stdClass();
        $object->objectProperty = new \stdClass();
        $identityRoutePart->setUriPattern('{objectProperty}');
        $identityRoutePart->_call('createPathSegmentForObject', $object);
    }
}
