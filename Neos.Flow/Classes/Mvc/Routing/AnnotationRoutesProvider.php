<?php

declare(strict_types=1);

namespace Neos\Flow\Mvc\Routing;

use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\Reflection\ReflectionService;
use Neos\Flow\Annotations as Flow;
use Neos\Utility\Arrays;

/**
 * @Flow\Scope("singleton")
 */
class AnnotationRoutesProvider implements RoutesProviderInterface
{
    public function __construct(
        public readonly ReflectionService $reflectionService,
        public readonly ObjectManagerInterface $objectManager,
    ) {
    }

    public function getRoutes(): Routes
    {
        $routes = [];
        $annotatedClasses = $this->reflectionService->getClassesContainingMethodsAnnotatedWith(Flow\Route::class);
        foreach ($annotatedClasses as $className) {
            $controllerObjectName = $this->objectManager->getCaseSensitiveObjectName($className);
            $controllerPackageKey = $this->objectManager->getPackageKeyByObjectName($controllerObjectName);
            $controllerPackageNamespace = str_replace('.', '\\', $controllerPackageKey);
            if (!str_ends_with($className, 'Controller')) {
                throw new \Exception('only for controller classes');
            }
            if (!str_starts_with($className, $controllerPackageNamespace . '\\')) {
                throw new \Exception('only for classes in package namespace');
            }

            $localClassName = substr($className, strlen($controllerPackageNamespace) + 1);

            if (str_starts_with($localClassName, 'Controller\\')) {
                $controllerName = substr($localClassName, 11);
                $subPackage = null;
            } elseif (str_contains($localClassName, '\\Controller\\')) {
                list($subPackage, $controllerName) = explode('\\Controller\\', $localClassName);
            } else {
                throw new \Exception('unknown controller pattern');
            }

            $annotatedMethods = $this->reflectionService->getMethodsAnnotatedWith($className, Flow\Route::class);
            // @todo remove once reflectionService handles multiple annotations properly
            $annotatedMethods = array_unique($annotatedMethods);
            foreach ($annotatedMethods as $methodName) {
                if (!str_ends_with($methodName, 'Action')) {
                    throw new \Exception('only for action methods');
                }
                $annotations = $this->reflectionService->getMethodAnnotations($className, $methodName, Flow\Route::class);
                foreach ($annotations as $annotation) {
                    if ($annotation instanceof Flow\Route) {
                        $configuration = [
                            'uriPattern' => $annotation->uriPattern,
                            'defaults' => Arrays::arrayMergeRecursiveOverrule(
                                [
                                    '@package' => $controllerPackageKey,
                                    '@subpackage' => $subPackage,
                                    '@controller' => substr($controllerName, 0, -10),
                                    '@action' => substr($methodName, 0, -6),
                                    '@format' => 'html'
                                ],
                                $annotation->defaults ?? []
                            )
                        ];
                        if ($annotation->name !== null) {
                            $configuration['name'] = $annotation->name;
                        }
                        if ($annotation->httpMethods !== null) {
                            $configuration['httpMethods'] = $annotation->httpMethods;
                        }
                        $routes[] = Route::fromConfiguration($configuration);
                    }
                }
            }
        }
        return Routes::create(...$routes);
    }
}
