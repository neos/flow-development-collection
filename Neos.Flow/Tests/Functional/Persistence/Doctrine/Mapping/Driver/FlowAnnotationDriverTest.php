<?php
namespace Neos\Flow\Tests\Functional\Persistence\Doctrine\Mapping\Driver;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Doctrine\ORM\Tools\SchemaTool;
use Neos\Flow\Persistence\Doctrine\Mapping\Driver\FlowAnnotationDriver;
use Neos\Flow\Persistence\Doctrine\PersistenceManager;
use Neos\Flow\Tests\Functional\Aop\Fixtures\TargetClass04;
use Neos\Flow\Tests\Functional\Persistence\Fixtures;
use Neos\Flow\Persistence\Doctrine\Mapping\ClassMetadata;
use Neos\Flow\Tests\FunctionalTestCase;

/**
 * Testcase for ORM annotation driver
 */
class FlowAnnotationDriverTest extends FunctionalTestCase
{
    protected static $testablePersistenceEnabled = true;

    /**
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        if (!$this->persistenceManager instanceof PersistenceManager) {
            $this->markTestSkipped('Doctrine persistence is not enabled');
        }
    }

    /**
     * @test
     */
    public function lifecycleEventAnnotationsAreDetected()
    {
        $classMetadata = new ClassMetadata(Fixtures\Post::class);
        $driver = $this->objectManager->get(FlowAnnotationDriver::class);
        $driver->loadMetadataForClass(Fixtures\Post::class, $classMetadata);
        $this->assertTrue($classMetadata->hasLifecycleCallbacks('prePersist'));
    }

    /**
     * @test
     */
    public function lifecycleEventAnnotationsAreDetectedWithoutHasLifecycleCallbacks()
    {
        $classMetadata = new ClassMetadata(Fixtures\Comment::class);
        $driver = $this->objectManager->get(FlowAnnotationDriver::class);
        $driver->loadMetadataForClass(Fixtures\Comment::class, $classMetadata);
        $this->assertTrue($classMetadata->hasLifecycleCallbacks('prePersist'));
    }

    /**
     * @test
     */
    public function lifecycleCallbacksAreNotRegisteredForUnproxiedEntities()
    {
        $classMetadata = new ClassMetadata(Fixtures\UnproxiedTestEntity::class);
        $driver = $this->objectManager->get(FlowAnnotationDriver::class);
        $driver->loadMetadataForClass(Fixtures\UnproxiedTestEntity::class, $classMetadata);
        $this->assertFalse($classMetadata->hasLifecycleCallbacks(\Doctrine\ORM\Events::postLoad));
    }

    /**
     * @test
     */
    public function inheritanceTypeIsNotChangedIfNoSubclassesOfNonAbstractClassExist()
    {
        $classMetadata = new ClassMetadata(Fixtures\Post::class);
        $driver = $this->objectManager->get(FlowAnnotationDriver::class);
        $driver->loadMetadataForClass(Fixtures\Post::class, $classMetadata);
        $this->assertSame(\Doctrine\ORM\Mapping\ClassMetadata::INHERITANCE_TYPE_JOINED, $classMetadata->inheritanceType);
    }

    /**
     * @test
     */
    public function inheritanceTypeIsSetToNoneIfNoSubclassesOfAbstractClassExist()
    {
        $classMetadata = new ClassMetadata(Fixtures\AbstractEntity::class);
        $driver = $this->objectManager->get(FlowAnnotationDriver::class);
        $driver->loadMetadataForClass(Fixtures\AbstractEntity::class, $classMetadata);
        $this->assertSame(\Doctrine\ORM\Mapping\ClassMetadata::INHERITANCE_TYPE_NONE, $classMetadata->inheritanceType);
    }

    /**
     * @test
     */
    public function compositePrimaryKeyOverEntityRelationIsRegistered()
    {
        $classMetadata = new ClassMetadata(Fixtures\CompositeKeyTestEntity::class);
        $driver = $this->objectManager->get(FlowAnnotationDriver::class);
        $driver->loadMetadataForClass(Fixtures\CompositeKeyTestEntity::class, $classMetadata);
        $this->assertTrue($classMetadata->isIdentifierComposite);
        $this->assertTrue($classMetadata->containsForeignIdentifier);
        $this->assertEquals($classMetadata->identifier, ['name', 'relatedEntity']);
    }

    /**
     * Makes sure that
     * - thumbnail and image (same type) do get distinct column names
     * - simple properties get mapped to their name
     * - using joincolumn without name on single associations uses the property name
     *
     * @test
     */
    public function columnNamesAreBuiltCorrectly()
    {
        $expectedTitleMapping = [
            'fieldName' => 'title',
            'columnName' => 'title',
            'targetEntity' => 'string',
            'nullable' => false,
            'type' => 'string',
        ];

        $expectedImageAssociationMapping = [
            'fieldName' => 'image',
            'columnName' => 'image',
            'joinColumns' => [
                0 => [
                    'name' => 'image',
                    'referencedColumnName' => 'persistence_object_identifier',
                    'unique' => true,
                ],
            ],
            'joinColumnFieldNames' => [
                'image' => 'image',
            ],
        ];

        $expectedCommentAssociationMapping = [
            'fieldName' => 'comment',
            'columnName' => 'comment',
            'joinColumns' => [0 => [
                    'name' => 'comment',
                    'referencedColumnName' => 'persistence_object_identifier',
                    'unique' => true,
                    'nullable' => true,
                    'onDelete' => 'SET NULL',
                    'columnDefinition' => null,
            ],
            ],
            'sourceEntity' => Fixtures\Post::class,
            'sourceToTargetKeyColumns' => [
                'comment' => 'persistence_object_identifier',
            ],
            'joinColumnFieldNames' => [
                'comment' => 'comment',
            ],
            'targetToSourceKeyColumns' => [
                'persistence_object_identifier' => 'comment',
            ],
        ];

        $classMetadata = new ClassMetadata(Fixtures\Post::class);
        $driver = $this->objectManager->get(FlowAnnotationDriver::class);
        $driver->loadMetadataForClass(Fixtures\Post::class, $classMetadata);

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
    public function joinColumnAnnotationsAreObserved()
    {
        $expectedRelatedAssociationMapping = [
            'fieldName' => 'related',
            'columnName' => 'related',
            'joinTable' => [
                'name' => 'neos_flow_tests_functional_persistence_fixtu_3a05f_related_join',
                'schema' => null,
                'joinColumns' => [
                    0 => [
                        'name' => 'flow_fixtures_post',
                        'referencedColumnName' => 'persistence_object_identifier',
                    ],
                ],
                'inverseJoinColumns' => [
                    0 => [
                        'name' => 'related_post_id',
                        'referencedColumnName' => 'persistence_object_identifier',
                        'unique' => false,
                        'nullable' => true,
                        'onDelete' => null,
                        'columnDefinition' => null,
                    ],
                ],
            ],
            'relationToSourceKeyColumns' => [
                'flow_fixtures_post' => 'persistence_object_identifier',
            ],
            'joinTableColumns' => [
                0 => 'flow_fixtures_post',
                1 => 'related_post_id',
            ],
            'relationToTargetKeyColumns' => [
                'related_post_id' => 'persistence_object_identifier',
            ],
        ];
        $classMetadata = new ClassMetadata(Fixtures\Post::class);
        $driver = $this->objectManager->get(FlowAnnotationDriver::class);
        $driver->loadMetadataForClass(Fixtures\Post::class, $classMetadata);

        $relatedAssociationMapping = $classMetadata->getAssociationMapping('related');
        foreach (array_keys($expectedRelatedAssociationMapping) as $key) {
            $this->assertEquals($expectedRelatedAssociationMapping[$key], $relatedAssociationMapping[$key]);
        }
    }

    /**
     * The "indexBy" annotation of EntityWithIndexedRelation must be kept
     *
     * @test
     */
    public function doctrineIndexByAnnotationIsObserved()
    {
        $classMetadata = new ClassMetadata(Fixtures\EntityWithIndexedRelation::class);
        $driver = $this->objectManager->get(FlowAnnotationDriver::class);
        $driver->loadMetadataForClass(Fixtures\EntityWithIndexedRelation::class, $classMetadata);

        /* The annotation should be available at ManyToMany relations */
        $relatedAssociationMapping = $classMetadata->getAssociationMapping('annotatedIdentitiesEntities');
        $this->assertArrayHasKey('indexBy', $relatedAssociationMapping);
        $this->assertEquals('author', $relatedAssociationMapping['indexBy']);

        /* The annotation should be available at OneToMany relations */
        $relatedAssociationMapping = $classMetadata->getAssociationMapping('relatedIndexEntities');
        $this->assertArrayHasKey('indexBy', $relatedAssociationMapping);
        $this->assertEquals('sorting', $relatedAssociationMapping['indexBy']);
    }

    /**
     * @test
     */
    public function introducedPropertiesAreObservedCorrectly()
    {
        $classMetadata = new ClassMetadata(TargetClass04::class);
        $driver = $this->objectManager->get(FlowAnnotationDriver::class);
        $driver->loadMetadataForClass(TargetClass04::class, $classMetadata);

        $fieldNames = $classMetadata->getFieldNames();
        $this->assertContains('introducedProtectedProperty', $fieldNames);
        $this->assertContains('introducedPublicProperty', $fieldNames);
        $this->assertNotContains('introducedTransientProperty', $fieldNames);
    }

    /**
     * @test
     */
    public function oneToOneRelationsAreMappedCorrectly()
    {
        $classMetadata = new ClassMetadata(Fixtures\OneToOneEntity::class);
        $driver = $this->objectManager->get(FlowAnnotationDriver::class);
        $driver->loadMetadataForClass(Fixtures\OneToOneEntity::class, $classMetadata);

        $selfReferencingMapping = $classMetadata->getAssociationMapping('selfReferencing');
        $this->assertNotEmpty($selfReferencingMapping['joinColumns']);
        $this->assertTrue($selfReferencingMapping['isOwningSide']);

        $bidirectionalMapping = $classMetadata->getAssociationMapping('bidirectionalRelation');
        $this->assertNotEmpty($bidirectionalMapping['joinColumns']);
        $this->assertEquals('bidirectionalRelation', $bidirectionalMapping['inversedBy']);
        $this->assertTrue($bidirectionalMapping['isOwningSide']);

        $classMetadata2 = new ClassMetadata(Fixtures\OneToOneEntity2::class);
        $driver->loadMetadataForClass(Fixtures\OneToOneEntity2::class, $classMetadata2);
        $bidirectionalMapping2 = $classMetadata2->getAssociationMapping('bidirectionalRelation');
        $this->assertFalse(isset($bidirectionalMapping2['joinColumns']));
        $this->assertEquals('bidirectionalRelation', $bidirectionalMapping2['mappedBy']);
        $this->assertFalse($bidirectionalMapping2['isOwningSide']);

        $unidirectionalMapping = $classMetadata->getAssociationMapping('unidirectionalRelation');
        $this->assertNotEmpty($unidirectionalMapping['joinColumns']);
        $this->assertTrue($unidirectionalMapping['isOwningSide']);

        /* @var $entityManager \Doctrine\Common\Persistence\ObjectManager */
        $entityManager = $this->objectManager->get(\Doctrine\Common\Persistence\ObjectManager::class);
        $schemaTool = new SchemaTool($entityManager);
        $schema = $schemaTool->getSchemaFromMetadata([$entityManager->getClassMetadata(Fixtures\OneToOneEntity2::class)]);
        /* @var $foreignKey \Doctrine\DBAL\Schema\ForeignKeyConstraint */
        foreach ($schema->getTable('persistence_onetooneentity2')->getForeignKeys() as $foreignKey) {
            if ($foreignKey->getForeignTableName() === 'persistence_onetooneentity') {
                $this->assertTrue(false);
            }
        }
    }
}
