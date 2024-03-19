<?php
declare(strict_types=1);

namespace Neos\Flow\Security\Policy;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Reflection\ReflectionService;
use Neos\Flow\Security\Authorization\Privilege\Method\MethodPrivilege;

class PolicyAnnotationService
{
    public function __construct(
        public readonly ReflectionService $reflectionService
    ) {
    }

    /**
     * Add policy configuration for Flow\Policy annotations and attributes
     */
    public function ammendPolicyConfiguration(array &$policyConfiguration): void
    {
        $annotatedClasses = $this->reflectionService->getClassesContainingMethodsAnnotatedWith(Flow\Policy::class);
        foreach ($annotatedClasses as $className) {
            $annotatedMethods = $this->reflectionService->getMethodsAnnotatedWith($className, Flow\Policy::class);
            // avoid methods beeing called multiple times when attributes are assigned more than once
            $annotatedMethods = array_unique($annotatedMethods);
            foreach ($annotatedMethods as $methodName) {
                /**
                 * @var Flow\Policy[] $annotations
                 */
                $annotations = $this->reflectionService->getMethodAnnotations($className, $methodName, Flow\Policy::class);
                $privilegeTargetMatcher = sprintf('method(%s->%s())', $className, $methodName);
                $privilegeTargetIdentifier = 'FromPhpAttribute:' . (str_replace('\\', '.', $className)) . ':'. $methodName . ':'. md5($privilegeTargetMatcher);
                $policyConfiguration['privilegeTargets'][MethodPrivilege::class][$privilegeTargetIdentifier] = ['matcher' => $privilegeTargetMatcher];
                foreach ($annotations as $annotation) {
                    $policyConfiguration['roles'][$annotation->role]['privileges'][] = [
                        'privilegeTarget' => $privilegeTargetIdentifier,
                        'permission' => $annotation->permission
                    ];
                }
            }
        }
    }
}
