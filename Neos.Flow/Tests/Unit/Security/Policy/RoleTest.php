<?php

declare(strict_types=1);

namespace Neos\Flow\Tests\Unit\Security\Policy;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */
use Neos\Flow\Security\Policy\Role;
use Neos\Flow\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Testcase for Neos\Flow\Security\Policy\Role
 */
final class RoleTest extends UnitTestCase
{
    /**
     * data provider
     *
     * @return \Iterator<(int | string), mixed>
     */
    public static function roleIdentifiersAndPackageKeysAndNames(): \Iterator
    {
        yield ['Neos.Flow:Everybody', 'Everybody', 'Neos.Flow', 'A role for everybody', 'The role is automatically assigned to every session'];
        yield ['Acme.Demo:Test', 'Test', 'Acme.Demo', 'just a label', ''];
        yield ['Acme.Demo.Sub:Test', 'Test', 'Acme.Demo.Sub', '', 'A descriptive description'];
    }

    /**
     * @param string $roleIdentifier
     * @param string $name
     * @param string $packageKey
     * @param string $label
     * @param string $description
     */
    #[DataProvider('roleIdentifiersAndPackageKeysAndNames')]
    #[Test]
    public function setNameTolePropertiesWork(string $roleIdentifier, string $name, string $packageKey, string $label, string $description): void
    {
        $role = new Role($roleIdentifier, [], $label, $description);

        self::assertSame($name, $role->getName());
        self::assertSame($packageKey, $role->getPackageKey());
        self::assertSame($description, $role->getDescription());

        if ($label === '') {
            self::assertSame($role->getName(), $role->getLabel());
        } else {
            self::assertSame($label, $role->getLabel());
        }
    }

    #[Test]
    public function setParentRolesMakesSureThatParentRolesDontContainDuplicates()
    {
        /** @var Role|MockObject $role */
        $role = $this->getAccessibleMock(Role::class, [], ['Acme.Demo:Test']);

        /** @var Role|MockObject $parentRole1 */
        $parentRole1 = $this->getAccessibleMock(Role::class, [], ['Acme.Demo:Parent1']);
        /** @var Role|MockObject $parentRole2 */
        $parentRole2 = $this->getAccessibleMock(Role::class, [], ['Acme.Demo:Parent2']);

        $parentRole2->addParentRole($parentRole1);
        $role->setParentRoles([$parentRole1, $parentRole2, $parentRole2, $parentRole1]);

        $expectedParentRoles = [
            'Acme.Demo:Parent1' => $parentRole1,
            'Acme.Demo:Parent2' => $parentRole2
        ];

        self::assertCount(2, $role->getParentRoles());
        self::assertEquals($expectedParentRoles, $role->getParentRoles());
    }
}
