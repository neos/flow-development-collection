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
    public function roleIdentifiersAndPackageKeysAndNames()
    {
        return [
            ['Neos.Flow:Everybody', 'Everybody', 'Neos.Flow'],
            ['Acme.Demo:Test', 'Test', 'Acme.Demo'],
            ['Acme.Demo.Sub:Test', 'Test', 'Acme.Demo.Sub']
        ];
    }

    /**
     * @dataProvider roleIdentifiersAndPackageKeysAndNames
     * @test
     */
    public function setNameAndPackageKeyWorks($roleIdentifier, $name, $packageKey)
    {
        $role = new Role($roleIdentifier);

        $this->assertEquals($name, $role->getName());
        $this->assertEquals($packageKey, $role->getPackageKey());
    }

    /**
     * @test
     */
    public function setParentRolesMakesSureThatParentRolesDontContainDuplicates()
    {
        /** @var Role|\PHPUnit_Framework_MockObject_MockObject $role */
        $role = $this->getAccessibleMock(Role::class, ['dummy'], ['Acme.Demo:Test']);

        /** @var Role|\PHPUnit_Framework_MockObject_MockObject $parentRole1 */
        $parentRole1 = $this->getAccessibleMock(Role::class, ['dummy'], ['Acme.Demo:Parent1']);
        /** @var Role|\PHPUnit_Framework_MockObject_MockObject $parentRole2 */
        $parentRole2 = $this->getAccessibleMock(Role::class, ['dummy'], ['Acme.Demo:Parent2']);

        $parentRole2->addParentRole($parentRole1);
        $role->setParentRoles([$parentRole1, $parentRole2, $parentRole2, $parentRole1]);

        $expectedParentRoles = [
            'Acme.Demo:Parent1' => $parentRole1,
            'Acme.Demo:Parent2' => $parentRole2
        ];

        $this->assertEquals(2, count($role->getParentRoles()));
        $this->assertEquals($expectedParentRoles, $role->getParentRoles());
    }
}
