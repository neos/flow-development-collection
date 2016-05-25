<?php
namespace TYPO3\Flow\Tests\Functional\Validation;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */
use TYPO3\Flow\Tests\Functional\Persistence\Fixtures\SubEntity;
use TYPO3\Flow\Tests\Functional\Persistence\Fixtures\TestEntity;
use TYPO3\Flow\Tests\FunctionalTestCase;

/**
 * Testcase for the Flow Validation Framework
 *
 */
class ValidationTest extends FunctionalTestCase
{
    /**
     * @var boolean
     */
    protected static $testablePersistenceEnabled = true;

    /**
     * @var \TYPO3\Flow\Tests\Functional\Persistence\Fixtures\TestEntityRepository
     */
    protected $testEntityRepository;

    /**
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        if (!$this->persistenceManager instanceof \TYPO3\Flow\Persistence\Doctrine\PersistenceManager) {
            $this->markTestSkipped('Doctrine persistence is not enabled');
        }

        $this->testEntityRepository = $this->objectManager->get(\TYPO3\Flow\Tests\Functional\Persistence\Fixtures\TestEntityRepository::class);

        $this->registerRoute('post', 'test/validation/entity/{@action}', array(
            '@package' => 'TYPO3.Flow',
            '@subpackage' => 'Tests\Functional\Mvc\Fixtures',
            '@controller' => 'Entity',
            '@format' => 'html'
        ));
    }

    /**
     * The ValidationResolver has a 1st level cache. This test ensures that this cache is flushed between two requests.
     *
     * @test
     */
    public function validationIsEnforcedOnSuccessiveRequests()
    {
        $entity = new TestEntity();
        $entity->setName('Some Name');
        $this->testEntityRepository->add($entity);
        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();

        $entityIdentifier = $this->persistenceManager->getIdentifierByObject($entity);
        $validArguments = array('entity' => array('__identity' => $entityIdentifier, 'name' => 'long enough name'));
        $response = $this->browser->request('http://localhost/test/validation/entity/update', 'POST', $validArguments);
        $this->assertSame('Entity "long enough name" updated', $response->getContent());

        $invalidArguments = array('entity' => array('__identity' => $entityIdentifier, 'name' => 'xx'));
        $response = $this->browser->request('http://localhost/test/validation/entity/update', 'POST', $invalidArguments);
        $this->assertSame('An error occurred while trying to call TYPO3\Flow\Tests\Functional\Mvc\Fixtures\Controller\EntityController->updateAction().' . PHP_EOL . 'Error for entity.name:  This field must contain at least 3 characters.' . PHP_EOL, $response->getContent());
    }

    /**
     * @test
     */
    public function validationIsEnforcedForChildObjects()
    {
        $entity = new TestEntity();
        $entity->setName('Some Name');
        $this->testEntityRepository->add($entity);

        $subEntity = new SubEntity();
        $subEntity->setContent('Sub Entity');
        $entity->addSubEntity($subEntity);
        $this->persistenceManager->add($subEntity);

        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();

        $entityIdentifier = $this->persistenceManager->getIdentifierByObject($entity);
        $subEntityIdentifier = $this->persistenceManager->getIdentifierByObject($subEntity);

        $invalidArguments = array('entity' => array('__identity' => $entityIdentifier, 'name' => 'long enough name', 'subEntities' => array(array('__identity' => $subEntityIdentifier, 'content' => ''))));
        $response = $this->browser->request('http://localhost/test/validation/entity/update', 'POST', $invalidArguments);
        $this->assertSame('An error occurred while trying to call TYPO3\Flow\Tests\Functional\Mvc\Fixtures\Controller\EntityController->updateAction().' . PHP_EOL . 'Error for entity.subEntities.0.content:  This property is required.' . PHP_EOL, $response->getContent());
    }

    /**
     * @test
     */
    public function validationIsEnforcedForParentObject()
    {
        $entity = new TestEntity();
        $entity->setName('Some Name');
        $this->testEntityRepository->add($entity);

        $subEntity = new SubEntity();
        $subEntity->setContent('Sub Entity');
        $subEntity->setParentEntity($entity);
        $entity->addSubEntity($subEntity);
        $this->persistenceManager->add($subEntity);

        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();

        $entityIdentifier = $this->persistenceManager->getIdentifierByObject($entity);
        $subEntityIdentifier = $this->persistenceManager->getIdentifierByObject($subEntity);

        $invalidArguments = array(
            'entity' => array(
                '__identity' => $entityIdentifier,
                'name' => 'xx',
                'subEntities' => array(array(
                    '__identity' => $subEntityIdentifier,
                    'content' => 'some valid content'
                ))
            )
        );
        $response = $this->browser->request('http://localhost/test/validation/entity/update', 'POST', $invalidArguments);
        $this->assertSame('An error occurred while trying to call TYPO3\Flow\Tests\Functional\Mvc\Fixtures\Controller\EntityController->updateAction().' . PHP_EOL . 'Error for entity.name:  This field must contain at least 3 characters.' . PHP_EOL, $response->getContent());
    }

    /**
     * @test
     */
    public function validationIsNotExecutedForUnchangedObject()
    {
        $entity = new TestEntity();
        $entity->setValidatedProperty('Some value');
        $this->testEntityRepository->add($entity);

        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();
        $this->objectManager->forgetInstance('TYPO3\Flow\Tests\Functional\Validation\Fixtures\SpyValidator');

        $entityIdentifier = $this->persistenceManager->getIdentifierByObject($entity);

        $arguments = array(
            'entity' => array(
                '__identity' => $entityIdentifier,
            )
        );

        $response = $this->browser->request('http://localhost/test/validation/entity/validate', 'POST', $arguments);
        $this->assertSame('SpyValidator was executed 0 times.', $response->getContent());
    }

    /**
     * @test
     */
    public function validationIsExecutedForChangedObject()
    {
        $entity = new TestEntity();
        $entity->setValidatedProperty('Some value');
        $this->testEntityRepository->add($entity);

        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();
        $this->objectManager->forgetInstance('TYPO3\Flow\Tests\Functional\Validation\Fixtures\SpyValidator');

        $entityIdentifier = $this->persistenceManager->getIdentifierByObject($entity);

        $arguments = array(
            'entity' => array(
                '__identity' => $entityIdentifier,
                'validatedProperty' => 'Changed value',
            )
        );

        $response = $this->browser->request('http://localhost/test/validation/entity/validate', 'POST', $arguments);
        $this->assertSame('SpyValidator was executed 1 times.', $response->getContent());
    }

    /**
     * @test
     */
    public function validationIsNotExecutedForUnchangedSubObject()
    {
        $entity = new TestEntity();
        $entity->setValidatedProperty('Some value');
        $this->testEntityRepository->add($entity);

        $subEntity = new SubEntity();
        $subEntity->setContent('Sub Entity');
        $subEntity->setValidatedProperty('Some value');
        $subEntity->setParentEntity($entity);
        $entity->addSubEntity($subEntity);
        $this->persistenceManager->add($subEntity);

        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();
        $this->objectManager->forgetInstance('TYPO3\Flow\Tests\Functional\Validation\Fixtures\SpyValidator');

        $entityIdentifier = $this->persistenceManager->getIdentifierByObject($entity);
        $subEntityIdentifier = $this->persistenceManager->getIdentifierByObject($subEntity);

        $arguments = array(
            'entity' => array(
                '__identity' => $entityIdentifier,
                'validatedProperty' => 'Changed value',
                'subEntities' => array(array(
                    '__identity' => $subEntityIdentifier,
                ))
            )
        );

        $response = $this->browser->request('http://localhost/test/validation/entity/validate', 'POST', $arguments);
        $this->assertSame('SpyValidator was executed 1 times.', $response->getContent());
    }

    /**
     * @test
     */
    public function validationIsOnlyExecutedForChangedSubObject()
    {
        $entity = new TestEntity();
        $entity->setValidatedProperty('Some value');
        $this->testEntityRepository->add($entity);

        $subEntity = new SubEntity();
        $subEntity->setContent('Sub Entity');
        $subEntity->setValidatedProperty('Some value');
        $subEntity->setParentEntity($entity);
        $entity->addSubEntity($subEntity);
        $this->persistenceManager->add($subEntity);

        $subEntity2 = new SubEntity();
        $subEntity2->setContent('Sub Entity 2');
        $subEntity2->setValidatedProperty('Some value');
        $subEntity2->setParentEntity($entity);
        $entity->addSubEntity($subEntity2);
        $this->persistenceManager->add($subEntity2);

        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();
        $this->objectManager->forgetInstance('TYPO3\Flow\Tests\Functional\Validation\Fixtures\SpyValidator');

        $entityIdentifier = $this->persistenceManager->getIdentifierByObject($entity);
        $subEntityIdentifier = $this->persistenceManager->getIdentifierByObject($subEntity);
        $subEntity2Identifier = $this->persistenceManager->getIdentifierByObject($subEntity2);

        $arguments = array(
            'entity' => array(
                '__identity' => $entityIdentifier,
                'subEntities' => array(array(
                    '__identity' => $subEntityIdentifier,
                ), array(
                    '__identity' => $subEntity2Identifier,
                    'validatedProperty' => 'Changed value',
                ))
            )
        );

        $response = $this->browser->request('http://localhost/test/validation/entity/validate', 'POST', $arguments);
        $this->assertSame('SpyValidator was executed 1 times.', $response->getContent());
    }


    /**
     * @test
     */
    public function persistenceValidationIsExecutedForChangedObject()
    {
        $entity = new TestEntity();
        $entity->setValidatedProperty('Some value');
        $this->testEntityRepository->add($entity);

        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();
        $this->objectManager->forgetInstance('TYPO3\Flow\Tests\Functional\Validation\Fixtures\SpyValidator');

        $entityIdentifier = $this->persistenceManager->getIdentifierByObject($entity);

        $arguments = array(
            'entity' => array(
                '__identity' => $entityIdentifier,
            )
        );

        $response = $this->browser->request('http://localhost/test/validation/entity/validatePersistence', 'POST', $arguments);
        $this->assertSame('SpyValidator was executed 2 times.', $response->getContent());
    }

    /**
     * @test
     */
    public function persistenceValidationIsOnlyExecutedForChangedSubObject()
    {
        $entity = new TestEntity();
        $entity->setValidatedProperty('Some value');
        $this->testEntityRepository->add($entity);

        $subEntity = new SubEntity();
        $subEntity->setContent('Sub Entity');
        $subEntity->setValidatedProperty('Some value');
        $subEntity->setParentEntity($entity);
        $entity->addSubEntity($subEntity);
        $this->persistenceManager->add($subEntity);

        $subEntity2 = new SubEntity();
        $subEntity2->setContent('Sub Entity 2');
        $subEntity2->setValidatedProperty('Some value');
        $subEntity2->setParentEntity($entity);
        $entity->addSubEntity($subEntity2);
        $this->persistenceManager->add($subEntity2);

        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();
        $this->objectManager->forgetInstance('TYPO3\Flow\Tests\Functional\Validation\Fixtures\SpyValidator');

        $entityIdentifier = $this->persistenceManager->getIdentifierByObject($entity);
        $subEntityIdentifier = $this->persistenceManager->getIdentifierByObject($subEntity);
        $subEntity2Identifier = $this->persistenceManager->getIdentifierByObject($subEntity2);

        $arguments = array(
            'entity' => array(
                '__identity' => $entityIdentifier,
                'subEntities' => array(array(
                    '__identity' => $subEntityIdentifier,
                ), array(
                    '__identity' => $subEntity2Identifier,
                    'validatedProperty' => 'Changed value',
                ))
            )
        );

        $response = $this->browser->request('http://localhost/test/validation/entity/validatePersistence', 'POST', $arguments);
        $this->assertSame('SpyValidator was executed 1 times.', $response->getContent());
    }
}
