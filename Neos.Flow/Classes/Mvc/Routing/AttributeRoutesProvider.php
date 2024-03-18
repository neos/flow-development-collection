<?php

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\Flow\Mvc\Routing;

use Neos\Flow\Mvc\Exception\InvalidActionNameException;
use Neos\Flow\Mvc\Routing\Exception\InvalidControllerException;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\Reflection\ReflectionService;
use Neos\Flow\Annotations as Flow;
use Neos\Utility\Arrays;

final class AttributeRoutesProvider implements RoutesProviderInterface
{
    /**
     * @param array<string> $classNames
     */
    public function __construct(
        public readonly ReflectionService $reflectionService,
        public readonly ObjectManagerInterface $objectManager,
        public readonly array $classNames,
    ) {
    }

    public function getRoutes(): Routes
    {
        $routes = [];
        $annotatedClasses = $this->reflectionService->getClassesContainingMethodsAnnotatedWith(Flow\Route::class);

        foreach ($annotatedClasses as $className) {
            $includeClassName = false;
            foreach ($this->classNames as $classNamePattern) {
                if (fnmatch($classNamePattern, $className, FNM_NOESCAPE)) {
                    $includeClassName = true;
                }
            }
            if (!$includeClassName) {
                continue;
            }
            $controllerObjectName = $this->objectManager->getCaseSensitiveObjectName($className);
            $controllerPackageKey = $this->objectManager->getPackageKeyByObjectName($controllerObjectName);
            $controllerPackageNamespace = str_replace('.', '\\', $controllerPackageKey);
            if (!str_ends_with($className, 'Controller')) {
                throw new InvalidControllerException('Only for controller classes');
            }

            $localClassName = substr($className, strlen($controllerPackageNamespace) + 1);

            if (str_starts_with($localClassName, 'Controller\\')) {
                $controllerName = substr($localClassName, 11);
                $subPackage = null;
            } elseif (str_contains($localClassName, '\\Controller\\')) {
                list($subPackage, $controllerName) = explode('\\Controller\\', $localClassName);
            } else {
                throw new InvalidControllerException('Unknown controller pattern');
            }

            $annotatedMethods = $this->reflectionService->getMethodsAnnotatedWith($className, Flow\Route::class);
            foreach ($annotatedMethods as $methodName) {
                if (!str_ends_with($methodName, 'Action')) {
                    throw new InvalidActionNameException('Only for action methods');
                }
                $annotations = $this->reflectionService->getMethodAnnotations($className, $methodName, Flow\Route::class);
                foreach ($annotations as $annotation) {
                    if ($annotation instanceof Flow\Route) {
                        $controller = substr($controllerName, 0, -10);
                        $action = substr($methodName, 0, -6);

                        $configuration = [
                            'name' => $controllerPackageKey . ' :: ' . $controller . ' :: ' . ($annotation->name ?: $action),
                            'uriPattern' => $annotation->uriPattern,
                            'httpMethods' => $annotation->httpMethods,
                            'defaults' => Arrays::arrayMergeRecursiveOverrule(
                                [
                                    '@package' => $controllerPackageKey,
                                    '@subpackage' => $subPackage,
                                    '@controller' => $controller,
                                    '@action' => $action,
                                    '@format' => 'html'
                                ],
                                $annotation->defaults ?? []
                            )
                        ];
                        $routes[] = Route::fromConfiguration($configuration);
                    }
                }
            }
        }
        return Routes::create(...$routes);
    }
}
