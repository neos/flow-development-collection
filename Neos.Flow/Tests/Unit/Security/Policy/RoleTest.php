<?php
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

/**
 * Testcase for for Neos\Flow\Security\Policy\Role
 */
class RoleTest extends UnitTestCase
{
    /**
     * data provider
     *
     * @return array
     */
    public function roleIdentifiersAndPackageKeysAndNames(): array
    {
        return [
            ['Neos.Flow:Everybody', 'Everybody', 'Neos.Flow', 'A role for everybody', 'The role is automatically assigned to every session'],
            ['Acme.Demo:Test', 'Test', 'Acme.Demo', 'just a label', ''],
            ['Acme.Demo.Sub:Test', 'Test', 'Acme.Demo.Sub', '', 'A descriptive description']
        ];
    }

    /**
     * @dataProvider roleIdentifiersAndPackageKeysAndNames
     * @test
     * @param string $roleIdentifier
     * @param string $name
     * @param string $packageKey
     * @param string $label
     * @param string $description
     */
    public function setNameTolePropertiesWork(string $roleIdentifier, string $name, string $packageKey, string $label, string $description): void
    {
        $role = new Role($roleIdentifier, [], $label, $description);

        self::assertEquals($name, $role->getName());
        self::assertEquals($packageKey, $role->getPackageKey());
        self::assertEquals($description, $role->getDescription());

        if ($label === '') {
            self::assertEquals($role->getName(), $role->getLabel());
        } else {
            self::assertEquals($label, $role->getLabel());
        }
    }

    /**
     * @test
     */
    public function setParentRolesMakesSureThatParentRolesDontContainDuplicates()
    {
        /** @var Role|\PHPUnit\Framework\MockObject\MockObject $role */
        $role = $this->getAccessibleMock(Role::class, ['dummy'], ['Acme.Demo:Test']);

        /** @var Role|\PHPUnit\Framework\MockObject\MockObject $parentRole1 */
        $parentRole1 = $this->getAccessibleMock(Role::class, ['dummy'], ['Acme.Demo:Parent1']);
        /** @var Role|\PHPUnit\Framework\MockObject\MockObject $parentRole2 */
        $parentRole2 = $this->getAccessibleMock(Role::class, ['dummy'], ['Acme.Demo:Parent2']);

        $parentRole2->addParentRole($parentRole1);
        $role->setParentRoles([$parentRole1, $parentRole2, $parentRole2, $parentRole1]);

        $expectedParentRoles = [
            'Acme.Demo:Parent1' => $parentRole1,
            'Acme.Demo:Parent2' => $parentRole2
        ];

        self::assertEquals(2, count($role->getParentRoles()));
        self::assertEquals($expectedParentRoles, $role->getParentRoles());
    }
}
