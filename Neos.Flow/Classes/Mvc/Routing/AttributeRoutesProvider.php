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

/**
 * Allows to annotate controller methods with route configurations
 *
 * Implementation:
 *
 * Flows routing configuration is declared via \@package, \@subpackage, \@controller and \@action (as well as the format \@format)
 * The first three options will resolve to a fully qualified class name {@see \Neos\Flow\Mvc\ActionRequest::getControllerObjectName()}
 * which is instantiated in the dispatcher {@see \Neos\Flow\Mvc\Dispatcher::dispatch()}
 *
 * The latter \@action option will be treated internally by each controller.
 * By convention and implementation of the default ActionController inside processRequest
 * {@see \Neos\Flow\Mvc\Controller\ActionController::callActionMethod()} will be used to concatenate the "Action" suffix
 * to the action name and invoke it internally with prepared method arguments.
 * The \@action is just another routing value while the doest not really know about "actions" from the "outside" (dispatcher).
 *
 * Creating routes by annotation must make a few assumptions to work.
 * As not every FQ class name is representable via the routing configuration (e.g. the class has to end with "Controller"),
 * only classes can be annotated that reside in a correct location and have the correct suffix.
 * Otherwise, an exception will be thrown as the class is not discoverable by the dispatcher.
 *
 * The routing annotation is placed at methods.
 * It is validated that the annotated method ends with "Action" and a routing value with the suffix trimmed will be generated.
 * Using the annotations on any controller makes the assumption that the controller will delegate the request to the dedicate
 * action by depending "Action".
 * This thesis is true for the ActionController.
 *
 * As discussed in https://discuss.neos.io/t/rfc-future-of-routing-mvc-in-flow/6535 we want to refactor the routing values
 * to include the fully qualified controller name, so it can be easier generated without strong restrictions.
 * Additionally, the action mapping should include its full name and be guaranteed to called.
 * Either by invoking the action in the dispatcher or by documenting this feature as part of a implementation of a ControllerInterface
 */
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
                    break;
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
