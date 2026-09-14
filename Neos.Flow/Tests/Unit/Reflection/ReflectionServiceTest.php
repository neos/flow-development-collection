<?php

declare(strict_types=1);

namespace Neos\Flow\Tests\Unit\Reflection;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */
use Neos\Flow\Reflection\Exception\ClassLoadingForReflectionFailedException;
use Neos\Flow\Reflection\ReflectionService;
use Neos\Flow\Tests\Unit\Reflection\Fixture\AliasedClass;
use Neos\Flow\Tests\Unit\Reflection\Fixture\ClassWithAliasDependency;
use Neos\Flow\Tests\Unit\Reflection\Fixture\ClassWithDifferentNameDifferent;
use Neos\Flow\Tests\Unit\Reflection\Fixture\FileWithNoClass;
use Neos\Flow\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Test;

require_once("Fixture/AliasedClass.php");
require_once("Fixture/ClassWithAliasDependency.php");
require_once("Fixture/FileWithNoClass.php");

/**
 * Testcase for the ReflectionService
 *
 */
final class ReflectionServiceTest extends UnitTestCase
{
    /**
     * @var ReflectionService
     */
    protected $reflectionService;

    protected function setUp(): void
    {
        $this->reflectionService = $this->getAccessibleMock(ReflectionService::class);

        $mockAnnotationReader = $this->createMock('Doctrine\Common\Annotations\Reader');
        $mockAnnotationReader->method('getClassAnnotations')->willReturn([]);
        $mockAnnotationReader->method('getMethodAnnotations')->willReturn([]);
        $this->inject($this->reflectionService, 'annotationReader', $mockAnnotationReader);
        $this->reflectionService->_set('initialized', true);
    }

    #[Test]
    public function reflectClassThrowsExceptionForNonExistingClasses()
    {
        $this->expectException(ClassLoadingForReflectionFailedException::class);
        $this->reflectionService->_call('reflectClass', 'Non\Existing\Class');
    }

    #[Test]
    public function reflectClassThrowsExceptionForFilesWithNoClass()
    {
        $this->expectException(ClassLoadingForReflectionFailedException::class);
        $this->reflectionService->_call('reflectClass', FileWithNoClass::class);
    }

    #[Test]
    public function reflectClassThrowsExceptionForClassesWithNoMatchingFilename()
    {
        $this->expectException(ClassLoadingForReflectionFailedException::class);
        $this->reflectionService->_call('reflectClass', ClassWithDifferentNameDifferent::class);
    }

    #[Test]
    public function getMethodParametersReturnsCorrectTypeForAliasedClass()
    {
        $this->reflectionService->_call('reflectClass', ClassWithAliasDependency::class);
        $parameters = $this->reflectionService->getMethodParameters(ClassWithAliasDependency::class, 'injectDependency');
        $this->assertEquals(AliasedClass::class, array_pop($parameters)['class']);
    }

    #[Test]
    public function isTagIgnoredReturnsTrueForIgnoredTags()
    {
        $settings = ['reflection' => ['ignoredTags' => ['ignored' => true]]];
        $this->reflectionService->injectSettings($settings);

        self::assertTrue($this->reflectionService->_call('isTagIgnored', 'ignored'));
    }

    #[Test]
    public function isTagIgnoredReturnsFalseForTagsThatAreNotIgnored()
    {
        $settings = ['reflection' => ['ignoredTags' => ['notignored' => false]]];
        $this->reflectionService->injectSettings($settings);

        self::assertFalse($this->reflectionService->_call('isTagIgnored', 'notignored'));
    }

    #[Test]
    public function isTagIgnoredReturnsFalseForTagsThatAreNotConfigured()
    {
        $settings = ['reflection' => ['ignoredTags' => ['ignored' => true, 'notignored' => false]]];
        $this->reflectionService->injectSettings($settings);

        self::assertFalse($this->reflectionService->_call('isTagIgnored', 'notconfigured'));
    }

    #[Test]
    public function isTagIgnoredWorksWithOldConfiguration()
    {
        $settings = ['reflection' => ['ignoredTags' => ['ignored']]];
        $this->reflectionService->injectSettings($settings);

        self::assertTrue($this->reflectionService->_call('isTagIgnored', 'ignored'));
        self::assertFalse($this->reflectionService->_call('isTagIgnored', 'notignored'));
    }
}
