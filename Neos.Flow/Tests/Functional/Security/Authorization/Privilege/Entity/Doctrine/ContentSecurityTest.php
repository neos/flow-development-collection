<?php
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

use Neos\Flow\Cache\CacheManager;
use Neos\Flow\Persistence\Doctrine\PersistenceManager;
use Neos\Flow\Tests\Functional\Security\Fixtures;
use Neos\Flow\Tests\FunctionalTestCase;
use Neos\Flow\Tests\Functional\Aop;
use Neos\Flow\Security;

/**
 * Testcase for content security using doctrine persistence
 *
 */
class ContentSecurityTest extends FunctionalTestCase
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
     * @var Aop\Fixtures\TestContext
     */
    protected $globalObjectTestContext;

    /**
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        if (!$this->persistenceManager instanceof PersistenceManager) {
            $this->markTestSkipped('Doctrine persistence is not enabled');
        }
        $this->restrictableEntityDoctrineRepository = new Fixtures\RestrictableEntityDoctrineRepository();
        $this->testEntityADoctrineRepository = new Fixtures\TestEntityADoctrineRepository();
        $this->testEntityCDoctrineRepository = new Fixtures\TestEntityCDoctrineRepository();
        $this->testEntityDDoctrineRepository = new Fixtures\TestEntityDDoctrineRepository();
        $this->globalObjectTestContext = $this->objectManager->get(Aop\Fixtures\TestContext::class);
    }

    /**
     * @test
     */
    public function administratorsAreAllowedToSeeHiddenRestrictableEntities()
    {
        $this->authenticateRoles(['Neos.Flow:Administrator']);

        $defaultEntity = new Fixtures\RestrictableEntity('default');
        $hiddenEntity = new Fixtures\RestrictableEntity('hiddenEntity');
        $hiddenEntity->setHidden(true);

        $this->restrictableEntityDoctrineRepository->add($defaultEntity);
        $defaultEntityIdentifier = $this->persistenceManager->getIdentifierByObject($defaultEntity);
        $this->restrictableEntityDoctrineRepository->add($hiddenEntity);
        $hiddenEntityIdentifier = $this->persistenceManager->getIdentifierByObject($hiddenEntity);

        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();

        $result = $this->restrictableEntityDoctrineRepository->findAllWithDql();
        $this->assertTrue(count($result) === 2);

        $this->assertNotNull($this->persistenceManager->getObjectByIdentifier($defaultEntityIdentifier, Fixtures\RestrictableEntity::class));
        $this->assertNotNull($this->persistenceManager->getObjectByIdentifier($hiddenEntityIdentifier, Fixtures\RestrictableEntity::class));

        $this->restrictableEntityDoctrineRepository->removeAll();
        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();
    }

    /**
     * @test
     */
    public function customersAreNotAllowedToSeeHiddenRestrictableEntities()
    {
        $this->authenticateRoles(['Neos.Flow:Customer']);

        $defaultEntity = new Fixtures\RestrictableEntity('default');
        $hiddenEntity = new Fixtures\RestrictableEntity('hiddenEntity');
        $hiddenEntity->setHidden(true);

        $this->restrictableEntityDoctrineRepository->add($defaultEntity);
        $defaultEntityIdentifier = $this->persistenceManager->getIdentifierByObject($defaultEntity);
        $this->restrictableEntityDoctrineRepository->add($hiddenEntity);
        $hiddenEntityIdentifier = $this->persistenceManager->getIdentifierByObject($hiddenEntity);

        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();

        $result = $this->restrictableEntityDoctrineRepository->findAllWithDql();
        $this->assertTrue(count($result) === 1);

        $this->assertNotNull($this->persistenceManager->getObjectByIdentifier($defaultEntityIdentifier, Fixtures\RestrictableEntity::class));
        $this->assertNull($this->persistenceManager->getObjectByIdentifier($hiddenEntityIdentifier, Fixtures\RestrictableEntity::class));

        $this->restrictableEntityDoctrineRepository->removeAll();
        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();
    }

    /**
     * @test
     */
    public function customersAreNotAllowedToSeeDeletedRestrictableEntities()
    {
        $this->authenticateRoles(['Neos.Flow:Customer']);

        $defaultEntity = new Fixtures\RestrictableEntity('default');
        $deletedEntity = new Fixtures\RestrictableEntity('deletedEntry');
        $deletedEntity->delete();

        $this->restrictableEntityDoctrineRepository->add($defaultEntity);
        $defaultEntityIdentifier = $this->persistenceManager->getIdentifierByObject($defaultEntity);
        $this->restrictableEntityDoctrineRepository->add($deletedEntity);
        $deletedEntityIdentifier = $this->persistenceManager->getIdentifierByObject($deletedEntity);

        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();

        $result = $this->restrictableEntityDoctrineRepository->findAllWithDql();
        $this->assertTrue(count($result) === 1);

        $this->assertNotNull($this->persistenceManager->getObjectByIdentifier($defaultEntityIdentifier, Fixtures\RestrictableEntity::class));
        $this->assertNull($this->persistenceManager->getObjectByIdentifier($deletedEntityIdentifier, Fixtures\RestrictableEntity::class));

        $this->restrictableEntityDoctrineRepository->removeAll();
        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();
    }

    /**
     * @test
     */
    public function administratorsCanSeeDeletedRestrictableEntities()
    {
        $this->authenticateRoles(['Neos.Flow:Administrator']);

        $defaultEntity = new Fixtures\RestrictableEntity('default');
        $deletedEntity = new Fixtures\RestrictableEntity('hiddenEntity');
        $deletedEntity->delete();

        $this->restrictableEntityDoctrineRepository->add($defaultEntity);
        $defaultEntityIdentifier = $this->persistenceManager->getIdentifierByObject($defaultEntity);
        $this->restrictableEntityDoctrineRepository->add($deletedEntity);
        $deletedEntityIdentifier = $this->persistenceManager->getIdentifierByObject($deletedEntity);

        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();

        $result = $this->restrictableEntityDoctrineRepository->findAllWithDql();
        $this->assertTrue(count($result) === 2);

        $this->assertNotNull($this->persistenceManager->getObjectByIdentifier($defaultEntityIdentifier, Fixtures\RestrictableEntity::class));
        $this->assertNotNull($this->persistenceManager->getObjectByIdentifier($deletedEntityIdentifier, Fixtures\RestrictableEntity::class));

        $this->restrictableEntityDoctrineRepository->removeAll();
        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();
    }

    /**
     * @test
     */
    public function anonymousUsersAreNotAllowedToSeeRestrictableEntitiesAtAll()
    {
        $defaultEntity = new Fixtures\RestrictableEntity('default');
        $hiddenEntity = new Fixtures\RestrictableEntity('hiddenEntity');
        $hiddenEntity->setHidden(true);

        $this->restrictableEntityDoctrineRepository->add($defaultEntity);
        $defaultEntityIdentifier = $this->persistenceManager->getIdentifierByObject($defaultEntity);
        $this->restrictableEntityDoctrineRepository->add($hiddenEntity);
        $hiddenEntityIdentifier = $this->persistenceManager->getIdentifierByObject($hiddenEntity);

        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();

        $result = $this->restrictableEntityDoctrineRepository->findAllWithDql();
        $this->assertTrue(count($result) === 0);

        $this->assertNull($this->persistenceManager->getObjectByIdentifier($defaultEntityIdentifier, Fixtures\RestrictableEntity::class));
        $this->assertNull($this->persistenceManager->getObjectByIdentifier($hiddenEntityIdentifier, Fixtures\RestrictableEntity::class));

        $this->restrictableEntityDoctrineRepository->removeAll();
        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();
    }

    /**
     * @test
     */
    public function customersCannotSeeOthersRestrictableEntites()
    {
        $ownAccount = $this->authenticateRoles(['Neos.Flow:Customer']);
        $ownAccount->setAccountIdentifier('ownAccount');
        $ownAccount->setAuthenticationProviderName('SomeProvider');
        $otherAccount = new Security\Account();
        $otherAccount->setAccountIdentifier('othersAccount');
        $otherAccount->setAuthenticationProviderName('SomeProvider');
        $this->persistenceManager->add($ownAccount);
        $this->persistenceManager->add($otherAccount);

        $ownEntity = new Fixtures\RestrictableEntity('ownEntity');
        $ownEntity->setOwnerAccount($ownAccount);
        $othersEntity = new Fixtures\RestrictableEntity('othersEntity');
        $othersEntity->setOwnerAccount($otherAccount);

        $this->restrictableEntityDoctrineRepository->add($ownEntity);
        $ownEntityIdentifier = $this->persistenceManager->getIdentifierByObject($ownEntity);
        $this->restrictableEntityDoctrineRepository->add($othersEntity);
        $othersEntityIdentifier = $this->persistenceManager->getIdentifierByObject($othersEntity);

        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();

        $result = $this->restrictableEntityDoctrineRepository->findAllWithDql();
        $this->assertTrue(count($result) === 1);

        $this->assertNotNull($this->persistenceManager->getObjectByIdentifier($ownEntityIdentifier, Fixtures\RestrictableEntity::class));
        $this->assertNull($this->persistenceManager->getObjectByIdentifier($othersEntityIdentifier, Fixtures\RestrictableEntity::class));

        $this->restrictableEntityDoctrineRepository->removeAll();
        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();
    }

    /**
     * @test
     */
    public function administratorsCanSeeOthersRestrictableEntites()
    {
        $ownAccount = $this->authenticateRoles(['Neos.Flow:Administrator', 'Neos.Flow:Customer']);
        $ownAccount->setAccountIdentifier('ownAccount');
        $ownAccount->setAuthenticationProviderName('SomeProvider');
        $otherAccount = new Security\Account();
        $otherAccount->setAccountIdentifier('othersAccount');
        $otherAccount->setAuthenticationProviderName('SomeProvider');
        $this->persistenceManager->add($ownAccount);
        $this->persistenceManager->add($otherAccount);

        $ownEntity = new Fixtures\RestrictableEntity('ownEntity');
        $ownEntity->setOwnerAccount($ownAccount);
        $othersEntity = new Fixtures\RestrictableEntity('othersEntity');
        $othersEntity->setOwnerAccount($otherAccount);

        $this->restrictableEntityDoctrineRepository->add($ownEntity);
        $ownEntityIdentifier = $this->persistenceManager->getIdentifierByObject($ownEntity);
        $this->restrictableEntityDoctrineRepository->add($othersEntity);
        $othersEntityIdentifier = $this->persistenceManager->getIdentifierByObject($othersEntity);

        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();

        $result = $this->restrictableEntityDoctrineRepository->findAllWithDql();
        $this->assertTrue(count($result) === 2);

        $this->assertNotNull($this->persistenceManager->getObjectByIdentifier($ownEntityIdentifier, Fixtures\RestrictableEntity::class));
        $this->assertNotNull($this->persistenceManager->getObjectByIdentifier($othersEntityIdentifier, Fixtures\RestrictableEntity::class));

        $this->restrictableEntityDoctrineRepository->removeAll();
        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();
    }

    /**
     * @test
     */
    public function customersCannotSeeRestrictableEntitesWhichAreOwnedByAndi()
    {
        $account = $this->authenticateRoles(['Neos.Flow:Customer']);
        $account->setAccountIdentifier('MyAccount');
        $account->setAuthenticationProviderName('SomeProvider');
        $andisAccount = new Security\Account();
        $andisAccount->setAccountIdentifier('Andi');
        $andisAccount->setAuthenticationProviderName('SomeProvider');
        $this->persistenceManager->add($account);
        $this->persistenceManager->add($andisAccount);

        $ownEntity = new Fixtures\RestrictableEntity('MyEntity');
        $ownEntity->setOwnerAccount($account);
        $andisEntity = new Fixtures\RestrictableEntity('AndisEntity');
        $andisEntity->setOwnerAccount($andisAccount);

        $this->restrictableEntityDoctrineRepository->add($ownEntity);
        $ownEntityIdentifier = $this->persistenceManager->getIdentifierByObject($ownEntity);
        $this->restrictableEntityDoctrineRepository->add($andisEntity);
        $andisEntityIdentifier = $this->persistenceManager->getIdentifierByObject($andisEntity);

        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();

        $result = $this->restrictableEntityDoctrineRepository->findAllWithDql();
        $this->assertTrue(count($result) === 1);

        $this->assertNotNull($this->persistenceManager->getObjectByIdentifier($ownEntityIdentifier, Fixtures\RestrictableEntity::class));
        $this->assertNull($this->persistenceManager->getObjectByIdentifier($andisEntityIdentifier, Fixtures\RestrictableEntity::class));

        $this->restrictableEntityDoctrineRepository->removeAll();
        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();
    }

    /**
     * @test
     */
    public function administratorsCanSeeRestrictableEntitesWhichAreOwnedByAndi()
    {
        $account = $this->authenticateRoles(['Neos.Flow:Administrator']);
        $account->setAccountIdentifier('MyAccount');
        $account->setAuthenticationProviderName('SomeProvider');
        $andisAccount = new Security\Account();
        $andisAccount->setAccountIdentifier('Andi');
        $andisAccount->setAuthenticationProviderName('SomeProvider');
        $this->persistenceManager->add($account);
        $this->persistenceManager->add($andisAccount);

        $ownEntity = new Fixtures\RestrictableEntity('MyEntity');
        $ownEntity->setOwnerAccount($account);
        $andisEntity = new Fixtures\RestrictableEntity('AndisEntity');
        $andisEntity->setOwnerAccount($andisAccount);

        $this->restrictableEntityDoctrineRepository->add($ownEntity);
        $ownEntityIdentifier = $this->persistenceManager->getIdentifierByObject($ownEntity);
        $this->restrictableEntityDoctrineRepository->add($andisEntity);
        $andisEntityIdentifier = $this->persistenceManager->getIdentifierByObject($andisEntity);

        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();

        $result = $this->restrictableEntityDoctrineRepository->findAllWithDql();
        $this->assertTrue(count($result) === 2);

        $this->assertNotNull($this->persistenceManager->getObjectByIdentifier($ownEntityIdentifier, Fixtures\RestrictableEntity::class));
        $this->assertNotNull($this->persistenceManager->getObjectByIdentifier($andisEntityIdentifier, Fixtures\RestrictableEntity::class));

        $this->restrictableEntityDoctrineRepository->removeAll();
        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();
    }

    /**
     * @test
     */
    public function customersCannotSeeTestEntityAAssociatedToATestEntityBWithValueAdmin()
    {
        $this->authenticateRoles(['Neos.Flow:Customer']);

        $testEntityB = new Fixtures\TestEntityB('Admin');
        $testEntityA = new Fixtures\TestEntityA($testEntityB);

        $testEntityB2 = new Fixtures\TestEntityB('NoAdmin');
        $testEntityA2 = new Fixtures\TestEntityA($testEntityB2);

        $this->testEntityADoctrineRepository->add($testEntityA);
        $this->testEntityADoctrineRepository->add($testEntityA2);
        $testEntityAIdentifier = $this->persistenceManager->getIdentifierByObject($testEntityA);
        $testEntityA2Identifier = $this->persistenceManager->getIdentifierByObject($testEntityA2);

        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();

        $result = $this->testEntityADoctrineRepository->findAllWithDql();
        $this->assertTrue(count($result) === 1);

        $this->assertNull($this->persistenceManager->getObjectByIdentifier($testEntityAIdentifier, Fixtures\TestEntityA::class));
        $this->assertNotNull($this->persistenceManager->getObjectByIdentifier($testEntityA2Identifier, Fixtures\TestEntityA::class));

        $this->restrictableEntityDoctrineRepository->removeAll();
        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();
    }

    /**
     * @test
     */
    public function administratorsCanSeeTestEntityAAssociatedToATestEntityBWithValueAdmin()
    {
        $this->authenticateRoles(['Neos.Flow:Administrator']);

        $testEntityB = new Fixtures\TestEntityB('Admin');
        $testEntityA = new Fixtures\TestEntityA($testEntityB);

        $testEntityB2 = new Fixtures\TestEntityB('NoAdmin');
        $testEntityA2 = new Fixtures\TestEntityA($testEntityB2);

        $this->testEntityADoctrineRepository->add($testEntityA);
        $this->testEntityADoctrineRepository->add($testEntityA2);
        $testEntityAIdentifier = $this->persistenceManager->getIdentifierByObject($testEntityA);
        $testEntityA2Identifier = $this->persistenceManager->getIdentifierByObject($testEntityA2);

        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();

        $result = $this->testEntityADoctrineRepository->findAllWithDql();
        $this->assertTrue(count($result) === 2);

        $this->assertNotNull($this->persistenceManager->getObjectByIdentifier($testEntityAIdentifier, Fixtures\TestEntityA::class));
        $this->assertNotNull($this->persistenceManager->getObjectByIdentifier($testEntityA2Identifier, Fixtures\TestEntityA::class));

        $this->restrictableEntityDoctrineRepository->removeAll();
        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();
    }

    /**
     * @test
     */
    public function customersCannotSeeTestEntityAAssociatedToATestEntityBSomeoneElsesAccount()
    {
        $cacheManager = $this->objectManager->get(CacheManager::class);
        $cacheManager->getCache('Flow_Persistence_Doctrine')->flush();
        $myAccount = $this->authenticateRoles(['Neos.Flow:Customer']);
        $myAccount->setAccountIdentifier('MyAccount');
        $myAccount->setAuthenticationProviderName('SomeProvider');
        $andisAccount = new Security\Account();
        $andisAccount->setAccountIdentifier('Andi');
        $andisAccount->setAuthenticationProviderName('SomeProvider');
        $this->persistenceManager->add($myAccount);
        $this->persistenceManager->add($andisAccount);

        $testEntityB = new Fixtures\TestEntityB('testEntityB');
        $testEntityB->setOwnerAccount($myAccount);
        $testEntityA = new Fixtures\TestEntityA($testEntityB);

        $testEntityB2 = new Fixtures\TestEntityB('testEntityB2');
        $testEntityB2->setOwnerAccount($andisAccount);
        $testEntityA2 = new Fixtures\TestEntityA($testEntityB2);

        $this->testEntityADoctrineRepository->add($testEntityA);
        $this->testEntityADoctrineRepository->add($testEntityA2);
        $testEntityAIdentifier = $this->persistenceManager->getIdentifierByObject($testEntityA);
        $testEntityA2Identifier = $this->persistenceManager->getIdentifierByObject($testEntityA2);

        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();

        $result = $this->testEntityADoctrineRepository->findAllWithDql();
        $this->assertTrue(count($result) === 1);

        $this->assertNotNull($this->persistenceManager->getObjectByIdentifier($testEntityAIdentifier, Fixtures\TestEntityA::class));
        $this->assertNull($this->persistenceManager->getObjectByIdentifier($testEntityA2Identifier, Fixtures\TestEntityA::class));

        $this->restrictableEntityDoctrineRepository->removeAll();
        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();
    }

    /**
     * @test
     */
    public function administratorsCanSeeTestEntityAAssociatedToATestEntityBSomeoneElsesAccount()
    {
        $myAccount = $this->authenticateRoles(['Neos.Flow:Administrator']);
        $myAccount->setAccountIdentifier('MyAccount');
        $myAccount->setAuthenticationProviderName('SomeProvider');
        $andisAccount = new Security\Account();
        $andisAccount->setAccountIdentifier('Andi');
        $andisAccount->setAuthenticationProviderName('SomeProvider');
        $this->persistenceManager->add($myAccount);
        $this->persistenceManager->add($andisAccount);

        $testEntityB = new Fixtures\TestEntityB('testEntityB');
        $testEntityB->setOwnerAccount($myAccount);
        $testEntityA = new Fixtures\TestEntityA($testEntityB);

        $testEntityB2 = new Fixtures\TestEntityB('testEntityB2');
        $testEntityB2->setOwnerAccount($andisAccount);
        $testEntityA2 = new Fixtures\TestEntityA($testEntityB2);

        $this->testEntityADoctrineRepository->add($testEntityA);
        $this->testEntityADoctrineRepository->add($testEntityA2);
        $testEntityAIdentifier = $this->persistenceManager->getIdentifierByObject($testEntityA);
        $testEntityA2Identifier = $this->persistenceManager->getIdentifierByObject($testEntityA2);

        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();

        $result = $this->testEntityADoctrineRepository->findAllWithDql();
        $this->assertTrue(count($result) === 2);

        $this->assertNotNull($this->persistenceManager->getObjectByIdentifier($testEntityAIdentifier, Fixtures\TestEntityA::class));
        $this->assertNotNull($this->persistenceManager->getObjectByIdentifier($testEntityA2Identifier, Fixtures\TestEntityA::class));

        $this->restrictableEntityDoctrineRepository->removeAll();
        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();
    }

    /**
     * @test
     */
    public function inOperatorWorksWithSimpleArrays()
    {
        // These relations are needed to fulfill the policy that is tested in "inOperatorWorksWithGlobalObjectAccess" as the globalObject has an empty array in this test, the query will do a "(NOT) IS NULL" constraint for this relation.
        $testEntityD = new Fixtures\TestEntityD();
        $testEntityD2 = new Fixtures\TestEntityD();
        $this->testEntityDDoctrineRepository->add($testEntityD);
        $this->testEntityDDoctrineRepository->add($testEntityD2);

        $testEntityC = new Fixtures\TestEntityC();
        $testEntityC->setSimpleStringProperty('Christopher');
        $testEntityC->setRelatedEntityD($testEntityD);
        $testEntityC2 = new Fixtures\TestEntityC();
        $testEntityC2->setSimpleStringProperty('Andi');
        $testEntityC2->setRelatedEntityD($testEntityD2);
        $this->testEntityCDoctrineRepository->add($testEntityC);
        $this->testEntityCDoctrineRepository->add($testEntityC2);

        $testEntityCIdentifier = $this->persistenceManager->getIdentifierByObject($testEntityC);
        $testEntityC2Identifier = $this->persistenceManager->getIdentifierByObject($testEntityC2);

        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();

        $result = $this->testEntityCDoctrineRepository->findAllWithDql();
        $this->assertTrue(count($result) === 1);

        $this->assertNotNull($this->persistenceManager->getObjectByIdentifier($testEntityCIdentifier, Fixtures\TestEntityC::class));
        $this->assertNull($this->persistenceManager->getObjectByIdentifier($testEntityC2Identifier, Fixtures\TestEntityC::class));
        $this->restrictableEntityDoctrineRepository->removeAll();
        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();
    }

    /**
     * @test
     */
    public function inOperatorWorksWithEmptyArray()
    {
        $testEntityC = new Fixtures\TestEntityC();
        $testEntityC->setSimpleStringProperty('Christopher');
        $testEntityC2 = new Fixtures\TestEntityC();
        $testEntityC2->setSimpleStringProperty('Andi');
        $this->testEntityCDoctrineRepository->add($testEntityC);
        $this->testEntityCDoctrineRepository->add($testEntityC2);

        $testEntityCIdentifier = $this->persistenceManager->getIdentifierByObject($testEntityC);
        $testEntityC2Identifier = $this->persistenceManager->getIdentifierByObject($testEntityC2);

        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();

        $result = $this->testEntityCDoctrineRepository->findAllWithDql();
        $this->assertTrue(count($result) === 0);

        $this->assertNull($this->persistenceManager->getObjectByIdentifier($testEntityCIdentifier, Fixtures\TestEntityC::class));
        $this->assertNull($this->persistenceManager->getObjectByIdentifier($testEntityC2Identifier, Fixtures\TestEntityC::class));
        $this->restrictableEntityDoctrineRepository->removeAll();
        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();
    }

    /**
     * @test
     */
    public function inOperatorWorksWithGlobalObjectAccess()
    {
        $cacheManager = $this->objectManager->get(CacheManager::class);
        $cacheManager->getCache('Flow_Persistence_Doctrine')->flush();
        $testEntityD1 = new Fixtures\TestEntityD();
        $testEntityD2 = new Fixtures\TestEntityD();
        $this->testEntityDDoctrineRepository->add($testEntityD1);
        $this->testEntityDDoctrineRepository->add($testEntityD2);

        $this->globalObjectTestContext->setSecurityFixturesEntityDCollection([$testEntityD1, $testEntityD2]);

        $testEntityC = new Fixtures\TestEntityC();
        $testEntityC->setSimpleStringProperty('Basti');
        $testEntityC->setRelatedEntityD($testEntityD2);
        $this->testEntityCDoctrineRepository->add($testEntityC);

        $testEntityCIdentifier = $this->persistenceManager->getIdentifierByObject($testEntityC);

        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();

        $result = $this->testEntityCDoctrineRepository->findAllWithDql();
        $this->assertTrue(count($result) === 0);

        $this->assertNull($this->persistenceManager->getObjectByIdentifier($testEntityCIdentifier, Fixtures\TestEntityC::class));

        $this->restrictableEntityDoctrineRepository->removeAll();
        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();
    }
}
