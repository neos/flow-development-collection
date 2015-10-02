<?php
namespace TYPO3\Flow\Tests\Unit\Reflection;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Doctrine\Common\Annotations\Reader;
use TYPO3\Flow\Reflection\ReflectionService;
use TYPO3\Flow\Tests\UnitTestCase;

/**
 * Testcase for the ReflectionService
 *
 */
class ReflectionServiceTest extends UnitTestCase
{
    /**
     * @var ReflectionService
     */
    protected $reflectionService;

    /**
     * @var Reader|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $mockAnnotationReader;

    public function setUp()
    {
        $this->reflectionService = $this->getAccessibleMock('TYPO3\Flow\Reflection\ReflectionService', null);

        $this->mockAnnotationReader = $this->getMockBuilder('Doctrine\Common\Annotations\Reader')->disableOriginalConstructor()->getMock();
        $this->mockAnnotationReader->expects($this->any())->method('getClassAnnotations')->will($this->returnValue(array()));
        $this->inject($this->reflectionService, 'annotationReader', $this->mockAnnotationReader);
    }

    /**
     * @test
     * @expectedException \TYPO3\Flow\Reflection\Exception\ClassLoadingForReflectionFailedException
     */
    public function reflectClassThrowsExceptionForNonExistingClasses()
    {
        $this->reflectionService->_call('reflectClass', 'Non\Existing\Class');
    }

    /**
     * @test
     * @expectedException \TYPO3\Flow\Reflection\Exception\ClassLoadingForReflectionFailedException
     */
    public function reflectClassThrowsExceptionForFilesWithNoClass()
    {
        $this->reflectionService->_call('reflectClass', 'TYPO3\Flow\Tests\Unit\Reflection\Fixture\FileWithNoClass');
    }

    /**
     * @test
     * @expectedException \TYPO3\Flow\Reflection\Exception\ClassLoadingForReflectionFailedException
     */
    public function reflectClassThrowsExceptionForClassesWithNoMatchingFilename()
    {
        $this->reflectionService->_call('reflectClass', 'TYPO3\Flow\Tests\Unit\Reflection\Fixture\ClassWithDifferentNameDifferent');
    }
}
