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

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Neos\Flow\Persistence\Doctrine\Repository;
use Neos\Flow\Tests\UnitTestCase;

/**
 * Testcase for the doctrine Repository
 */
class RepositoryTest extends UnitTestCase
{
    /**
     * @var EntityManagerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $mockEntityManager;

    /**
     * @var ClassMetadata|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $mockClassMetadata;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->mockEntityManager = $this->getMockBuilder(EntityManagerInterface::class)->disableOriginalConstructor()->getMock();

        $this->mockClassMetadata = $this->getMockBuilder(ClassMetadata::class)->disableOriginalConstructor()->getMock();
        $this->mockEntityManager->expects(self::any())->method('getClassMetadata')->will(self::returnValue($this->mockClassMetadata));
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
        self::assertEquals($modelClassName, $repository->getEntityClassName());
    }
}
