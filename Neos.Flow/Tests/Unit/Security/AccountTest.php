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

use Neos\Flow\Security\Account;
use Neos\Flow\Security\Exception\NoSuchRoleException;
use Neos\Flow\Security\Policy\PolicyService;
use Neos\Flow\Security\Policy\Role;
use Neos\Flow\Tests\UnitTestCase;

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
    protected function setUp(): void
    {
        $administratorRole = new Role('Neos.Flow:Administrator');
        $this->administratorRole = $administratorRole;
        $customerRole = new Role('Neos.Flow:Customer');
        $this->customerRole = $customerRole;

        $mockPolicyService = $this->createMock(PolicyService::class);
        $mockPolicyService->expects(self::any())->method('getRole')->will(self::returnCallBack(function ($roleIdentifier) use ($administratorRole, $customerRole) {
            switch ($roleIdentifier) {
                case 'Neos.Flow:Administrator':
                    return $administratorRole;
                case 'Neos.Flow:Customer':
                    return $customerRole;
                default:
                    throw new NoSuchRoleException();
            }
        }));
        $mockPolicyService->expects(self::any())->method('hasRole')->will(self::returnCallBack(function ($roleIdentifier) use ($administratorRole, $customerRole) {
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
        self::assertCount(2, $this->account->getRoles());
    }

    /**
     * @test
     */
    public function addRoleSkipsRoleIfAssigned()
    {
        $this->account->setRoles([$this->administratorRole]);
        $this->account->addRole($this->administratorRole);

        self::assertCount(1, $this->account->getRoles());
    }

    /**
     * @test
     */
    public function removeRoleRemovesRoleFromAccountIfAssigned()
    {
        $this->account->setRoles([$this->administratorRole, $this->customerRole]);
        $this->account->removeRole($this->customerRole);

        self::assertCount(1, $this->account->getRoles());
    }

    /**
     * @test
     */
    public function removeRoleSkipsRemovalIfRoleNotAssigned()
    {
        $this->account->setRoles([$this->administratorRole]);
        $this->account->removeRole($this->customerRole);

        self::assertCount(1, $this->account->getRoles());
    }

    /**
     * @test
     */
    public function hasRoleWorks()
    {
        $this->account->setRoles([$this->administratorRole]);

        self::assertTrue($this->account->hasRole($this->administratorRole));
        self::assertFalse($this->account->hasRole($this->customerRole));
    }

    /**
     * @test
     */
    public function getRolesReturnsOnlyExistingRoles()
    {
        $this->inject($this->account, 'roleIdentifiers', ['Acme.Demo:NoLongerThere', $this->administratorRole->getIdentifier()]);

        $roles = $this->account->getRoles();
        self::assertCount(1, $roles);
        self::assertArrayHasKey($this->administratorRole->getIdentifier(), $roles);
    }

    /**
     * @test
     */
    public function hasRoleReturnsFalseForAssignedButNonExistentRole()
    {
        $this->inject($this->account, 'roleIdentifiers', ['Acme.Demo:NoLongerThere', $this->administratorRole->getIdentifier()]);

        self::assertTrue($this->account->hasRole($this->administratorRole));
        self::assertFalse($this->account->hasRole(new Role('Acme.Demo:NoLongerThere')));
    }

    /**
     * @test
     */
    public function setRolesWorks()
    {
        $roles = [$this->administratorRole, $this->customerRole];
        $expectedRoles = [$this->administratorRole->getIdentifier() => $this->administratorRole, $this->customerRole->getIdentifier() => $this->customerRole];
        $this->account->setRoles($roles);

        self::assertSame($expectedRoles, $this->account->getRoles());
    }

    /**
     * @test
     */
    public function expirationDateCanBeSetNull()
    {
        $this->account->setExpirationDate(new \DateTime());
        $this->account->setExpirationDate(null);

        self::assertEquals(null, $this->account->getExpirationDate());
    }

    /**
     * @test
     */
    public function isActiveReturnsTrueIfTheAccountHasNoExpirationDate()
    {
        $this->account->setExpirationDate(null);
        self::assertTrue($this->account->isActive());
    }

    /**
     * @test
     */
    public function isActiveReturnsTrueIfTheAccountHasAnExpirationDateInTheFuture()
    {
        $this->inject($this->account, 'now', new \DateTime());

        $this->account->setExpirationDate(new \DateTime('tomorrow'));
        self::assertTrue($this->account->isActive());
    }

    /**
     * @test
     */
    public function isActiveReturnsFalseIfTheAccountHasAnExpirationDateInThePast()
    {
        $this->inject($this->account, 'now', new \DateTime());

        $this->account->setExpirationDate(new \DateTime('yesterday'));
        self::assertFalse($this->account->isActive());
    }
}
