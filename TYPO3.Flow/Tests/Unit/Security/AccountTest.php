<?php
namespace TYPO3\Flow\Tests\Unit\Security;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Security\Account;
use TYPO3\Flow\Security\Exception\NoSuchRoleException;
use TYPO3\Flow\Security\Policy\Role;
use TYPO3\Flow\Tests\UnitTestCase;

/**
 * Test case for the account
 */
class AccountTest extends UnitTestCase
{
    /**
     * @var Role
     */
    protected $administratorRole;

    /**
     * @var Role
     */
    protected $customerRole;

    /**
     * @var Account
     */
    protected $account;

    /**
     * Setup function for the test case
     */
    public function setUp()
    {
        $administratorRole = new Role('TYPO3.Flow:Administrator');
        $this->administratorRole = $administratorRole;
        $customerRole = new Role('TYPO3.Flow:Customer');
        $this->customerRole = $customerRole;

        $mockPolicyService = $this->getMock(\TYPO3\Flow\Security\Policy\PolicyService::class);
        $mockPolicyService->expects($this->any())->method('getRole')->will($this->returnCallback(function ($roleIdentifier) use ($administratorRole, $customerRole) {
            switch ($roleIdentifier) {
                case 'TYPO3.Flow:Administrator':
                    return $administratorRole;
                case 'TYPO3.Flow:Customer':
                    return $customerRole;
                default:
                    throw new NoSuchRoleException();
            }
        }));
        $mockPolicyService->expects($this->any())->method('hasRole')->will($this->returnCallback(function ($roleIdentifier) use ($administratorRole, $customerRole) {
            switch ($roleIdentifier) {
                case 'TYPO3.Flow:Administrator':
                case 'TYPO3.Flow:Customer':
                    return true;
                default:
                    return false;
            }
        }));

        $this->account = $this->getAccessibleMock(\TYPO3\Flow\Security\Account::class, array('dummy'));
        $this->account->_set('policyService', $mockPolicyService);
    }

    /**
     * @test
     */
    public function addRoleAddsRoleToAccountIfNotAssigned()
    {
        $this->account->setRoles(array($this->administratorRole));
        $this->account->addRole($this->customerRole);
        $this->assertCount(2, $this->account->getRoles());
    }

    /**
     * @test
     */
    public function addRoleSkipsRoleIfAssigned()
    {
        $this->account->setRoles(array($this->administratorRole));
        $this->account->addRole($this->administratorRole);

        $this->assertCount(1, $this->account->getRoles());
    }

    /**
     * @test
     */
    public function removeRoleRemovesRoleFromAccountIfAssigned()
    {
        $this->account->setRoles(array($this->administratorRole, $this->customerRole));
        $this->account->removeRole($this->customerRole);

        $this->assertCount(1, $this->account->getRoles());
    }

    /**
     * @test
     */
    public function removeRoleSkipsRemovalIfRoleNotAssigned()
    {
        $this->account->setRoles(array($this->administratorRole));
        $this->account->removeRole($this->customerRole);

        $this->assertCount(1, $this->account->getRoles());
    }

    /**
     * @test
     */
    public function hasRoleWorks()
    {
        $this->account->setRoles(array($this->administratorRole));

        $this->assertTrue($this->account->hasRole($this->administratorRole));
        $this->assertFalse($this->account->hasRole($this->customerRole));
    }

    /**
     * @test
     */
    public function getRolesReturnsOnlyExistingRoles()
    {
        $this->inject($this->account, 'roleIdentifiers', array('Acme.Demo:NoLongerThere', $this->administratorRole->getIdentifier()));

        $roles = $this->account->getRoles();
        $this->assertCount(1, $roles);
        $this->assertArrayHasKey($this->administratorRole->getIdentifier(), $roles);
    }

    /**
     * @test
     */
    public function hasRoleReturnsFalseForAssignedButNonExistentRole()
    {
        $this->inject($this->account, 'roleIdentifiers', array('Acme.Demo:NoLongerThere', $this->administratorRole->getIdentifier()));

        $this->assertTrue($this->account->hasRole($this->administratorRole));
        $this->assertFalse($this->account->hasRole(new Role('Acme.Demo:NoLongerThere')));
    }

    /**
     * @test
     */
    public function setRolesWorks()
    {
        $roles = array($this->administratorRole, $this->customerRole);
        $expectedRoles = array($this->administratorRole->getIdentifier() => $this->administratorRole, $this->customerRole->getIdentifier() => $this->customerRole);
        $this->account->setRoles($roles);

        $this->assertSame($expectedRoles, $this->account->getRoles());
    }

    /**
     * @test
     */
    public function expirationDateCanBeSetNull()
    {
        $this->account->setExpirationDate(new \DateTime());
        $this->account->setExpirationDate(null);

        $this->assertEquals(null, $this->account->getExpirationDate());
    }

    /**
     * @test
     */
    public function isActiveReturnsTrueIfTheAccountHasNoExpirationDate()
    {
        $this->account->setExpirationDate(null);
        $this->assertTrue($this->account->isActive());
    }

    /**
     * @test
     */
    public function isActiveReturnsTrueIfTheAccountHasAnExpirationDateInTheFuture()
    {
        $this->inject($this->account, 'now', new \DateTime());

        $this->account->setExpirationDate(new \DateTime('tomorrow'));
        $this->assertTrue($this->account->isActive());
    }

    /**
     * @test
     */
    public function isActiveReturnsFalseIfTheAccountHasAnExpirationDateInThePast()
    {
        $this->inject($this->account, 'now', new \DateTime());

        $this->account->setExpirationDate(new \DateTime('yesterday'));
        $this->assertFalse($this->account->isActive());
    }


    /**
     * @expectedException \TYPO3\Flow\Security\Exception
     * @expectedExceptionCode 1397747246
     * @test
     */
    public function callingGetPartyWithoutIdentifierThrowsException()
    {
        $account = new Account();
        $account->getParty();
    }

    /**
     * @test
     */
    public function callingGetPartyInvokesPartyDomainServiceWithAccountAndReturnsItsValue()
    {
        $account = new Account();
        $partyService = $this->getMock('TYPO3\Party\Domain\Service\PartyService', array('getAssignedPartyOfAccount'));
        $partyService->expects($this->once())->method('getAssignedPartyOfAccount')->with($account)->will($this->returnValue('ReturnedValue'));

        $objectManager = $this->getMock(\TYPO3\Flow\Object\ObjectManagerInterface::class);
        $objectManager->expects($this->once())->method('isRegistered')->with('TYPO3\Party\Domain\Service\PartyService')->will($this->returnValue(true));
        $objectManager->expects($this->once())->method('get')->with('TYPO3\Party\Domain\Service\PartyService')->will($this->returnValue($partyService));

        $this->inject($account, 'objectManager', $objectManager);

        $account->setAccountIdentifier('AccountIdentifierToCheck');
        $this->assertEquals('ReturnedValue', $account->getParty());
    }

    /**
     * @expectedException \TYPO3\Flow\Security\Exception
     * @expectedExceptionCode 1397745354
     * @test
     */
    public function callingSetPartyWithoutIdentifierThrowsException()
    {
        $account = new Account();

        $mockParty = $this->getMock('TYPO3\Party\Domain\Model\AbstractParty');
        $account->setParty($mockParty);
    }

    /**
     * @test
     */
    public function callingSetPartyInvokesPartyDomainServiceWithAccountIdentifier()
    {
        $partyMock = $this->getMock('TYPO3\Party\Domain\Model\AbstractParty');
        $account = new Account();
        $partyService = $this->getMock('DummyService', array('assignAccountToParty'));
        $partyService->expects($this->once())->method('assignAccountToParty')->with($account, $partyMock);

        $objectManager = $this->getMock(\TYPO3\Flow\Object\ObjectManagerInterface::class);
        $objectManager->expects($this->once())->method('isRegistered')->with('TYPO3\Party\Domain\Service\PartyService')->will($this->returnValue(true));
        $objectManager->expects($this->once())->method('get')->with('TYPO3\Party\Domain\Service\PartyService')->will($this->returnValue($partyService));

        $this->inject($account, 'objectManager', $objectManager);

        $account->setAccountIdentifier('AccountIdentifierToCheck');
        $account->setParty($partyMock);
    }
}
