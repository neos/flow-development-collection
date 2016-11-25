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

use Neos\Flow\Configuration\ConfigurationManager;
use Neos\Flow\ObjectManagement\ObjectManager;
use Neos\Flow\Security\Authorization\Privilege\AbstractPrivilege;
use Neos\Flow\Security\Authorization\Privilege\PrivilegeTarget;
use Neos\Flow\Security\Policy\PolicyService;
use Neos\Flow\Security\Policy\Role;
use Neos\Flow\Tests\UnitTestCase;

/**
 * Testcase for for the PolicyService
 */
class PolicyServiceTest extends UnitTestCase
{
    /**
     * @var PolicyService
     */
    protected $policyService;

    /**
     * @var array
     */
    protected $mockPolicyConfiguration = [];

    /**
     * @var ConfigurationManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $mockConfigurationManager;

    /**
     * @var ObjectManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $mockObjectManager;

    /**
     * @var AbstractPrivilege|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $mockPrivilege;

    public function setUp()
    {
        $this->policyService = new PolicyService();

        $this->mockConfigurationManager = $this->getMockBuilder(ConfigurationManager::class)->disableOriginalConstructor()->getMock();
        $this->mockConfigurationManager->expects($this->any())->method('getConfiguration')->with(ConfigurationManager::CONFIGURATION_TYPE_POLICY)->will($this->returnCallback(function () {
            return $this->mockPolicyConfiguration;
        }));
        $this->inject($this->policyService, 'configurationManager', $this->mockConfigurationManager);

        $this->mockObjectManager = $this->getMockBuilder(ObjectManager::class)->disableOriginalConstructor()->getMock();
        $this->inject($this->policyService, 'objectManager', $this->mockObjectManager);

        $this->mockPrivilege = $this->getAccessibleMock(AbstractPrivilege::class, ['matchesSubject'], [], '', false);
    }

    /**
     * @test
     */
    public function hasRoleReturnsFalseIfTheSpecifiedRoleIsNotConfigured()
    {
        $this->assertFalse($this->policyService->hasRole('Non.Existing:Role'));
    }

    /**
     * @test
     */
    public function hasRoleReturnsTrueIfTheSpecifiedRoleIsConfigured()
    {
        $this->mockPolicyConfiguration = [
            'roles' => [
                'Some.Package:SomeRole' => [],
            ],
        ];
        $this->assertTrue($this->policyService->hasRole('Some.Package:SomeRole'));
    }

    /**
     * @test
     * @expectedException \Neos\Flow\Security\Exception\NoSuchRoleException
     */
    public function getRoleThrowsExceptionIfTheSpecifiedRoleIsNotConfigured()
    {
        $this->policyService->getRole('Non.Existing:Role');
    }

    /**
     * @test
     */
    public function getRoleReturnsTheSpecifiedRole()
    {
        $this->mockPolicyConfiguration = [
            'roles' => [
                'Some.Package:SomeRole' => [
                    'abstract' => true,
                ],
                'Some.Package:SomeOtherRole' => [
                    'parentRoles' => ['Some.Package:SomeRole'],
                ],
            ],
        ];
        $role = $this->policyService->getRole('Some.Package:SomeOtherRole');
        $this->assertInstanceOf(Role::class, $role);
        $this->assertSame('Some.Package:SomeOtherRole', $role->getIdentifier());
        $this->assertSame('Some.Package:SomeRole', $role->getParentRoles()['Some.Package:SomeRole']->getIdentifier());
    }

    /**
     * @test
     */
    public function getRolesExcludesAbstractRolesByDefault()
    {
        $this->mockPolicyConfiguration = [
            'roles' => [
                'Some.Package:SomeRole' => [
                    'abstract' => true,
                ],
                'Some.Package:SomeOtherRole' => [
                    'parentRoles' => ['Some.Package:SomeRole'],
                ],
            ],
        ];
        $roles = $this->policyService->getRoles();
        $this->assertSame(['Some.Package:SomeOtherRole'], array_keys($roles));
    }

    /**
     * @test
     */
    public function getRolesIncludesAbstractRolesIfRequested()
    {
        $this->mockPolicyConfiguration = [
            'roles' => [
                'Some.Package:SomeRole' => [
                    'abstract' => true,
                ],
                'Some.Package:SomeOtherRole' => [
                    'parentRoles' => ['Some.Package:SomeRole'],
                ],
            ],
        ];
        $roles = $this->policyService->getRoles(true);
        $this->assertSame(['Some.Package:SomeRole', 'Some.Package:SomeOtherRole', 'Neos.Flow:Everybody'], array_keys($roles));
    }

    /**
     * @test
     */
    public function getAllPrivilegesByTypeReturnsAnEmptyArrayIfNoMatchingPrivilegesAreConfigured()
    {
        $this->assertSame([], $this->policyService->getAllPrivilegesByType('SomeNonExistingPrivilegeType'));
    }

    /**
     * @test
     */
    public function getAllPrivilegesByTypeReturnsAllConfiguredPrivilegesOfThatType()
    {
        $mockPrivilegeClassName = get_class($this->mockPrivilege);
        $this->mockPolicyConfiguration = [
            'privilegeTargets' => [
                $mockPrivilegeClassName => [
                    'Some.PrivilegeTarget:Identifier' => [
                        'matcher' => 'someMatcher()',
                    ],
                ],
            ],
        ];
        $this->assertCount(1, $this->policyService->getAllPrivilegesByType($mockPrivilegeClassName));
        $this->assertInstanceOf($mockPrivilegeClassName, $this->mockPrivilege, $this->policyService->getAllPrivilegesByType($mockPrivilegeClassName));
    }

    /**
     * @test
     */
    public function getPrivilegeTargetsReturnsAnEmptyArrayIfNoPrivilegeTargetsAreConfigured()
    {
        $this->assertSame([], $this->policyService->getPrivilegeTargets());
    }

    /**
     * @test
     */
    public function getPrivilegeTargetsReturnsAllConfiguredPrivilegeTargets()
    {
        $mockPrivilegeClassName = get_class($this->mockPrivilege);
        $this->mockPolicyConfiguration = [
            'privilegeTargets' => [
                $mockPrivilegeClassName => [
                    'Some.PrivilegeTarget:Identifier' => [
                        'matcher' => 'someMatcher()',
                    ],
                ],
            ],
        ];
        $this->assertCount(1, $this->policyService->getPrivilegeTargets());
        $this->assertSame('Some.PrivilegeTarget:Identifier', $this->policyService->getPrivilegeTargets()['Some.PrivilegeTarget:Identifier']->getIdentifier());
    }

    /**
     * @test
     */
    public function getPrivilegeTargetByIdentifierReturnsAnNullIfNoPrivilegeTargetIsConfigured()
    {
        $this->assertNull($this->policyService->getPrivilegeTargetByIdentifier('SomeNonExistingPrivilegeTarget'));
    }

    /**
     * @test
     */
    public function getPrivilegeTargetByIdentifierReturnsTheConfiguredPrivilegeTarget()
    {
        $mockPrivilegeClassName = get_class($this->mockPrivilege);
        $this->mockPolicyConfiguration = [
            'privilegeTargets' => [
                $mockPrivilegeClassName => [
                    'Some.PrivilegeTarget:Identifier' => [
                        'matcher' => 'someMatcher()',
                    ],
                ],
            ],
        ];

        $privilegeTarget = $this->policyService->getPrivilegeTargetByIdentifier('Some.PrivilegeTarget:Identifier');
        $this->assertInstanceOf(PrivilegeTarget::class, $privilegeTarget);
        $this->assertSame('Some.PrivilegeTarget:Identifier', $privilegeTarget->getIdentifier());
    }

    /**
     * @test
     */
    public function everybodyRoleGetsAnAbstainPrivilegeForAllConfiguredPrivilegeTargets()
    {
        $mockPrivilegeClassName = get_class($this->mockPrivilege);
        $this->mockPolicyConfiguration = [
            'privilegeTargets' => [
                $mockPrivilegeClassName => [
                    'Some.PrivilegeTarget:Identifier' => [
                        'matcher' => 'someMatcher()',
                    ],
                    'Some.OtherPrivilegeTarget:Identifier' => [
                        'matcher' => 'someMatcher()',
                    ],
                ],
            ],
        ];

        $everybodyRole = $this->policyService->getRole('Neos.Flow:Everybody');
        $this->assertCount(2, $everybodyRole->getPrivileges());
        $this->assertTrue($everybodyRole->getPrivilegeForTarget('Some.PrivilegeTarget:Identifier')->isAbstained());
        $this->assertTrue($everybodyRole->getPrivilegeForTarget('Some.OtherPrivilegeTarget:Identifier')->isAbstained());
    }

    /**
     * @test
     */
    public function everybodyRoleCanHaveExplicitGrants()
    {
        $mockPrivilegeClassName = get_class($this->mockPrivilege);
        $this->mockPolicyConfiguration = [
            'privilegeTargets' => [
                $mockPrivilegeClassName => [
                    'Some.PrivilegeTarget:Identifier' => [
                        'matcher' => 'someMatcher()',
                    ],
                    'Some.OtherPrivilegeTarget:Identifier' => [
                        'matcher' => 'someMatcher()',
                    ],
                ],
            ],
            'roles' => [
                'Neos.Flow:Everybody' => [
                    'privileges' => [
                        [
                            'privilegeTarget' => 'Some.PrivilegeTarget:Identifier',
                            'permission' => 'GRANT',
                        ]
                    ],
                ],
                'Some.Other:Role' => [
                    'privileges' => [
                        [
                            'privilegeTarget' => 'Some.PrivilegeTarget:Identifier',
                            'permission' => 'DENY',
                        ]
                    ],
                ],
            ],
        ];

        $everybodyRole = $this->policyService->getRole('Neos.Flow:Everybody');
        $this->assertTrue($everybodyRole->getPrivilegeForTarget('Some.PrivilegeTarget:Identifier')->isGranted());
    }

    /**
     * @test
     */
    public function everybodyRoleCanHaveExplicitDenies()
    {
        $mockPrivilegeClassName = get_class($this->mockPrivilege);
        $this->mockPolicyConfiguration = [
            'privilegeTargets' => [
                $mockPrivilegeClassName => [
                    'Some.PrivilegeTarget:Identifier' => [
                        'matcher' => 'someMatcher()',
                    ],
                    'Some.OtherPrivilegeTarget:Identifier' => [
                        'matcher' => 'someMatcher()',
                    ],
                ],
            ],
            'roles' => [
                'Neos.Flow:Everybody' => [
                    'privileges' => [
                        [
                            'privilegeTarget' => 'Some.PrivilegeTarget:Identifier',
                            'permission' => 'DENY',
                        ]
                    ],
                ],
                'Some.Other:Role' => [
                    'privileges' => [
                        [
                            'privilegeTarget' => 'Some.PrivilegeTarget:Identifier',
                            'permission' => 'GRANT',
                        ]
                    ],
                ],
            ],
        ];

        $everybodyRole = $this->policyService->getRole('Neos.Flow:Everybody');
        $this->assertTrue($everybodyRole->getPrivilegeForTarget('Some.PrivilegeTarget:Identifier')->isDenied());
    }
}
