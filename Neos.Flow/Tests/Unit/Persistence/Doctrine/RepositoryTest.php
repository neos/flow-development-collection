<?php

declare(strict_types=1);

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
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Testcase for the doctrine Repository
 */
final class RepositoryTest extends UnitTestCase
{
    /**
     * @var EntityManagerInterface|MockObject
     */
    protected $mockEntityManager;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->mockEntityManager = $this->createMock(EntityManagerInterface::class);

        $mockClassMetadata = $this->createMock(ClassMetadata::class);
        $this->mockEntityManager->method('getClassMetadata')->willReturn(($mockClassMetadata));
    }

    /**
     * dataProvider for constructSetsObjectTypeFromClassName
     */
    public static function modelAndRepositoryClassNames(): \Iterator
    {
        $idSuffix = uniqid();
        yield ['TYPO3\Blog\Domain\Repository', 'C' . $idSuffix . 'BlogRepository', 'TYPO3\Blog\Domain\Model\\' . 'C' . $idSuffix . 'Blog'];
        yield ['Domain\Repository\Content', 'C' . $idSuffix . 'PageRepository', 'Domain\Model\\Content\\' . 'C' . $idSuffix . 'Page'];
        yield ['Domain\Repository', 'C' . $idSuffix . 'RepositoryRepository', 'Domain\Model\\' . 'C' . $idSuffix . 'Repository'];
    }

    #[DataProvider('modelAndRepositoryClassNames')]
    #[Test]
    public function constructSetsObjectTypeFromClassName($repositoryNamespace, $repositoryClassName, $modelClassName)
    {
        $mockClassName = $repositoryNamespace . '\\' . $repositoryClassName;
        eval('namespace ' . $repositoryNamespace . '; class ' . $repositoryClassName . ' extends \Neos\Flow\Persistence\Doctrine\Repository {}');

        /** @var Repository $repository */
        $repository = new $mockClassName($this->mockEntityManager);
        self::assertEquals($modelClassName, $repository->getEntityClassName());
    }
}
