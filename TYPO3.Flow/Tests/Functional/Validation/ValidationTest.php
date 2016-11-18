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
use TYPO3\Flow\Persistence\Doctrine\PersistenceManager;
use TYPO3\Flow\Tests\Functional\Persistence\Fixtures\SubEntity;
use TYPO3\Flow\Tests\Functional\Persistence\Fixtures\TestEntity;
use TYPO3\Flow\Tests\FunctionalTestCase;
use TYPO3\Flow\Tests\Functional\Persistence\Fixtures;

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
     * @var Fixtures\TestEntityRepository
     */
    protected $testEntityRepository;

    /**
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        if (!$this->persistenceManager instanceof PersistenceManager) {
            $this->markTestSkipped('Doctrine persistence is not enabled');
        }

        $this->testEntityRepository = $this->objectManager->get(Fixtures\TestEntityRepository::class);

        $this->registerRoute('post', 'test/validation/entity/{@action}', [
            '@package' => 'TYPO3.Flow',
            '@subpackage' => 'Tests\Functional\Mvc\Fixtures',
            '@controller' => 'Entity',
            '@format' =>'html'
        ]);
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
        $validArguments = ['entity' => ['__identity' => $entityIdentifier, 'name' => 'long enough name']];
        $response = $this->browser->request('http://localhost/test/validation/entity/update', 'POST', $validArguments);
        $this->assertSame('Entity "long enough name" updated', $response->getContent());

        $invalidArguments = ['entity' => ['__identity' => $entityIdentifier, 'name' => 'xx']];
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

        $invalidArguments = ['entity' => ['__identity' => $entityIdentifier, 'name' => 'long enough name', 'subEntities' => [['__identity' => $subEntityIdentifier, 'content' => '']]]];
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

        $invalidArguments = [
            'entity' => [
                '__identity' => $entityIdentifier,
                'name' => 'xx',
                'subEntities' => [[
                    '__identity' => $subEntityIdentifier,
                    'content' => 'some valid content'
                ]]
            ]
        ];
        $response = $this->browser->request('http://localhost/test/validation/entity/update', 'POST', $invalidArguments);
        $this->assertSame('An error occurred while trying to call TYPO3\Flow\Tests\Functional\Mvc\Fixtures\Controller\EntityController->updateAction().' . PHP_EOL . 'Error for entity.name:  This field must contain at least 3 characters.' . PHP_EOL, $response->getContent());
    }
}
