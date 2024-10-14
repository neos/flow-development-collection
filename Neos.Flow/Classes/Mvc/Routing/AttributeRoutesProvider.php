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

use Neos\Flow\Mvc\Controller\ActionController;
use Neos\Flow\Mvc\Exception\InvalidActionNameException;
use Neos\Flow\Mvc\Routing\Exception\InvalidControllerException;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\Reflection\ReflectionService;
use Neos\Flow\Annotations as Flow;
use Neos\Utility\Arrays;

/**
 * Allows to annotate controller methods with route configurations
 *
 * Internal implementation:
 * -----------------------
 *
 * Flows routing configuration is declared via \@package, \@subpackage, \@controller and \@action
 * The first three options will resolve to a fully qualified class name {@see \Neos\Flow\Mvc\ActionRequest::getControllerObjectName()}
 * which is instantiated in the dispatcher {@see \Neos\Flow\Mvc\Dispatcher::dispatch()}
 *
 * The latter \@action option will be treated internally by each controller. From the perspective of the dispatcher \@action is just another routing value.
 * By convention during processRequest in the default ActionController {@see \ActionController::resolveActionMethodName()} will be used
 * to concatenate the "Action" suffix to the action name
 * and {@see ActionController::callActionMethod()} will invoke the method internally with prepared method arguments.
 *
 * Creating routes by annotation must make a few assumptions to work:
 *
 * 1. As not every FQ class name is representable via the routing configuration (e.g. the class has to end with "Controller"),
 * only classes can be annotated that reside in a correct location and have the correct suffix.
 * Otherwise, an exception will be thrown as the class is not discoverable by the dispatcher.
 *
 * 2. As the ActionController requires a little magic and is the main use case we currently only support this controller.
 * For that reason it is validated that the annotation is inside an ActionController and the method ends with "Action".
 * The routing value with the suffix trimmed will be generated:
 *
 *     class MyThingController extends ActionController
 *     {
 *         #[Flow\Route(path: 'foo')]
 *         public function someAction()
 *         {
 *         }
 *     }
 *
 * The example will genrate the configuration:
 *
 *     \@package My.Package
 *     \@controller MyThing
 *     \@action some
 *
 * TODO for a future scope of `Flow\Action` see {@link https://github.com/neos/flow-development-collection/issues/3335}
 */
final class AttributeRoutesProvider implements RoutesProviderInterface
{
    /**
     * @param array<string> $classNames
     */
    public function __construct(
        public readonly ObjectManagerInterface $objectManager,
        public readonly array $classNames,
    ) {
    }

    public function getRoutes(): Routes
    {
        $routes = [];
        foreach (static::compileRoutesConfiguration($this->objectManager) as $className => $routesForClass) {
            $includeClassName = false;
            foreach ($this->classNames as $classNamePattern) {
                if (fnmatch($classNamePattern, $className, FNM_NOESCAPE)) {
                    $includeClassName = true;
                    break;
                }
            }
            if (!$includeClassName) {
                continue;
            }

            $routes = [...$routes, ...$routesForClass];
        }

        $routes = array_map(static fn (array $routeConfiguration): Route => Route::fromConfiguration($routeConfiguration), $routes);
        return Routes::create(...$routes);
    }

    /**
     * @param ObjectManagerInterface $objectManager
     * @return array<string, array<int, mixed>>
     * @throws InvalidActionNameException
     * @throws InvalidControllerException
     * @throws \Neos\Flow\Utility\Exception
     * @throws \Neos\Utility\Exception\FilesException
     * @throws \ReflectionException
     */
    #[Flow\CompileStatic]
    public static function compileRoutesConfiguration(ObjectManagerInterface $objectManager): array
    {
        $reflectionService = $objectManager->get(ReflectionService::class);

        $routesByClassName = [];
        $annotatedClasses = $reflectionService->getClassesContainingMethodsAnnotatedWith(Flow\Route::class);

        foreach ($annotatedClasses as $className) {
            if (!in_array(ActionController::class, class_parents($className), true)) {
                throw new InvalidControllerException('TODO: Currently #[Flow\Route] is only supported for ActionController. See https://github.com/neos/flow-development-collection/issues/3335.');
            }

            $controllerObjectName = $objectManager->getCaseSensitiveObjectName($className);
            $controllerPackageKey = $objectManager->getPackageKeyByObjectName($controllerObjectName);
            $controllerPackageNamespace = str_replace('.', '\\', $controllerPackageKey);
            if (!str_ends_with($className, 'Controller')) {
                throw new InvalidControllerException('Only for controller classes');
            }

            $localClassName = substr($className, strlen($controllerPackageNamespace) + 1);

            if (str_starts_with($localClassName, 'Controller\\')) {
                $controllerName = substr($localClassName, 11);
                $subPackage = null;
            } elseif (str_contains($localClassName, '\\Controller\\')) {
                [$subPackage, $controllerName] = explode('\\Controller\\', $localClassName);
            } else {
                throw new InvalidControllerException('Unknown controller pattern');
            }

            $routesByClassName[$className] = [];
            $annotatedMethods = $reflectionService->getMethodsAnnotatedWith($className, Flow\Route::class);
            foreach ($annotatedMethods as $methodName) {
                if (!str_ends_with($methodName, 'Action')) {
                    throw new InvalidActionNameException('Only for action methods');
                }
                $annotations = $reflectionService->getMethodAnnotations($className, $methodName, Flow\Route::class);
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
                        $routesByClassName[$className][] = $configuration;
                    }
                }
            }
        }

        return $routesByClassName;
    }
}
