<?php
namespace Neos\Flow\Tests\Unit\Persistence\Doctrine;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Neos\Flow\Persistence\Doctrine\Repository;
use Neos\Flow\Tests\UnitTestCase;

/**
 * Testcase for the doctrine Repository
 */
class RepositoryTest extends UnitTestCase
{
    /**
     * @var ObjectManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $mockEntityManager;

    /**
     * @var ClassMetadata|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $mockClassMetadata;

    /**
     * @return void
     */
    public function setUp()
    {
        $this->mockEntityManager = $this->getMockBuilder(ObjectManager::class)->disableOriginalConstructor()->getMock();

        $this->mockClassMetadata = $this->getMockBuilder(ClassMetadata::class)->disableOriginalConstructor()->getMock();
        $this->mockEntityManager->expects($this->any())->method('getClassMetadata')->will($this->returnValue($this->mockClassMetadata));
    }

    /**
     * dataProvider for constructSetsObjectTypeFromClassName
     */
    public function modelAndRepositoryClassNames()
    {
        $idSuffix = uniqid();
        return [
            ['TYPO3\Blog\Domain\Repository', 'C' . $idSuffix . 'BlogRepository', 'TYPO3\Blog\Domain\Model\\' . 'C' . $idSuffix . 'Blog'],
            ['Domain\Repository\Content', 'C' . $idSuffix . 'PageRepository', 'Domain\Model\\Content\\' . 'C' . $idSuffix . 'Page'],
            ['Domain\Repository', 'C' . $idSuffix . 'RepositoryRepository', 'Domain\Model\\' . 'C' . $idSuffix . 'Repository']
        ];
    }

    /**
     * @test
     * @dataProvider modelAndRepositoryClassNames
     */
    public function constructSetsObjectTypeFromClassName($repositoryNamespace, $repositoryClassName, $modelClassName)
    {
        $mockClassName = $repositoryNamespace . '\\' . $repositoryClassName;
        eval('namespace ' . $repositoryNamespace . '; class ' . $repositoryClassName . ' extends \Neos\Flow\Persistence\Doctrine\Repository {}');

        /** @var Repository $repository */
        $repository = new $mockClassName($this->mockEntityManager);
        $this->assertEquals($modelClassName, $repository->getEntityClassName());
    }
}
