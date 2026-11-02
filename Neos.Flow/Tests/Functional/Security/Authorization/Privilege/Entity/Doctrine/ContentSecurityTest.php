<?php

declare(strict_types=1);

namespace Neos\Flow\Tests\Functional\Security\Authorization\Privilege\Entity\Doctrine;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */
use Doctrine\Common\Collections\ArrayCollection;
use Neos\Flow\Cache\CacheManager;
use Neos\Flow\Persistence\Doctrine\PersistenceManager;
use Neos\Flow\Security;
use Neos\Flow\Security\Account;
use Neos\Flow\Tests\Functional\Aop\Fixtures\TestContext;
use Neos\Flow\Tests\Functional\Security\Fixtures;
use Neos\Flow\Tests\Functional\Security\Fixtures\RestrictableEntity;
use Neos\Flow\Tests\Functional\Security\Fixtures\RestrictableEntityDoctrineRepository;
use Neos\Flow\Tests\Functional\Security\Fixtures\TestEntityA;
use Neos\Flow\Tests\Functional\Security\Fixtures\TestEntityADoctrineRepository;
use Neos\Flow\Tests\Functional\Security\Fixtures\TestEntityB;
use Neos\Flow\Tests\Functional\Security\Fixtures\TestEntityC;
use Neos\Flow\Tests\Functional\Security\Fixtures\TestEntityCDoctrineRepository;
use Neos\Flow\Tests\Functional\Security\Fixtures\TestEntityD;
use Neos\Flow\Tests\Functional\Security\Fixtures\TestEntityDDoctrineRepository;
use Neos\Flow\Tests\FunctionalTestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Testcase for content security using doctrine persistence
 *
 */
final class ContentSecurityTest extends FunctionalTestCase
{
    /**
     * @var boolean
     */
    protected $testableSecurityEnabled = true;

    /**
     * @var boolean
     */
    protected static $testablePersistenceEnabled = true;

    /**
     * @var Fixtures\RestrictableEntityDoctrineRepository
     */
    protected $restrictableEntityDoctrineRepository;

    /**
     * @var Fixtures\TestEntityADoctrineRepository
     */
    protected $testEntityADoctrineRepository;

    /**
     * @var Fixtures\TestEntityCDoctrineRepository
     */
    protected $testEntityCDoctrineRepository;

    /**
     * @var Fixtures\TestEntityDDoctrineRepository
     */
    protected $testEntityDDoctrineRepository;

    /**
     * @var TestContext
     */
    protected $globalObjectTestContext;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        if (!$this->persistenceManager instanceof PersistenceManager) {
            $this->markTestSkipped('Doctrine persistence is not enabled');
        }
        $this->restrictableEntityDoctrineRepository = $this->objectManager->get(RestrictableEntityDoctrineRepository::class);
        $this->testEntityADoctrineRepository = $this->objectManager->get(TestEntityADoctrineRepository::class);
        $this->testEntityCDoctrineRepository = $this->objectManager->get(TestEntityCDoctrineRepository::class);
        $this->testEntityDDoctrineRepository = $this->objectManager->get(TestEntityDDoctrineRepository::class);
        $this->globalObjectTestContext = $this->objectManager->get(TestContext::class);
    }

    #[Test]
    public function administratorsAreAllowedToSeeHiddenRestrictableEntities()
    {
        $this->authenticateRoles(['Neos.Flow:Administrator']);

        $defaultEntity = new RestrictableEntity('default');
        $hiddenEntity = new RestrictableEntity('hiddenEntity');
        $hiddenEntity->setHidden(true);

        $this->restrictableEntityDoctrineRepository->add($defaultEntity);
        $defaultEntityIdentifier = $this->persistenceManager->getIdentifierByObject($defaultEntity);
        $this->restrictableEntityDoctrineRepository->add($hiddenEntity);
        $hiddenEntityIdentifier = $this->persistenceManager->getIdentifierByObject($hiddenEntity);

        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();

        $result = $this->restrictableEntityDoctrineRepository->findAllWithDql();
        self::assertCount(2, $result);

        self::assertNotNull($this->persistenceManager->getObjectByIdentifier($defaultEntityIdentifier, RestrictableEntity::class));
        self::assertNotNull($this->persistenceManager->getObjectByIdentifier($hiddenEntityIdentifier, RestrictableEntity::class));

        $this->restrictableEntityDoctrineRepository->removeAll();
        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();
    }

    #[Test]
    public function customersAreNotAllowedToSeeHiddenRestrictableEntities()
    {
        $this->authenticateRoles(['Neos.Flow:Customer']);

        $defaultEntity = new RestrictableEntity('default');
        $hiddenEntity = new RestrictableEntity('hiddenEntity');
        $hiddenEntity->setHidden(true);

        $this->restrictableEntityDoctrineRepository->add($defaultEntity);
        $defaultEntityIdentifier = $this->persistenceManager->getIdentifierByObject($defaultEntity);
        $this->restrictableEntityDoctrineRepository->add($hiddenEntity);
        $hiddenEntityIdentifier = $this->persistenceManager->getIdentifierByObject($hiddenEntity);

        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();

        $result = $this->restrictableEntityDoctrineRepository->findAllWithDql();
        self::assertCount(1, $result);

        self::assertNotNull($this->persistenceManager->getObjectByIdentifier($defaultEntityIdentifier, RestrictableEntity::class));
        self::assertNull($this->persistenceManager->getObjectByIdentifier($hiddenEntityIdentifier, RestrictableEntity::class));

        $this->restrictableEntityDoctrineRepository->removeAll();
        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();
    }

    #[Test]
    public function customersAreNotAllowedToSeeDeletedRestrictableEntities()
    {
        $this->authenticateRoles(['Neos.Flow:Customer']);

        $defaultEntity = new RestrictableEntity('default');
        $deletedEntity = new RestrictableEntity('deletedEntry');
        $deletedEntity->delete();

        $this->restrictableEntityDoctrineRepository->add($defaultEntity);
        $defaultEntityIdentifier = $this->persistenceManager->getIdentifierByObject($defaultEntity);
        $this->restrictableEntityDoctrineRepository->add($deletedEntity);
        $deletedEntityIdentifier = $this->persistenceManager->getIdentifierByObject($deletedEntity);

        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();

        $result = $this->restrictableEntityDoctrineRepository->findAllWithDql();
        self::assertCount(1, $result);

        self::assertNotNull($this->persistenceManager->getObjectByIdentifier($defaultEntityIdentifier, RestrictableEntity::class));
        self::assertNull($this->persistenceManager->getObjectByIdentifier($deletedEntityIdentifier, RestrictableEntity::class));

        $this->restrictableEntityDoctrineRepository->removeAll();
        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();
    }

    #[Test]
    public function administratorsCanSeeDeletedRestrictableEntities()
    {
        $this->authenticateRoles(['Neos.Flow:Administrator']);

        $defaultEntity = new RestrictableEntity('default');
        $deletedEntity = new RestrictableEntity('hiddenEntity');
        $deletedEntity->delete();

        $this->restrictableEntityDoctrineRepository->add($defaultEntity);
        $defaultEntityIdentifier = $this->persistenceManager->getIdentifierByObject($defaultEntity);
        $this->restrictableEntityDoctrineRepository->add($deletedEntity);
        $deletedEntityIdentifier = $this->persistenceManager->getIdentifierByObject($deletedEntity);

        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();

        $result = $this->restrictableEntityDoctrineRepository->findAllWithDql();
        self::assertCount(2, $result);

        self::assertNotNull($this->persistenceManager->getObjectByIdentifier($defaultEntityIdentifier, RestrictableEntity::class));
        self::assertNotNull($this->persistenceManager->getObjectByIdentifier($deletedEntityIdentifier, RestrictableEntity::class));

        $this->restrictableEntityDoctrineRepository->removeAll();
        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();
    }

    #[Test]
    public function anonymousUsersAreNotAllowedToSeeRestrictableEntitiesAtAll()
    {
        $defaultEntity = new RestrictableEntity('default');
        $hiddenEntity = new RestrictableEntity('hiddenEntity');
        $hiddenEntity->setHidden(true);

        $this->restrictableEntityDoctrineRepository->add($defaultEntity);
        $defaultEntityIdentifier = $this->persistenceManager->getIdentifierByObject($defaultEntity);
        $this->restrictableEntityDoctrineRepository->add($hiddenEntity);
        $hiddenEntityIdentifier = $this->persistenceManager->getIdentifierByObject($hiddenEntity);

        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();

        $result = $this->restrictableEntityDoctrineRepository->findAllWithDql();
        self::assertCount(0, $result);

        self::assertNull($this->persistenceManager->getObjectByIdentifier($defaultEntityIdentifier, RestrictableEntity::class));
        self::assertNull($this->persistenceManager->getObjectByIdentifier($hiddenEntityIdentifier, RestrictableEntity::class));

        $this->restrictableEntityDoctrineRepository->removeAll();
        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();
    }

    #[Test]
    public function customersCannotSeeOthersRestrictableEntites()
    {
        $ownAccount = $this->authenticateRoles(['Neos.Flow:Customer']);
        $ownAccount->setAccountIdentifier('ownAccount');
        $ownAccount->setAuthenticationProviderName('SomeProvider');
        $otherAccount = new Account();
        $otherAccount->setAccountIdentifier('othersAccount');
        $otherAccount->setAuthenticationProviderName('SomeProvider');
        $this->persistenceManager->add($ownAccount);
        $this->persistenceManager->add($otherAccount);

        $ownEntity = new RestrictableEntity('ownEntity');
        $ownEntity->setOwnerAccount($ownAccount);
        $othersEntity = new RestrictableEntity('othersEntity');
        $othersEntity->setOwnerAccount($otherAccount);

        $this->restrictableEntityDoctrineRepository->add($ownEntity);
        $ownEntityIdentifier = $this->persistenceManager->getIdentifierByObject($ownEntity);
        $this->restrictableEntityDoctrineRepository->add($othersEntity);
        $othersEntityIdentifier = $this->persistenceManager->getIdentifierByObject($othersEntity);

        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();

        $result = $this->restrictableEntityDoctrineRepository->findAllWithDql();
        self::assertCount(1, $result);

        self::assertNotNull($this->persistenceManager->getObjectByIdentifier($ownEntityIdentifier, RestrictableEntity::class));
        self::assertNull($this->persistenceManager->getObjectByIdentifier($othersEntityIdentifier, RestrictableEntity::class));

        $this->restrictableEntityDoctrineRepository->removeAll();
        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();
    }

    #[Test]
    public function administratorsCanSeeOthersRestrictableEntites()
    {
        $ownAccount = $this->authenticateRoles(['Neos.Flow:Administrator', 'Neos.Flow:Customer']);
        $ownAccount->setAccountIdentifier('ownAccount');
        $ownAccount->setAuthenticationProviderName('SomeProvider');
        $otherAccount = new Account();
        $otherAccount->setAccountIdentifier('othersAccount');
        $otherAccount->setAuthenticationProviderName('SomeProvider');
        $this->persistenceManager->add($ownAccount);
        $this->persistenceManager->add($otherAccount);

        $ownEntity = new RestrictableEntity('ownEntity');
        $ownEntity->setOwnerAccount($ownAccount);
        $othersEntity = new RestrictableEntity('othersEntity');
        $othersEntity->setOwnerAccount($otherAccount);

        $this->restrictableEntityDoctrineRepository->add($ownEntity);
        $ownEntityIdentifier = $this->persistenceManager->getIdentifierByObject($ownEntity);
        $this->restrictableEntityDoctrineRepository->add($othersEntity);
        $othersEntityIdentifier = $this->persistenceManager->getIdentifierByObject($othersEntity);

        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();

        $result = $this->restrictableEntityDoctrineRepository->findAllWithDql();
        self::assertCount(2, $result);

        self::assertNotNull($this->persistenceManager->getObjectByIdentifier($ownEntityIdentifier, RestrictableEntity::class));
        self::assertNotNull($this->persistenceManager->getObjectByIdentifier($othersEntityIdentifier, RestrictableEntity::class));

        $this->restrictableEntityDoctrineRepository->removeAll();
        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();
    }

    #[Test]
    public function customersCannotSeeRestrictableEntitesWhichAreOwnedByAndi()
    {
        $account = $this->authenticateRoles(['Neos.Flow:Customer']);
        $account->setAccountIdentifier('MyAccount');
        $account->setAuthenticationProviderName('SomeProvider');
        $andisAccount = new Account();
        $andisAccount->setAccountIdentifier('Andi');
        $andisAccount->setAuthenticationProviderName('SomeProvider');
        $this->persistenceManager->add($account);
        $this->persistenceManager->add($andisAccount);

        $ownEntity = new RestrictableEntity('MyEntity');
        $ownEntity->setOwnerAccount($account);
        $andisEntity = new RestrictableEntity('AndisEntity');
        $andisEntity->setOwnerAccount($andisAccount);

        $this->restrictableEntityDoctrineRepository->add($ownEntity);
        $ownEntityIdentifier = $this->persistenceManager->getIdentifierByObject($ownEntity);
        $this->restrictableEntityDoctrineRepository->add($andisEntity);
        $andisEntityIdentifier = $this->persistenceManager->getIdentifierByObject($andisEntity);

        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();

        $result = $this->restrictableEntityDoctrineRepository->findAllWithDql();
        self::assertCount(1, $result);

        self::assertNotNull($this->persistenceManager->getObjectByIdentifier($ownEntityIdentifier, RestrictableEntity::class));
        self::assertNull($this->persistenceManager->getObjectByIdentifier($andisEntityIdentifier, RestrictableEntity::class));

        $this->restrictableEntityDoctrineRepository->removeAll();
        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();
    }

    #[Test]
    public function administratorsCanSeeRestrictableEntitesWhichAreOwnedByAndi()
    {
        $account = $this->authenticateRoles(['Neos.Flow:Administrator']);
        $account->setAccountIdentifier('MyAccount');
        $account->setAuthenticationProviderName('SomeProvider');
        $andisAccount = new Account();
        $andisAccount->setAccountIdentifier('Andi');
        $andisAccount->setAuthenticationProviderName('SomeProvider');
        $this->persistenceManager->add($account);
        $this->persistenceManager->add($andisAccount);

        $ownEntity = new RestrictableEntity('MyEntity');
        $ownEntity->setOwnerAccount($account);
        $andisEntity = new RestrictableEntity('AndisEntity');
        $andisEntity->setOwnerAccount($andisAccount);

        $this->restrictableEntityDoctrineRepository->add($ownEntity);
        $ownEntityIdentifier = $this->persistenceManager->getIdentifierByObject($ownEntity);
        $this->restrictableEntityDoctrineRepository->add($andisEntity);
        $andisEntityIdentifier = $this->persistenceManager->getIdentifierByObject($andisEntity);

        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();

        $result = $this->restrictableEntityDoctrineRepository->findAllWithDql();
        self::assertCount(2, $result);

        self::assertNotNull($this->persistenceManager->getObjectByIdentifier($ownEntityIdentifier, RestrictableEntity::class));
        self::assertNotNull($this->persistenceManager->getObjectByIdentifier($andisEntityIdentifier, RestrictableEntity::class));

        $this->restrictableEntityDoctrineRepository->removeAll();
        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();
    }

    #[Test]
    public function customersCannotSeeTestEntityAAssociatedToATestEntityBWithValueAdmin()
    {
        $this->authenticateRoles(['Neos.Flow:Customer']);

        $testEntityB = new TestEntityB('Admin');
        $testEntityA = new TestEntityA($testEntityB);

        $testEntityB2 = new TestEntityB('NoAdmin');
        $testEntityA2 = new TestEntityA($testEntityB2);

        $this->testEntityADoctrineRepository->add($testEntityA);
        $this->testEntityADoctrineRepository->add($testEntityA2);
        $testEntityAIdentifier = $this->persistenceManager->getIdentifierByObject($testEntityA);
        $testEntityA2Identifier = $this->persistenceManager->getIdentifierByObject($testEntityA2);

        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();

        $result = $this->testEntityADoctrineRepository->findAllWithDql();
        self::assertCount(1, $result);

        self::assertNull($this->persistenceManager->getObjectByIdentifier($testEntityAIdentifier, TestEntityA::class));
        self::assertNotNull($this->persistenceManager->getObjectByIdentifier($testEntityA2Identifier, TestEntityA::class));

        $this->restrictableEntityDoctrineRepository->removeAll();
        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();
    }

    #[Test]
    public function administratorsCanSeeTestEntityAAssociatedToATestEntityBWithValueAdmin()
    {
        $this->authenticateRoles(['Neos.Flow:Administrator']);

        $testEntityB = new TestEntityB('Admin');
        $testEntityA = new TestEntityA($testEntityB);

        $testEntityB2 = new TestEntityB('NoAdmin');
        $testEntityA2 = new TestEntityA($testEntityB2);

        $this->testEntityADoctrineRepository->add($testEntityA);
        $this->testEntityADoctrineRepository->add($testEntityA2);
        $testEntityAIdentifier = $this->persistenceManager->getIdentifierByObject($testEntityA);
        $testEntityA2Identifier = $this->persistenceManager->getIdentifierByObject($testEntityA2);

        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();

        $result = $this->testEntityADoctrineRepository->findAllWithDql();
        self::assertCount(2, $result);

        self::assertNotNull($this->persistenceManager->getObjectByIdentifier($testEntityAIdentifier, TestEntityA::class));
        self::assertNotNull($this->persistenceManager->getObjectByIdentifier($testEntityA2Identifier, TestEntityA::class));

        $this->restrictableEntityDoctrineRepository->removeAll();
        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();
    }

    #[Test]
    public function customersCannotSeeTestEntityAAssociatedToATestEntityBSomeoneElsesAccount()
    {
        $cacheManager = $this->objectManager->get(CacheManager::class);
        $cacheManager->getCache('Flow_Persistence_Doctrine')->flush();
        $myAccount = $this->authenticateRoles(['Neos.Flow:Customer']);
        $myAccount->setAccountIdentifier('MyAccount');
        $myAccount->setAuthenticationProviderName('SomeProvider');
        $andisAccount = new Account();
        $andisAccount->setAccountIdentifier('Andi');
        $andisAccount->setAuthenticationProviderName('SomeProvider');
        $this->persistenceManager->add($myAccount);
        $this->persistenceManager->add($andisAccount);

        $testEntityB = new TestEntityB('testEntityB');
        $testEntityB->setOwnerAccount($myAccount);
        $testEntityA = new TestEntityA($testEntityB);

        $testEntityB2 = new TestEntityB('testEntityB2');
        $testEntityB2->setOwnerAccount($andisAccount);
        $testEntityA2 = new TestEntityA($testEntityB2);

        $this->testEntityADoctrineRepository->add($testEntityA);
        $this->testEntityADoctrineRepository->add($testEntityA2);
        $testEntityAIdentifier = $this->persistenceManager->getIdentifierByObject($testEntityA);
        $testEntityA2Identifier = $this->persistenceManager->getIdentifierByObject($testEntityA2);

        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();

        $result = $this->testEntityADoctrineRepository->findAllWithDql();
        self::assertCount(1, $result);

        self::assertNotNull($this->persistenceManager->getObjectByIdentifier($testEntityAIdentifier, TestEntityA::class));
        self::assertNull($this->persistenceManager->getObjectByIdentifier($testEntityA2Identifier, TestEntityA::class));

        $this->restrictableEntityDoctrineRepository->removeAll();
        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();
    }

    #[Test]
    public function administratorsCanSeeTestEntityAAssociatedToATestEntityBSomeoneElsesAccount()
    {
        $myAccount = $this->authenticateRoles(['Neos.Flow:Administrator']);
        $myAccount->setAccountIdentifier('MyAccount');
        $myAccount->setAuthenticationProviderName('SomeProvider');
        $andisAccount = new Account();
        $andisAccount->setAccountIdentifier('Andi');
        $andisAccount->setAuthenticationProviderName('SomeProvider');
        $this->persistenceManager->add($myAccount);
        $this->persistenceManager->add($andisAccount);

        $testEntityB = new TestEntityB('testEntityB');
        $testEntityB->setOwnerAccount($myAccount);
        $testEntityA = new TestEntityA($testEntityB);

        $testEntityB2 = new TestEntityB('testEntityB2');
        $testEntityB2->setOwnerAccount($andisAccount);
        $testEntityA2 = new TestEntityA($testEntityB2);

        $this->testEntityADoctrineRepository->add($testEntityA);
        $this->testEntityADoctrineRepository->add($testEntityA2);
        $testEntityAIdentifier = $this->persistenceManager->getIdentifierByObject($testEntityA);
        $testEntityA2Identifier = $this->persistenceManager->getIdentifierByObject($testEntityA2);

        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();

        $result = $this->testEntityADoctrineRepository->findAllWithDql();
        self::assertCount(2, $result);

        self::assertNotNull($this->persistenceManager->getObjectByIdentifier($testEntityAIdentifier, TestEntityA::class));
        self::assertNotNull($this->persistenceManager->getObjectByIdentifier($testEntityA2Identifier, TestEntityA::class));

        $this->restrictableEntityDoctrineRepository->removeAll();
        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();
    }

    #[Test]
    public function inOperatorWorksWithSimpleArrays()
    {
        // These relations are needed to fulfill the policy that is tested in "inOperatorWorksWithGlobalObjectAccess" as the globalObject has an empty array in this test, the query will do a "(NOT) IS NULL" constraint for this relation.
        $testEntityD = new TestEntityD();
        $testEntityD2 = new TestEntityD();
        $this->testEntityDDoctrineRepository->add($testEntityD);
        $this->testEntityDDoctrineRepository->add($testEntityD2);

        $testEntityC = new TestEntityC();
        $testEntityC->setSimpleStringProperty('Christopher');
        $testEntityC->setRelatedEntityD($testEntityD);
        $testEntityC2 = new TestEntityC();
        $testEntityC2->setSimpleStringProperty('Andi');
        $testEntityC2->setRelatedEntityD($testEntityD2);
        $this->testEntityCDoctrineRepository->add($testEntityC);
        $this->testEntityCDoctrineRepository->add($testEntityC2);

        $testEntityCIdentifier = $this->persistenceManager->getIdentifierByObject($testEntityC);
        $testEntityC2Identifier = $this->persistenceManager->getIdentifierByObject($testEntityC2);

        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();

        $result = $this->testEntityCDoctrineRepository->findAllWithDql();
        self::assertCount(1, $result);

        self::assertNotNull($this->persistenceManager->getObjectByIdentifier($testEntityCIdentifier, TestEntityC::class));
        self::assertNull($this->persistenceManager->getObjectByIdentifier($testEntityC2Identifier, TestEntityC::class));
        $this->restrictableEntityDoctrineRepository->removeAll();
        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();
    }

    #[Test]
    public function inOperatorWorksWithEmptyArray()
    {
        $testEntityC = new TestEntityC();
        $testEntityC->setSimpleStringProperty('Christopher');
        $testEntityC2 = new TestEntityC();
        $testEntityC2->setSimpleStringProperty('Andi');
        $this->testEntityCDoctrineRepository->add($testEntityC);
        $this->testEntityCDoctrineRepository->add($testEntityC2);

        $testEntityCIdentifier = $this->persistenceManager->getIdentifierByObject($testEntityC);
        $testEntityC2Identifier = $this->persistenceManager->getIdentifierByObject($testEntityC2);

        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();

        $result = $this->testEntityCDoctrineRepository->findAllWithDql();
        self::assertCount(0, $result);

        self::assertNull($this->persistenceManager->getObjectByIdentifier($testEntityCIdentifier, TestEntityC::class));
        self::assertNull($this->persistenceManager->getObjectByIdentifier($testEntityC2Identifier, TestEntityC::class));
        $this->restrictableEntityDoctrineRepository->removeAll();
        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();
    }

    #[Test]
    public function inOperatorWorksWithGlobalObjectAccess()
    {
        $cacheManager = $this->objectManager->get(CacheManager::class);
        $cacheManager->getCache('Flow_Persistence_Doctrine')->flush();
        $testEntityD1 = new TestEntityD();
        $testEntityD2 = new TestEntityD();
        $this->testEntityDDoctrineRepository->add($testEntityD1);
        $this->testEntityDDoctrineRepository->add($testEntityD2);

        $this->globalObjectTestContext->setSecurityFixturesEntityDCollection([$testEntityD1, $testEntityD2]);

        $testEntityC = new TestEntityC();
        $testEntityC->setSimpleStringProperty('Basti');
        $testEntityC->setRelatedEntityD($testEntityD2);
        $this->testEntityCDoctrineRepository->add($testEntityC);

        $testEntityCIdentifier = $this->persistenceManager->getIdentifierByObject($testEntityC);

        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();

        $result = $this->testEntityCDoctrineRepository->findAllWithDql();
        self::assertCount(0, $result);

        self::assertNull($this->persistenceManager->getObjectByIdentifier($testEntityCIdentifier, TestEntityC::class));

        $this->restrictableEntityDoctrineRepository->removeAll();
        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();
    }

    #[Test]
    public function containsOperatorBlocksWithOneToMany()
    {
        $testEntityCIdentifier = $this->setupContainsRelationForOneToMany();

        $result = $this->testEntityCDoctrineRepository->findAllWithDql();
        self::assertCount(0, $result);

        self::assertNull($this->persistenceManager->getObjectByIdentifier($testEntityCIdentifier, TestEntityC::class));

        $this->restrictableEntityDoctrineRepository->removeAll();
        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();
    }

    /**
     * @throws \Neos\Flow\Persistence\Exception\IllegalObjectTypeException
     */
    #[Test]
    public function containsOperatorGrantsWithOneToMany()
    {
        $testEntityCIdentifier = $this->setupContainsRelationForOneToMany();

        $this->authenticateRoles(['Neos.Flow:Customer']);

        $result = $this->testEntityCDoctrineRepository->findAllWithDql();
        self::assertCount(1, $result);

        self::assertInstanceOf(TestEntityC::class, $this->persistenceManager->getObjectByIdentifier($testEntityCIdentifier, TestEntityC::class));

        $this->restrictableEntityDoctrineRepository->removeAll();
        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();
    }

    #[Test]
    public function containsOperatorBlocksWithManyToMany()
    {
        $testEntityCIdentifier = $this->setupContainsRelationForManyToMany();

        $result = $this->testEntityCDoctrineRepository->findAllWithDql();
        self::assertCount(0, $result);

        self::assertNull($this->persistenceManager->getObjectByIdentifier($testEntityCIdentifier, TestEntityC::class));

        $this->restrictableEntityDoctrineRepository->removeAll();
        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();
    }

    /**
     * @throws \Neos\Flow\Persistence\Exception\IllegalObjectTypeException
     */
    #[Test]
    public function containsOperatorGrantsWithManyToMany()
    {
        $testEntityCIdentifier = $this->setupContainsRelationForManyToMany();

        $this->authenticateRoles(['Neos.Flow:Customer']);

        $result = $this->testEntityCDoctrineRepository->findAllWithDql();
        self::assertCount(1, $result);

        self::assertInstanceOf(TestEntityC::class, $this->persistenceManager->getObjectByIdentifier($testEntityCIdentifier, TestEntityC::class));

        $this->restrictableEntityDoctrineRepository->removeAll();
        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();
    }

    /**
     * @return string
     * @throws \Neos\Flow\Persistence\Exception\IllegalObjectTypeException
     */
    private function setupContainsRelationForOneToMany()
    {
        $cacheManager = $this->objectManager->get(CacheManager::class);
        $cacheManager->getCache('Flow_Persistence_Doctrine')->flush();

        $testEntityD1 = new TestEntityD();
        $testEntityD2 = new TestEntityD();

        $this->globalObjectTestContext->setSecurityFixturesEntityD($testEntityD1);

        $testEntityC = new TestEntityC();
        $testEntityCIdentifier = $this->persistenceManager->getIdentifierByObject($testEntityC);

        // the other test policy kicks in
        $testEntityC->setSimpleStringProperty('Not one of the three');
        $testEntityC->setRelatedEntityD($testEntityD1);
        // if neither of those are set this way.

        $testEntityD1->setManyToOneToRelatedEntityC($testEntityC);

        $this->testEntityCDoctrineRepository->add($testEntityC);
        $this->testEntityDDoctrineRepository->add($testEntityD1);
        $this->testEntityDDoctrineRepository->add($testEntityD2);

        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();

        return $testEntityCIdentifier;
    }

    /**
     * @return string
     * @throws \Neos\Flow\Persistence\Exception\IllegalObjectTypeException
     */
    private function setupContainsRelationForManyToMany()
    {
        $cacheManager = $this->objectManager->get(CacheManager::class);
        $cacheManager->getCache('Flow_Persistence_Doctrine')->flush();

        $testEntityD1 = new TestEntityD();
        $testEntityD2 = new TestEntityD();

        $this->globalObjectTestContext->setSecurityFixturesEntityD($testEntityD1);

        $testEntityC = new TestEntityC();
        $testEntityCIdentifier = $this->persistenceManager->getIdentifierByObject($testEntityC);

        // the other test policy kicks in
        $testEntityC->setSimpleStringProperty('Not one of the three');
        $testEntityC->setRelatedEntityD($testEntityD1);
        // if neither of those are set this way.

        $testEntityC->setManyToManyToRelatedEntityD(new ArrayCollection([$testEntityD1]));

        $this->testEntityCDoctrineRepository->add($testEntityC);
        $this->testEntityDDoctrineRepository->add($testEntityD1);
        $this->testEntityDDoctrineRepository->add($testEntityD2);

        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();

        return $testEntityCIdentifier;
    }
}
