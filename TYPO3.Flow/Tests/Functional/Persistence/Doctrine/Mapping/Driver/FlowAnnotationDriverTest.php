<?php
namespace TYPO3\FLOW3\Tests\Functional\Persistence\Doctrine\Mapping\Driver;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Testcase for ORM annotation driver
 */
class Flow3AnnotationDriverTest extends \TYPO3\FLOW3\Tests\FunctionalTestCase {

	/**
	 * @return void
	 */
	public function setUp() {
		parent::setUp();
		if (!$this->persistenceManager instanceof \TYPO3\FLOW3\Persistence\Doctrine\PersistenceManager) {
			$this->markTestSkipped('Doctrine persistence is not enabled');
		}
	}

	/**
	 * @test
	 */
	public function lifecycleEventAnnotationsAreDetected() {
		$classMetadata = new \TYPO3\FLOW3\Persistence\Doctrine\Mapping\ClassMetadata('TYPO3\FLOW3\Tests\Functional\Persistence\Fixtures\Post');
		$driver = $this->objectManager->get('TYPO3\FLOW3\Persistence\Doctrine\Mapping\Driver\Flow3AnnotationDriver');
		$driver->loadMetadataForClass('TYPO3\FLOW3\Tests\Functional\Persistence\Fixtures\Post', $classMetadata);
		$this->assertTrue($classMetadata->hasLifecycleCallbacks('prePersist'));
	}

	/**
	 * @test
	 */
	public function lifecycleEventAnnotationsAreDetectedWithoutHasLifecycleCallbacks() {
		$classMetadata = new \TYPO3\FLOW3\Persistence\Doctrine\Mapping\ClassMetadata('TYPO3\FLOW3\Tests\Functional\Persistence\Fixtures\Comment');
		$driver = $this->objectManager->get('TYPO3\FLOW3\Persistence\Doctrine\Mapping\Driver\Flow3AnnotationDriver');
		$driver->loadMetadataForClass('TYPO3\FLOW3\Tests\Functional\Persistence\Fixtures\Comment', $classMetadata);
		$this->assertTrue($classMetadata->hasLifecycleCallbacks('prePersist'));
	}

	/**
	 * Makes sure that
	 * - thumbnail and image (same type) do get distinct column names
	 * - simple properties get mapped to their name
	 * - using joincolumn without name on single associations uses the property name
	 *
	 * @test
	 */
	public function columnNamesAreBuiltCorrectly() {
		$expectedTitleMapping = array(
			'fieldName' => 'title',
			'columnName' => 'title',
			'targetEntity' => 'string',
			'nullable' => FALSE,
			'type' => 'string',
		);

		$expectedImageAssociationMapping = array(
			'fieldName' => 'image',
			'columnName' => 'image',
			'joinColumns' => array (
				0 => array (
					'name' => 'image',
					'referencedColumnName' => 'flow3_persistence_identifier',
					'unique' => TRUE,
				),
			),
			'joinColumnFieldNames' => array(
				'image' => 'image',
			),
		);

		$expectedCommentAssociationMapping = array(
			'fieldName' => 'comment',
			'columnName' => 'comment',
			'joinColumns' => array(0 => array (
					'name' => 'comment',
					'referencedColumnName' => 'flow3_persistence_identifier',
					'unique' => TRUE,
					'nullable' => TRUE,
					'onDelete' => 'SET NULL',
					'columnDefinition' => NULL,
				),
			),
			'sourceEntity' => 'TYPO3\\FLOW3\\Tests\\Functional\\Persistence\\Fixtures\\Post',
			'sourceToTargetKeyColumns' => array (
				'comment' => 'flow3_persistence_identifier',
			),
			'joinColumnFieldNames' => array (
				'comment' => 'comment',
			),
			'targetToSourceKeyColumns' => array (
				'flow3_persistence_identifier' => 'comment',
			),
		);

		$classMetadata = new \TYPO3\FLOW3\Persistence\Doctrine\Mapping\ClassMetadata('TYPO3\FLOW3\Tests\Functional\Persistence\Fixtures\Post');
		$driver = $this->objectManager->get('TYPO3\FLOW3\Persistence\Doctrine\Mapping\Driver\Flow3AnnotationDriver');
		$driver->loadMetadataForClass('TYPO3\FLOW3\Tests\Functional\Persistence\Fixtures\Post', $classMetadata);

		$this->assertEquals($expectedTitleMapping, $classMetadata->getFieldMapping('title'), 'mapping for "title" not as expected');
		$imageAssociationMapping = $classMetadata->getAssociationMapping('image');
		$thumbnailAssociationMapping = $classMetadata->getAssociationMapping('thumbnail');
		foreach (array_keys($expectedImageAssociationMapping) as $key) {
			$this->assertEquals($expectedImageAssociationMapping[$key], $imageAssociationMapping[$key], 'mapping for "image" not as expected');
			$this->assertNotEquals($expectedImageAssociationMapping[$key], $thumbnailAssociationMapping[$key], 'mapping for "thumbnail" not as expected');
		}

		$commentAssociationMapping = $classMetadata->getAssociationMapping('comment');
		$this->assertEquals(1, count($commentAssociationMapping['joinColumns']));
		foreach (array_keys($expectedCommentAssociationMapping) as $key) {
			$this->assertEquals($expectedCommentAssociationMapping[$key], $commentAssociationMapping[$key], 'mapping for "comment" not as expected');
		}
	}

	/**
	 * The "related_post_id" column given manually must be kept.
	 *
	 * @test
	 */
	public function joinColumnAnnotationsAreObserved() {
		$expectedRelatedAssociationMapping = array(
			'fieldName' => 'related',
			'columnName' => 'related',
			'joinTable' => array(
				'name' => 'typo3_flow3_tests_functional_persistence_fix_3ebc7_related_join',
				'schema' => NULL,
				'joinColumns' => array(
					0 => array(
						'name' => 'flow3_fixtures_post',
						'referencedColumnName' => 'flow3_persistence_identifier',
					),
				),
				'inverseJoinColumns' => array(
					0 => array(
						'name' => 'related_post_id',
						'referencedColumnName' => 'flow3_persistence_identifier',
						'unique' => FALSE,
						'nullable' => TRUE,
						'onDelete' => NULL,
						'columnDefinition' => NULL,
					),
				),
			),
			'relationToSourceKeyColumns' => array(
				'flow3_fixtures_post' => 'flow3_persistence_identifier',
			),
			'joinTableColumns' => array(
				0 => 'flow3_fixtures_post',
				1 => 'related_post_id',
			),
			'relationToTargetKeyColumns' => array(
				'related_post_id' => 'flow3_persistence_identifier',
			),
		);
		$classMetadata = new \TYPO3\FLOW3\Persistence\Doctrine\Mapping\ClassMetadata('TYPO3\FLOW3\Tests\Functional\Persistence\Fixtures\Post');
		$driver = $this->objectManager->get('TYPO3\FLOW3\Persistence\Doctrine\Mapping\Driver\Flow3AnnotationDriver');
		$driver->loadMetadataForClass('TYPO3\FLOW3\Tests\Functional\Persistence\Fixtures\Post', $classMetadata);

		$relatedAssociationMapping = $classMetadata->getAssociationMapping('related');
		foreach (array_keys($expectedRelatedAssociationMapping) as $key) {
			$this->assertEquals($expectedRelatedAssociationMapping[$key], $relatedAssociationMapping[$key]);
		}
	}

}

?>