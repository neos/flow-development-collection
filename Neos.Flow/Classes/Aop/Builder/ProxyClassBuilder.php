<?php
namespace Neos\Flow\Aop\Builder;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Cache\Frontend\VariableFrontend;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Aop;
use Neos\Flow\Aop\AdvicesTrait;
use Neos\Flow\Aop\AspectContainer;
use Neos\Flow\Aop\Exception;
use Neos\Flow\Aop\Exception\InvalidPointcutExpressionException;
use Neos\Flow\Aop\Exception\InvalidTargetClassException;
use Neos\Flow\Aop\Exception\VoidImplementationException;
use Neos\Flow\Aop\Pointcut\Pointcut;
use Neos\Flow\Aop\PropertyIntroduction;
use Neos\Flow\Aop\TraitIntroduction;
use Neos\Flow\Log\Utility\LogEnvironment;
use Neos\Flow\ObjectManagement\CompileTimeObjectManager;
use Neos\Flow\ObjectManagement\Exception\CannotBuildObjectException;
use Neos\Flow\ObjectManagement\Proxy\Compiler;
use Neos\Flow\ObjectManagement\Proxy\ProxyMethodGenerator;
use Neos\Flow\Reflection\Exception\ClassLoadingForReflectionFailedException;
use Neos\Flow\Reflection\Exception\InvalidClassException;
use Neos\Flow\Reflection\PropertyReflection;
use Neos\Flow\Reflection\ReflectionService;
use Neos\Flow\Utility\Algorithms;
use Psr\Log\LoggerInterface;

/**
 * The main class of the AOP (Aspect Oriented Programming) framework.
 */
#[Flow\Proxy(false)]
#[Flow\Scope("singleton")]
class ProxyClassBuilder
{
    protected Compiler $compiler;
    protected ReflectionService $reflectionService;
    protected LoggerInterface $logger;
    protected VariableFrontend $objectConfigurationCache;
    protected CompileTimeObjectManager $objectManager;
    protected AspectContainerBuilder $aspectContainerBuilder;

    /**
     * A registry of all known aspects
     * @var AspectContainer[]
     */
    protected array $aspectContainers = [];

    protected array $methodInterceptorBuilders = [];

    public function injectCompiler(Compiler $compiler): void
    {
        $this->compiler = $compiler;
    }

    public function injectReflectionService(ReflectionService $reflectionService): void
    {
        $this->reflectionService = $reflectionService;
    }

    #[Flow\Autowiring(false)]
    public function injectLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    #[Flow\Autowiring(false)]
    public function injectObjectConfigurationCache(VariableFrontend $objectConfigurationCache): void
    {
        $this->objectConfigurationCache = $objectConfigurationCache;
    }

    public function injectAdvisedConstructorInterceptorBuilder(AdvisedConstructorInterceptorBuilder $builder): void
    {
        $this->methodInterceptorBuilders['AdvisedConstructor'] = $builder;
    }

    public function injectAdvisedMethodInterceptorBuilder(AdvisedMethodInterceptorBuilder $builder): void
    {
        $this->methodInterceptorBuilders['AdvisedMethod'] = $builder;
    }

    public function injectObjectManager(CompileTimeObjectManager $objectManager): void
    {
        $this->objectManager = $objectManager;
    }

    public function injectAspectContainerBuilder(AspectContainerBuilder $aspectContainerBuilder): void
    {
        $this->aspectContainerBuilder = $aspectContainerBuilder;
    }

    /**
     * Builds proxy class code which weaves advices into the respective target classes.
     *
     * The object configurations provided by the Compiler are searched for possible aspect
     * annotations. If an aspect class is found, the pointcut expressions are parsed and
     * a new aspect with one or more advisors is added to the aspect registry of the AOP framework.
     * Finally, all advices are woven into their target classes by generating proxy classes.
     *
     * In general, the command neos.flow:core:compile is responsible for compilation
     * and calls this method to do so.
     *
     * In order to distinguish between an emerged / changed possible target class and
     * a class which has been matched previously but just didn't have to be proxied,
     * the latter are kept track of by an "unproxiedClass-*" cache entry.
     *
     * @throws CannotBuildObjectException
     * @throws ClassLoadingForReflectionFailedException
     * @throws Exception
     * @throws InvalidTargetClassException
     * @throws InvalidClassException
     * @throws InvalidPointcutExpressionException
     * @throws VoidImplementationException
     * @throws \Neos\Cache\Exception
     * @throws \ReflectionException
     */
    public function build(): void
    {
        $allAvailableClassNamesByPackage = $this->objectManager->getRegisteredClassNames();


        $this->aspectContainers = $this->aspectContainerBuilder->buildFromReflection();

        $proxyableClassesFilter = new ProxyableClassesFilter();
        $possibleTargetClassNames = $proxyableClassesFilter->getProxyableClasses($allAvailableClassNamesByPackage, array_keys($this->aspectContainers));

        $rebuildEverything = false;
        if ($this->objectConfigurationCache->has('allAspectClassesUpToDate') === false) {
            $rebuildEverything = true;
            $this->logger->info('Aspects have been modified, therefore rebuilding all target classes.', LogEnvironment::fromMethodName(__METHOD__));
            $this->objectConfigurationCache->set('allAspectClassesUpToDate', true);
        }

        $possibleTargetClassNameIndex = new ClassNameIndex();
        $possibleTargetClassNameIndex->setClassNames($possibleTargetClassNames);

        $targetClassNameCandidates = new ClassNameIndex();
        foreach ($this->aspectContainers as $aspectContainer) {
            $targetClassNameCandidates->applyUnion($aspectContainer->reduceTargetClassNames($possibleTargetClassNameIndex));
        }
        $targetClassNameCandidates->sort();

        $treatedSubClasses = new ClassNameIndex();

        foreach ($targetClassNameCandidates->getClassNames() as $targetClassName) {
            $isUnproxied = $this->objectConfigurationCache->has('unproxiedClass-' . str_replace('\\', '_', $targetClassName));
            $hasCacheEntry = $this->compiler->hasCacheEntryForClass($targetClassName) || $isUnproxied;
            if ($rebuildEverything === true || $hasCacheEntry === false) {
                $proxyBuildResult = $this->buildProxyClass($targetClassName, $this->aspectContainers);
                if ($proxyBuildResult === false) {
                    // In case the proxy was not built because there was nothing advised,
                    // it might be an advice in the parent, and so we need to try to treat this class.
                    $treatedSubClasses = $this->addBuildMethodsAndAdvicesCodeToClass($targetClassName, $treatedSubClasses);
                }
                $treatedSubClasses = $this->proxySubClassesOfClassToEnsureAdvices($targetClassName, $targetClassNameCandidates, $treatedSubClasses);
                if ($proxyBuildResult !== false) {
                    if ($isUnproxied) {
                        $this->objectConfigurationCache->remove('unproxiedClass-' . str_replace('\\', '_', $targetClassName));
                    }
                    $this->logger->debug(sprintf('Built AOP proxy for class "%s".', $targetClassName));
                } else {
                    $this->objectConfigurationCache->set('unproxiedClass-' . str_replace('\\', '_', $targetClassName), true);
                }
            }
        }
    }

    /**
     * Traverses the aspect containers to find a pointcut from the aspect class name
     * and pointcut method name
     *
     * @param string $aspectClassName Name of the aspect class where the pointcut has been declared
     * @param string $pointcutMethodName Method name of the pointcut
     * @return Pointcut|false The Pointcut or false if none was found
     */
    public function findPointcut(string $aspectClassName, string $pointcutMethodName): Pointcut|false
    {
        if (!isset($this->aspectContainers[$aspectClassName])) {
            return false;
        }
        foreach ($this->aspectContainers[$aspectClassName]->getPointcuts() as $pointcut) {
            if ($pointcut->getPointcutMethodName() === $pointcutMethodName) {
                return $pointcut;
            }
        }
        return false;
    }

    /**
     * Builds methods for a single AOP proxy class for the specified class.
     *
     * @param class-string $targetClassName Name of the class to create a proxy class file for
     * @param array $aspectContainers The array of aspect containers from the AOP Framework
     * @return bool true if the proxy class could be built, false otherwise.
     * @throws \ReflectionException
     * @throws CannotBuildObjectException
     * @throws VoidImplementationException
     * @throws Exception
     * @throws \Exception
     */
    public function buildProxyClass(string $targetClassName, array $aspectContainers): bool
    {
        $interfaceIntroductions = $this->getMatchingInterfaceIntroductions($aspectContainers, $targetClassName);
        $introducedInterfaces = $this->getInterfaceNamesFromIntroductions($interfaceIntroductions);
        $introducedTraits = $this->getMatchingTraitNamesFromIntroductions($aspectContainers, $targetClassName);

        $propertyIntroductions = $this->getMatchingPropertyIntroductions($aspectContainers, $targetClassName);

        $methodsFromTargetClass = $this->getMethodsFromTargetClass($targetClassName);
        $methodsFromIntroducedInterfaces = $this->getIntroducedMethodsFromInterfaceIntroductions($interfaceIntroductions);

        $interceptedMethods = [];
        $interceptedMethods = $this->addAdvisedMethodsToInterceptedMethods($interceptedMethods, array_merge($methodsFromTargetClass, $methodsFromIntroducedInterfaces), $targetClassName, $aspectContainers);
        $interceptedMethods = $this->addIntroducedMethodsToInterceptedMethods($interceptedMethods, $methodsFromIntroducedInterfaces);

        if (count($interceptedMethods) < 1 && count($introducedInterfaces) < 1 && count($introducedTraits) < 1 && count($propertyIntroductions) < 1) {
            return false;
        }

        $proxyClass = $this->compiler->getProxyClass($targetClassName);
        if ($proxyClass === false) {
            return false;
        }

        $proxyClass->addInterfaces($introducedInterfaces);
        $proxyClass->addTraits($introducedTraits);

        foreach ($propertyIntroductions as $propertyIntroduction) {
            $propertyName = $propertyIntroduction->getPropertyName();
            $declaringAspectClassName = $propertyIntroduction->getDeclaringAspectClassName();
            $possiblePropertyTypes = $this->reflectionService->getPropertyTagValues($declaringAspectClassName, $propertyName, 'var');
            if (count($possiblePropertyTypes) > 0 && !$this->reflectionService->isPropertyAnnotatedWith($declaringAspectClassName, $propertyName, Flow\Transient::class)) {
                $classSchema = $this->reflectionService->getClassSchema($targetClassName);
                $classSchema?->addProperty($propertyName, $possiblePropertyTypes[0]);
            }
            $propertyReflection = new PropertyReflection($declaringAspectClassName, $propertyName);
            $propertyReflection->setIsAopIntroduced(true);
            $this->reflectionService->reflectClassProperty($targetClassName, $propertyReflection);

            $proxyClass->addProperty($propertyName, var_export($propertyIntroduction->getInitialValue(), true), $propertyIntroduction->getPropertyVisibility(), $propertyIntroduction->getPropertyDocComment());
        }

        $proxyClass->getMethod('Flow_Aop_Proxy_buildMethodsAndAdvicesArray')->addPreParentCallCode("        if (method_exists(parent::class, 'Flow_Aop_Proxy_buildMethodsAndAdvicesArray') && is_callable([parent::class, 'Flow_Aop_Proxy_buildMethodsAndAdvicesArray'])) parent::Flow_Aop_Proxy_buildMethodsAndAdvicesArray();\n");
        $proxyClass->getMethod('Flow_Aop_Proxy_buildMethodsAndAdvicesArray')->addPreParentCallCode($this->buildMethodsAndAdvicesArrayCode($interceptedMethods));
        $proxyClass->getMethod('Flow_Aop_Proxy_buildMethodsAndAdvicesArray')->setVisibility(ProxyMethodGenerator::VISIBILITY_PROTECTED);

        $callBuildMethodsAndAdvicesArrayCode = "\n        \$this->Flow_Aop_Proxy_buildMethodsAndAdvicesArray();\n";
        $proxyClass->getConstructor()->addPreParentCallCode($callBuildMethodsAndAdvicesArrayCode);
        $proxyClass->getMethod('__wakeup')->addPreParentCallCode($callBuildMethodsAndAdvicesArrayCode);
        $proxyClass->getMethod('__clone')->addPreParentCallCode($callBuildMethodsAndAdvicesArrayCode);

        if (!$this->reflectionService->hasMethod($targetClassName, '__wakeup')) {
            $proxyClass->getMethod('__wakeup')->addPostParentCallCode(<<<PHP
            if (method_exists(parent::class, '__wakeup') && is_callable([parent::class, '__wakeup'])) parent::__wakeup();
            PHP);
        }
        $proxyClass->addTraits(['\\' . AdvicesTrait::class]);

        $this->buildMethodsInterceptorCode($targetClassName, $interceptedMethods);

        $proxyClass->addProperty('Flow_Aop_Proxy_targetMethodsAndGroupedAdvices', 'array()');
        $proxyClass->addProperty('Flow_Aop_Proxy_groupedAdviceChains', 'array()');
        $proxyClass->addProperty('Flow_Aop_Proxy_methodIsInAdviceMode', 'array()');

        return true;
    }

    /**
     * Makes sure that any subclasses of an advised class also build the advices array on construction.
     *
     * @param class-string $className The advised class name
     * @param ClassNameIndex $targetClassNameCandidates target class names for advices
     * @param ClassNameIndex $treatedSubClasses Already treated (sub) classes to avoid duplication
     * @return ClassNameIndex The new collection of already treated classes
     * @throws CannotBuildObjectException
     * @throws \ReflectionException
     */
    protected function proxySubClassesOfClassToEnsureAdvices(string $className, ClassNameIndex $targetClassNameCandidates, ClassNameIndex $treatedSubClasses): ClassNameIndex
    {
        if ($this->reflectionService->isClassReflected($className) === false) {
            return $treatedSubClasses;
        }
        if (trait_exists($className)) {
            return $treatedSubClasses;
        }
        if (interface_exists($className)) {
            return $treatedSubClasses;
        }

        $subClassNames = $this->reflectionService->getAllSubClassNamesForClass($className);
        foreach ($subClassNames as $subClassName) {
            if ($targetClassNameCandidates->hasClassName($subClassName)) {
                continue;
            }

            $treatedSubClasses = $this->addBuildMethodsAndAdvicesCodeToClass($subClassName, $treatedSubClasses);
        }

        return $treatedSubClasses;
    }

    /**
     * Adds code to build the methods and advices array in case the parent class has some.
     *
     * @param string $className
     * @param ClassNameIndex $treatedSubClasses
     * @return ClassNameIndex
     * @throws \ReflectionException
     * @throws CannotBuildObjectException
     */
    protected function addBuildMethodsAndAdvicesCodeToClass(string $className, ClassNameIndex $treatedSubClasses): ClassNameIndex
    {
        if ($treatedSubClasses->hasClassName($className)) {
            return $treatedSubClasses;
        }

        $treatedSubClasses = $treatedSubClasses->union(new ClassNameIndex([$className]));
        if ($this->reflectionService->isClassReflected($className) === false) {
            return $treatedSubClasses;
        }

        $proxyClass = $this->compiler->getProxyClass($className);
        if ($proxyClass === false) {
            return $treatedSubClasses;
        }

        $callBuildMethodsAndAdvicesArrayCode = "        if (method_exists(parent::class, 'Flow_Aop_Proxy_buildMethodsAndAdvicesArray') && is_callable([parent::class, 'Flow_Aop_Proxy_buildMethodsAndAdvicesArray'])) parent::Flow_Aop_Proxy_buildMethodsAndAdvicesArray();\n";
        $proxyClass->getConstructor()->addPreParentCallCode($callBuildMethodsAndAdvicesArrayCode);
        $proxyClass->getMethod('__wakeup')->addPreParentCallCode($callBuildMethodsAndAdvicesArrayCode);

        return $treatedSubClasses;
    }

    /**
     * Returns the methods of the target class.
     *
     * @param class-string $targetClassName Name of the target class
     * @return array<array{0: class-string, 1: string}> Method information with declaring class and method name pairs
     * @throws \ReflectionException
     */
    protected function getMethodsFromTargetClass(string $targetClassName): array
    {
        $methods = [];
        $class = new \ReflectionClass($targetClassName);

        foreach (['__construct', '__clone'] as $builtInMethodName) {
            if (!$class->hasMethod($builtInMethodName)) {
                $methods[] = [$targetClassName, $builtInMethodName];
            }
        }

        foreach ($class->getMethods() as $method) {
            $methods[] = [$targetClassName, $method->getName()];
        }

        return $methods;
    }

    /**
     * Creates code for an array of target methods and their advices.
     *
     * Example:
     *
     * 	$this->Flow_Aop_Proxy_targetMethodsAndGroupedAdvices = array(
     * 		'getSomeProperty' => array(
     * 			\Neos\Flow\Aop\Advice\AroundAdvice::class => array(
     * 				new \Neos\Flow\Aop\Advice\AroundAdvice(\Neos\Foo\SomeAspect::class, 'aroundAdvice', \Neos\Flow\Core\Bootstrap::$staticObjectManager, function() { ... }),
     * 			),
     * 		),
     * 	);
     *
     *
     * @param array $methodsAndGroupedAdvices An array of method names and grouped advice objects
     * @return string PHP code for the content of an array of target method names and advice objects
     * @see buildProxyClass()
     */
    protected function buildMethodsAndAdvicesArrayCode(array $methodsAndGroupedAdvices): string
    {
        if (count($methodsAndGroupedAdvices) < 1) {
            return '';
        }

        $methodsAndAdvicesArrayCode = "\n        \$objectManager = \\Neos\\Flow\\Core\\Bootstrap::\$staticObjectManager;\n";
        $methodsAndAdvicesArrayCode .= "        \$this->Flow_Aop_Proxy_targetMethodsAndGroupedAdvices = array(\n";
        foreach ($methodsAndGroupedAdvices as $methodName => $advicesAndDeclaringClass) {
            $methodsAndAdvicesArrayCode .= "            '" . $methodName . "' => array(\n";
            foreach ($advicesAndDeclaringClass['groupedAdvices'] as $adviceType => $adviceConfigurations) {
                $methodsAndAdvicesArrayCode .= "                '" . $adviceType . "' => array(\n";
                foreach ($adviceConfigurations as $adviceConfiguration) {
                    $advice = $adviceConfiguration['advice'];
                    $methodsAndAdvicesArrayCode .= "                    new \\" . get_class($advice) . "('" . $advice->getAspectObjectName() . "', '" . $advice->getAdviceMethodName() . "', \$objectManager, " . $adviceConfiguration['runtimeEvaluationsClosureCode'] . "),\n";
                }
                $methodsAndAdvicesArrayCode .= "                ),\n";
            }
            $methodsAndAdvicesArrayCode .= "            ),\n";
        }
        $methodsAndAdvicesArrayCode .= "        );\n";
        return $methodsAndAdvicesArrayCode;
    }

    /**
     * Traverses all intercepted methods and their advices and builds PHP code to intercept
     * methods if necessary.
     *
     * The generated code is added directly to the proxy class by calling the respective
     * methods of the Compiler API.
     *
     * @param string $targetClassName The target class the pointcut should match with
     * @param array $interceptedMethods An array of method names which need to be intercepted
     * @return void
     * @throws Aop\Exception\VoidImplementationException
     */
    protected function buildMethodsInterceptorCode(string $targetClassName, array $interceptedMethods): void
    {
        foreach ($interceptedMethods as $methodName => $methodMetaInformation) {
            if (count($methodMetaInformation['groupedAdvices']) === 0) {
                throw new Aop\Exception\VoidImplementationException(sprintf('Refuse to introduce method %s into target class %s because it has no implementation code. You might want to create an around advice which implements this method.', $methodName, $targetClassName), 1303224472);
            }
            $builderType = 'Advised' . ($methodName === '__construct' ? 'Constructor' : 'Method');
            $this->methodInterceptorBuilders[$builderType]->build($methodName, $interceptedMethods, $targetClassName);
        }
    }

    /**
     * Traverses all aspect containers, their aspects and their advisors and adds the
     * methods and their advices to the (usually empty) array of intercepted methods.
     *
     * @param array $interceptedMethods An array (empty or not) which contains the names of the intercepted methods and additional information
     * @param array<array{0: class-string, 1: string}> $methods An array of class and method names which are matched against the pointcut (class name = name of the class or interface the method was declared)
     * @param class-string $targetClassName Name of the class the pointcut should match with
     * @param AspectContainer[] $aspectContainers All aspects to take into consideration
     * @return array
     */
    protected function addAdvisedMethodsToInterceptedMethods(array $interceptedMethods, array $methods, string $targetClassName, array $aspectContainers): array
    {
        $pointcutQueryIdentifier = 0;

        foreach ($aspectContainers as $aspectContainer) {
            if (!$aspectContainer->getCachedTargetClassNameCandidates()->hasClassName($targetClassName)) {
                continue;
            }
            foreach ($aspectContainer->getAdvisors() as $advisor) {
                $pointcut = $advisor->getPointcut();
                foreach ($methods as $method) {
                    [$methodDeclaringClassName, $methodName] = $method;

                    if ($this->reflectionService->isMethodStatic($targetClassName, $methodName)) {
                        continue;
                    }

                    if ($pointcut->matches($targetClassName, $methodName, $methodDeclaringClassName, $pointcutQueryIdentifier)) {
                        $advice = $advisor->getAdvice();
                        $interceptedMethods[$methodName]['groupedAdvices'][get_class($advice)][] = [
                            'advice' => $advice,
                            'runtimeEvaluationsClosureCode' => $pointcut->getRuntimeEvaluationsClosureCode()
                        ];
                        $interceptedMethods[$methodName]['declaringClassName'] = $methodDeclaringClassName;
                    }
                    $pointcutQueryIdentifier++;
                }
            }
        }

        return $interceptedMethods;
    }

    /**
     * Traverses all methods which were introduced by interfaces and adds them to the
     * intercepted methods array if they didn't exist already.
     *
     * @param array $interceptedMethods An array (empty or not) which contains the names of the intercepted methods and additional information
     * @param array<array{0: class-string, 1: string}> $methodsFromIntroducedInterfaces An array of class and method names from introduced interfaces
     * @return array
     */
    protected function addIntroducedMethodsToInterceptedMethods(array $interceptedMethods, array $methodsFromIntroducedInterfaces): array
    {
        foreach ($methodsFromIntroducedInterfaces as $interfaceAndMethodName) {
            [$interfaceName, $methodName] = $interfaceAndMethodName;
            if (!isset($interceptedMethods[$methodName])) {
                $interceptedMethods[$methodName]['groupedAdvices'] = [];
                $interceptedMethods[$methodName]['declaringClassName'] = $interfaceName;
            }
        }

        return $interceptedMethods;
    }

    /**
     * Traverses all aspect containers and returns an array of interface
     * introductions which match the target class.
     *
     * @param AspectContainer[] $aspectContainers All aspects to take into consideration
     * @param class-string $targetClassName Name of the class the pointcut should match with
     * @return Aop\InterfaceIntroduction[] array of interface names
     * @throws \Exception
     */
    protected function getMatchingInterfaceIntroductions(array $aspectContainers, string $targetClassName): array
    {
        $introductions = [];
        foreach ($aspectContainers as $aspectContainer) {
            if (!$aspectContainer->getCachedTargetClassNameCandidates()->hasClassName($targetClassName)) {
                continue;
            }
            foreach ($aspectContainer->getInterfaceIntroductions() as $introduction) {
                $pointcut = $introduction->getPointcut();
                if ($pointcut->matches($targetClassName, null, null, Algorithms::generateRandomString(13))) {
                    $introductions[] = $introduction;
                }
            }
        }
        return $introductions;
    }

    /**
     * Traverses all aspect containers and returns an array of property
     * introductions which match the target class.
     *
     * @param AspectContainer[] $aspectContainers All aspects to take into consideration
     * @param string $targetClassName Name of the class the pointcut should match with
     * @return array|PropertyIntroduction[] array of property introductions
     * @throws \Exception
     */
    protected function getMatchingPropertyIntroductions(array $aspectContainers, string $targetClassName): array
    {
        $introductions = [];
        foreach ($aspectContainers as $aspectContainer) {
            if (!$aspectContainer->getCachedTargetClassNameCandidates()->hasClassName($targetClassName)) {
                continue;
            }
            foreach ($aspectContainer->getPropertyIntroductions() as $introduction) {
                $pointcut = $introduction->getPointcut();
                if ($pointcut->matches($targetClassName, null, null, Algorithms::generateRandomString(13))) {
                    $introductions[] = $introduction;
                }
            }
        }
        return $introductions;
    }

    /**
     * Traverses all aspect containers and returns an array of trait
     * introductions which match the target class.
     *
     * @param AspectContainer[] $aspectContainers All aspects to take into consideration
     * @param string $targetClassName Name of the class the pointcut should match with
     * @return string[] array of trait names
     * @throws \Exception
     */
    protected function getMatchingTraitNamesFromIntroductions(array $aspectContainers, string $targetClassName): array
    {
        $introductions = [];
        foreach ($aspectContainers as $aspectContainer) {
            if (!$aspectContainer->getCachedTargetClassNameCandidates()->hasClassName($targetClassName)) {
                continue;
            }
            /** @var TraitIntroduction $introduction */
            foreach ($aspectContainer->getTraitIntroductions() as $introduction) {
                $pointcut = $introduction->getPointcut();
                if ($pointcut->matches($targetClassName, null, null, Algorithms::generateRandomString(13))) {
                    $introductions[] = '\\' . $introduction->getTraitName();
                }
            }
        }

        return $introductions;
    }

    /**
     * Returns an array of interface names introduced by the given introductions
     *
     * @param Aop\InterfaceIntroduction[] $interfaceIntroductions An array of interface introductions
     * @return string[] Array of interface names
     */
    protected function getInterfaceNamesFromIntroductions(array $interfaceIntroductions): array
    {
        $interfaceNames = [];
        foreach ($interfaceIntroductions as $introduction) {
            $interfaceNames[] = '\\' . $introduction->getInterfaceName();
        }
        return $interfaceNames;
    }

    /**
     * Returns all methods declared by the introduced interfaces
     *
     * @param Aop\InterfaceIntroduction[] $interfaceIntroductions An array of Aop\InterfaceIntroduction
     * @return array<int, array{0: class-string, 1: string}> An array of method information (interface, method name)
     * @throws Aop\Exception
     */
    protected function getIntroducedMethodsFromInterfaceIntroductions(array $interfaceIntroductions): array
    {
        $methods = [];
        $methodsAndIntroductions = [];
        foreach ($interfaceIntroductions as $introduction) {
            $interfaceName = $introduction->getInterfaceName();
            $methodNames = get_class_methods($interfaceName);
            foreach ($methodNames as $newMethodName) {
                if (isset($methodsAndIntroductions[$newMethodName])) {
                    throw new Aop\Exception('Method name conflict! Method "' . $newMethodName . '" introduced by "' . $introduction->getInterfaceName() . '" declared in aspect "' . $introduction->getDeclaringAspectClassName() . '" has already been introduced by "' . $methodsAndIntroductions[$newMethodName]->getInterfaceName() . '" declared in aspect "' . $methodsAndIntroductions[$newMethodName]->getDeclaringAspectClassName() . '".', 1173020942);
                }
                $methods[] = [$interfaceName, $newMethodName];
                $methodsAndIntroductions[$newMethodName] = $introduction;
            }
        }
        return $methods;
    }
}
