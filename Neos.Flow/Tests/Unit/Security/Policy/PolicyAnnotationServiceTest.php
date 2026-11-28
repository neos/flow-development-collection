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

use Neos\Flow\Annotations\Policy;
use Neos\Flow\Reflection\ReflectionService;
use Neos\Flow\Security\Authorization\Privilege\Method\MethodPrivilege;
use Neos\Flow\Security\Authorization\Privilege\PrivilegeInterface;
use Neos\Flow\Security\Policy\PolicyAnnotationService;
use Neos\Flow\Tests\UnitTestCase;

/**
 * Testcase for the PolicyAnnotationService
 */
class PolicyAnnotationServiceTest extends UnitTestCase
{
    /**
     * @var PolicyAnnotationService
     */
    protected $policyAnnotationService;

    /**
     * @var ReflectionService|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $mockReflectionService;

    protected function setUp(): void
    {
        $this->mockReflectionService = $this->getMockBuilder(ReflectionService::class)->disableOriginalConstructor()->getMock();
        $this->policyAnnotationService = new PolicyAnnotationService(
            $this->mockReflectionService
        );
    }

    /**
     * @test
     */
    public function policyConfigurationIsNotModifiedIfNoAnnotationsAreFound()
    {
        $this->mockReflectionService->expects($this->once())
            ->method('getClassesContainingMethodsAnnotatedWith')
            ->with(Policy::class)
            ->willReturn([]);

        $policyConfiguration = [];

        $this->policyAnnotationService->ammendPolicyConfiguration($policyConfiguration);

        $this->assertSame(
            [],
            $policyConfiguration,
        );
    }

    /**
     * @test
     */
    public function policyConfigurationIsCreatedForAnnotationsCreated()
    {
        $this->mockReflectionService->expects($this->once())
            ->method('getClassesContainingMethodsAnnotatedWith')
            ->with(Policy::class)
            ->willReturn(['Vendor\Example']);

        $this->mockReflectionService->expects($this->once())
            ->method('getMethodsAnnotatedWith')
            ->with('Vendor\Example', Policy::class)
            ->willReturn(['annotatedMethod']);

        $this->mockReflectionService->expects($this->once())
            ->method('getMethodAnnotations')
            ->with('Vendor\Example', 'annotatedMethod', Policy::class)
            ->willReturn([new Policy('Neos.Flow:Administrator'), new Policy('Neos.Flow:Anonymous', PrivilegeInterface::DENY)]);

        $policyConfiguration = [];

        $this->policyAnnotationService->ammendPolicyConfiguration($policyConfiguration);
        $expectedTargetId = 'FromPhpAttribute:Vendor.Example:annotatedMethod:' . md5('method(Vendor\Example->annotatedMethod())');

        $this->assertSame(
            [
                'privilegeTargets' => [
                    MethodPrivilege::class => [
                        $expectedTargetId => [
                            'matcher' => 'method(Vendor\Example->annotatedMethod())'
                        ]
                    ]
                ],
                'roles' => [
                    'Neos.Flow:Administrator' => ['privileges' => [['privilegeTarget'=> $expectedTargetId, 'permission' => 'grant']]],
                    'Neos.Flow:Anonymous' => ['privileges' => [['privilegeTarget'=> $expectedTargetId, 'permission' => 'deny']]]
                ]
            ],
            $policyConfiguration,
        );
    }
}
