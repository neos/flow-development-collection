<?php
namespace TYPO3\Flow\Tests\Unit\Persistence\Doctrine;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use TYPO3\Flow\Persistence\Doctrine\Repository;
use TYPO3\Flow\Tests\UnitTestCase;

/**
 * Testcase for the doctrine Repository
 */
class RepositoryTest extends UnitTestCase {

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
	public function setUp() {
		$this->mockEntityManager = $this->getMockBuilder(ObjectManager::class)->disableOriginalConstructor()->getMock();

		$this->mockClassMetadata = $this->getMockBuilder(ClassMetadata::class)->disableOriginalConstructor()->getMock();
		$this->mockEntityManager->expects($this->any())->method('getClassMetadata')->will($this->returnValue($this->mockClassMetadata));
	}

	/**
	 * dataProvider for constructSetsObjectTypeFromClassName
	 */
	public function modelAndRepositoryClassNames() {
		$idSuffix = uniqid();
		return array(
			array('TYPO3\Blog\Domain\Repository', 'C' . $idSuffix . 'BlogRepository', 'TYPO3\Blog\Domain\Model\\' . 'C' . $idSuffix . 'Blog'),
			array('Domain\Repository\Content', 'C' . $idSuffix . 'PageRepository', 'Domain\Model\\Content\\' . 'C' . $idSuffix . 'Page'),
			array('Domain\Repository', 'C' . $idSuffix . 'RepositoryRepository', 'Domain\Model\\' . 'C' . $idSuffix . 'Repository')
		);
	}

	/**
	 * @test
	 * @dataProvider modelAndRepositoryClassNames
	 */
	public function constructSetsObjectTypeFromClassName($repositoryNamespace, $repositoryClassName, $modelClassName) {
		$mockClassName = $repositoryNamespace . '\\' . $repositoryClassName;
		eval('namespace ' . $repositoryNamespace . '; class ' . $repositoryClassName . ' extends \TYPO3\Flow\Persistence\Doctrine\Repository {}');

		/** @var Repository $repository */
		$repository = new $mockClassName($this->mockEntityManager);
		$this->assertEquals($modelClassName, $repository->getEntityClassName());
	}

}
