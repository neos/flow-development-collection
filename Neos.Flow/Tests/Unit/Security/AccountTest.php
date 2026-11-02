<?php

declare(strict_types=1);

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
use PHPUnit\Framework\Attributes\Test;

/**
 * Test case for the account
 */
final class AccountTest extends UnitTestCase
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
        $mockPolicyService->method('getRole')->willReturnCallback(function (string $roleIdentifier) use ($administratorRole, $customerRole) {
            switch ($roleIdentifier) {
                case 'Neos.Flow:Administrator':
                    return $administratorRole;
                case 'Neos.Flow:Customer':
                    return $customerRole;
                default:
                    throw new NoSuchRoleException();
            }
        });
        $mockPolicyService->method('hasRole')->willReturnCallback(function (string $roleIdentifier) use ($administratorRole, $customerRole): bool {
            switch ($roleIdentifier) {
                case 'Neos.Flow:Administrator':
                case 'Neos.Flow:Customer':
                    return true;
                default:
                    return false;
            }
        });

        $this->account = $this->getAccessibleMock(Account::class, []);
        $this->account->_set('policyService', $mockPolicyService);
    }

    #[Test]
    public function addRoleAddsRoleToAccountIfNotAssigned()
    {
        $this->account->setRoles([$this->administratorRole]);
        $this->account->addRole($this->customerRole);
        self::assertCount(2, $this->account->getRoles());
    }

    #[Test]
    public function addRoleSkipsRoleIfAssigned()
    {
        $this->account->setRoles([$this->administratorRole]);
        $this->account->addRole($this->administratorRole);

        self::assertCount(1, $this->account->getRoles());
    }

    #[Test]
    public function removeRoleRemovesRoleFromAccountIfAssigned()
    {
        $this->account->setRoles([$this->administratorRole, $this->customerRole]);
        $this->account->removeRole($this->customerRole);

        self::assertCount(1, $this->account->getRoles());
    }

    #[Test]
    public function removeRoleSkipsRemovalIfRoleNotAssigned()
    {
        $this->account->setRoles([$this->administratorRole]);
        $this->account->removeRole($this->customerRole);

        self::assertCount(1, $this->account->getRoles());
    }

    #[Test]
    public function hasRoleWorks()
    {
        $this->account->setRoles([$this->administratorRole]);

        self::assertTrue($this->account->hasRole($this->administratorRole));
        self::assertFalse($this->account->hasRole($this->customerRole));
    }

    #[Test]
    public function getRolesReturnsOnlyExistingRoles()
    {
        $this->inject($this->account, 'roleIdentifiers', ['Acme.Demo:NoLongerThere', $this->administratorRole->getIdentifier()]);

        $roles = $this->account->getRoles();
        self::assertCount(1, $roles);
        self::assertArrayHasKey($this->administratorRole->getIdentifier(), $roles);
    }

    #[Test]
    public function hasRoleReturnsFalseForAssignedButNonExistentRole()
    {
        $this->inject($this->account, 'roleIdentifiers', ['Acme.Demo:NoLongerThere', $this->administratorRole->getIdentifier()]);

        self::assertTrue($this->account->hasRole($this->administratorRole));
        self::assertFalse($this->account->hasRole(new Role('Acme.Demo:NoLongerThere')));
    }

    #[Test]
    public function setRolesWorks()
    {
        $roles = [$this->administratorRole, $this->customerRole];
        $expectedRoles = [$this->administratorRole->getIdentifier() => $this->administratorRole, $this->customerRole->getIdentifier() => $this->customerRole];
        $this->account->setRoles($roles);

        self::assertSame($expectedRoles, $this->account->getRoles());
    }

    #[Test]
    public function expirationDateCanBeSetNull()
    {
        $this->account->setExpirationDate(new \DateTime());
        $this->account->setExpirationDate(null);

        self::assertEquals(null, $this->account->getExpirationDate());
    }

    #[Test]
    public function isActiveReturnsTrueIfTheAccountHasNoExpirationDate()
    {
        $this->account->setExpirationDate(null);
        self::assertTrue($this->account->isActive());
    }

    #[Test]
    public function isActiveReturnsTrueIfTheAccountHasAnExpirationDateInTheFuture()
    {
        $this->inject($this->account, 'now', new \DateTime());

        $this->account->setExpirationDate(new \DateTime('tomorrow'));
        self::assertTrue($this->account->isActive());
    }

    #[Test]
    public function isActiveReturnsFalseIfTheAccountHasAnExpirationDateInThePast()
    {
        $this->inject($this->account, 'now', new \DateTime());

        $this->account->setExpirationDate(new \DateTime('yesterday'));
        self::assertFalse($this->account->isActive());
    }
}
