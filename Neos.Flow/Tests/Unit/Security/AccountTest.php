<?php
namespace Neos\Flow\Tests\Unit\Security;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\Security\Account;
use Neos\Flow\Security\Exception\NoSuchRoleException;
use Neos\Flow\Security\Policy\PolicyService;
use Neos\Flow\Security\Policy\Role;
use Neos\Flow\Tests\UnitTestCase;
use Neos\Party\Domain\Service\PartyService;

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
        $administratorRole = new Role('Neos.Flow:Administrator');
        $this->administratorRole = $administratorRole;
        $customerRole = new Role('Neos.Flow:Customer');
        $this->customerRole = $customerRole;

        $mockPolicyService = $this->createMock(PolicyService::class);
        $mockPolicyService->expects($this->any())->method('getRole')->will($this->returnCallback(function ($roleIdentifier) use ($administratorRole, $customerRole) {
            switch ($roleIdentifier) {
                case 'Neos.Flow:Administrator':
                    return $administratorRole;
                case 'Neos.Flow:Customer':
                    return $customerRole;
                default:
                    throw new NoSuchRoleException();
            }
        }));
        $mockPolicyService->expects($this->any())->method('hasRole')->will($this->returnCallback(function ($roleIdentifier) use ($administratorRole, $customerRole) {
            switch ($roleIdentifier) {
                case 'Neos.Flow:Administrator':
                case 'Neos.Flow:Customer':
                    return true;
                default:
                    return false;
            }
        }));

        $this->account = $this->getAccessibleMock(Account::class, ['dummy']);
        $this->account->_set('policyService', $mockPolicyService);
    }

    /**
     * @test
     */
    public function addRoleAddsRoleToAccountIfNotAssigned()
    {
        $this->account->setRoles([$this->administratorRole]);
        $this->account->addRole($this->customerRole);
        $this->assertCount(2, $this->account->getRoles());
    }

    /**
     * @test
     */
    public function addRoleSkipsRoleIfAssigned()
    {
        $this->account->setRoles([$this->administratorRole]);
        $this->account->addRole($this->administratorRole);

        $this->assertCount(1, $this->account->getRoles());
    }

    /**
     * @test
     */
    public function removeRoleRemovesRoleFromAccountIfAssigned()
    {
        $this->account->setRoles([$this->administratorRole, $this->customerRole]);
        $this->account->removeRole($this->customerRole);

        $this->assertCount(1, $this->account->getRoles());
    }

    /**
     * @test
     */
    public function removeRoleSkipsRemovalIfRoleNotAssigned()
    {
        $this->account->setRoles([$this->administratorRole]);
        $this->account->removeRole($this->customerRole);

        $this->assertCount(1, $this->account->getRoles());
    }

    /**
     * @test
     */
    public function hasRoleWorks()
    {
        $this->account->setRoles([$this->administratorRole]);

        $this->assertTrue($this->account->hasRole($this->administratorRole));
        $this->assertFalse($this->account->hasRole($this->customerRole));
    }

    /**
     * @test
     */
    public function getRolesReturnsOnlyExistingRoles()
    {
        $this->inject($this->account, 'roleIdentifiers', ['Acme.Demo:NoLongerThere', $this->administratorRole->getIdentifier()]);

        $roles = $this->account->getRoles();
        $this->assertCount(1, $roles);
        $this->assertArrayHasKey($this->administratorRole->getIdentifier(), $roles);
    }

    /**
     * @test
     */
    public function hasRoleReturnsFalseForAssignedButNonExistentRole()
    {
        $this->inject($this->account, 'roleIdentifiers', ['Acme.Demo:NoLongerThere', $this->administratorRole->getIdentifier()]);

        $this->assertTrue($this->account->hasRole($this->administratorRole));
        $this->assertFalse($this->account->hasRole(new Role('Acme.Demo:NoLongerThere')));
    }

    /**
     * @test
     */
    public function setRolesWorks()
    {
        $roles = [$this->administratorRole, $this->customerRole];
        $expectedRoles = [$this->administratorRole->getIdentifier() => $this->administratorRole, $this->customerRole->getIdentifier() => $this->customerRole];
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
}
