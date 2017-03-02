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

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Aop\AdvicesTrait;
use Neos\Flow\Aop\AspectContainer;
use Neos\Flow\Aop\PropertyIntroduction;
use Neos\Cache\Frontend\VariableFrontend;
use Neos\Flow\Log\SystemLoggerInterface;
use Neos\Flow\ObjectManagement\CompileTimeObjectManager;
use Neos\Flow\Reflection\ClassReflection;
use Neos\Flow\Reflection\PropertyReflection;
use Neos\Flow\Aop\TraitIntroduction;
use Neos\Flow\Aop;
use Neos\Flow\ObjectManagement\Proxy;
use Neos\Flow\Reflection\ReflectionService;

/**
 * The main class of the AOP (Aspect Oriented Programming) framework.
 *
 * @Flow\Proxy(false)
 * @Flow\Scope("singleton")
 */
class ProxyClassBuilder
{
    /**
     * @var Proxy\Compiler
     */
    protected $compiler;

    /**
     * The Flow settings
     * @var array
     */
    protected $settings;

    /**
     * @var ReflectionService
     */
    protected $reflectionService;

    /**
     * @var SystemLoggerInterface
     */
    protected $systemLogger;

    /**
     * An instance of the pointcut expression parser
     * @var Aop\Pointcut\PointcutExpressionParser
     */
    protected $pointcutExpressionParser;

    /**
     * @var ProxyClassBuilder
     */
    protected $proxyClassBuilder;

    /**
     * @var VariableFrontend
     */
    protected $objectConfigurationCache;

    /**
     * @var CompileTimeObjectManager
     */
    protected $objectManager;

    /**
     * Hardcoded list of Flow sub packages (first 15 characters) which must be immune to AOP proxying for security, technical or conceptual reasons.
     * @var array
     */
    protected $blacklistedSubPackages = ['Neos\Flow\Aop\\', 'Neos\Flow\Cach', 'Neos\Flow\Erro', 'Neos\Flow\Log\\', 'Neos\Flow\Moni', 'Neos\Flow\Obje', 'Neos\Flow\Pack', 'Neos\Flow\Prop', 'Neos\Flow\Refl', 'Neos\Flow\Util', 'Neos\Flow\Vali'];

    /**
     * A registry of all known aspects
     * @var array
     */
    protected $aspectContainers = [];

    /**
     * @var array
     */
    protected $methodInterceptorBuilders = [];

    /**
     * @param Proxy\Compiler $compiler
     * @return void
     */
    public function injectCompiler(Proxy\Compiler $compiler)
    {
        $this->compiler = $compiler;
    }

    /**
     * Injects the reflection service
     *
     * @param ReflectionService $reflectionService
     * @return void
     */
    public function injectReflectionService(ReflectionService $reflectionService)
    {
        $this->reflectionService = $reflectionService;
    }

    /**
     * @param SystemLoggerInterface $systemLogger
     * @return void
     */
    public function injectSystemLogger(SystemLoggerInterface $systemLogger)
    {
        $this->systemLogger = $systemLogger;
    }

    /**
     * Injects an instance of the pointcut expression parser
     *
     * @param Aop\Pointcut\PointcutExpressionParser $pointcutExpressionParser
     * @return void
     */
    public function injectPointcutExpressionParser(Aop\Pointcut\PointcutExpressionParser $pointcutExpressionParser)
    {
        $this->pointcutExpressionParser = $pointcutExpressionParser;
    }

    /**
     * Injects the cache for storing information about objects
     *
     * @param VariableFrontend $objectConfigurationCache
     * @return void
     * @Flow\Autowiring(false)
     */
    public function injectObjectConfigurationCache(VariableFrontend $objectConfigurationCache)
    {
        $this->objectConfigurationCache = $objectConfigurationCache;
    }

    /**
     * Injects the Adviced Constructor Interceptor Builder
     *
     * @param AdvicedConstructorInterceptorBuilder $builder
     * @return void
     */
    public function injectAdvicedConstructorInterceptorBuilder(AdvicedConstructorInterceptorBuilder $builder)
    {
        $this->methodInterceptorBuilders['AdvicedConstructor'] = $builder;
    }

    /**
     * Injects the Adviced Method Interceptor Builder
     *
     * @param AdvicedMethodInterceptorBuilder $builder
     * @return void
     */
    public function injectAdvicedMethodInterceptorBuilder(AdvicedMethodInterceptorBuilder $builder)
    {
        $this->methodInterceptorBuilders['AdvicedMethod'] = $builder;
    }

    /**
     * @param CompileTimeObjectManager $objectManager
     * @return void
     */
    public function injectObjectManager(CompileTimeObjectManager $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    /**
     * Injects the Flow settings
     *
     * @param array $settings The settings
     * @return void
     */
    public function injectSettings(array $settings)
    {
        $this->settings = $settings;
    }

    /**
     * Builds proxy class code which weaves advices into the respective target classes.
     *
     * The object configurations provided by the Compiler are searched for possible aspect
     * annotations. If an aspect class is found, the pointcut expressions are parsed and
     * a new aspect with one or more advisors is added to the aspect registry of the AOP framework.
     * Finally all advices are woven into their target classes by generating proxy classes.
     *
     * In general, the command neos.flow:core:compile is responsible for compilation
     * and calls this method to do so.
     *
     * In order to distinguish between an emerged / changed possible target class and
     * a class which has been matched previously but just didn't have to be proxied,
     * the latter are kept track of by an "unproxiedClass-*" cache entry.
     *
     * @return void
     */
    public function build()
    {
        $allAvailableClassNamesByPackage = $this->objectManager->getRegisteredClassNames();
        $possibleTargetClassNames = $this->getProxyableClasses($allAvailableClassNamesByPackage);
        $actualAspectClassNames = $this->reflectionService->getClassNamesByAnnotation(Flow\Aspect::class);
        sort($possibleTargetClassNames);
        sort($actualAspectClassNames);

        $this->aspectContainers = $this->buildAspectContainers($actualAspectClassNames);

        $rebuildEverything = false;
        if ($this->objectConfigurationCache->has('allAspectClassesUpToDate') === false) {
            $rebuildEverything = true;
            $this->systemLogger->log('Aspects have been modified, therefore rebuilding all target classes.', LOG_INFO);
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
                    // In case the proxy was not build because there was nothing adviced,
                    // it might be an advice in the parent and so we need to try to treat this class.
                    $treatedSubClasses = $this->addBuildMethodsAndAdvicesCodeToClass($targetClassName, $treatedSubClasses);
                }
                $treatedSubClasses = $this->proxySubClassesOfClassToEnsureAdvices($targetClassName, $targetClassNameCandidates, $treatedSubClasses);
                if ($proxyBuildResult !== false) {
                    if ($isUnproxied) {
                        $this->objectConfigurationCache->remove('unproxiedClass-' . str_replace('\\', '_', $targetClassName));
                    }
                    $this->systemLogger->log(sprintf('Built AOP proxy for class "%s".', $targetClassName), LOG_DEBUG);
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
     * @return mixed The Aop\Pointcut\Pointcut or FALSE if none was found
     */
    public function findPointcut($aspectClassName, $pointcutMethodName)
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
     * Determines which of the given classes are potentially proxyable
     * and returns their names in an array.
     *
     * @param array $classNamesByPackage Names of the classes to check
     * @return array Names of classes which can be proxied
     */
    protected function getProxyableClasses(array $classNamesByPackage)
    {
        $proxyableClasses = [];
        foreach ($classNamesByPackage as $classNames) {
            foreach ($classNames as $className) {
                if (in_array(substr($className, 0, 15), $this->blacklistedSubPackages)) {
                    continue;
                }
                if ($this->reflectionService->isClassAnnotatedWith($className, Flow\Aspect::class)) {
                    continue;
                }
                $proxyableClasses[] = $className;
            }
        }
        return $proxyableClasses;
    }

    /**
     * Checks the annotations of the specified classes for aspect tags
     * and creates an aspect with advisors accordingly.
     *
     * @param array &$classNames Classes to check for aspect tags.
     * @return array An array of Aop\AspectContainer for all aspects which were found.
     */
    protected function buildAspectContainers(array &$classNames)
    {
        $aspectContainers = [];
        foreach ($classNames as $aspectClassName) {
            $aspectContainers[$aspectClassName] = $this->buildAspectContainer($aspectClassName);
        }
        return $aspectContainers;
    }

    /**
     * Creates and returns an aspect from the annotations found in a class which
     * is tagged as an aspect. The object acting as an advice will already be
     * fetched (and therefore instantiated if necessary).
     *
     * @param  string $aspectClassName Name of the class which forms the aspect, contains advices etc.
     * @return mixed The aspect container containing one or more advisors or FALSE if no container could be built
     * @throws Aop\Exception
     */
    protected function buildAspectContainer($aspectClassName)
    {
        $aspectContainer = new AspectContainer($aspectClassName);
        $methodNames = get_class_methods($aspectClassName);

        foreach ($methodNames as $methodName) {
            foreach ($this->reflectionService->getMethodAnnotations($aspectClassName, $methodName) as $annotation) {
                $annotationClass = get_class($annotation);
                switch ($annotationClass) {
                    case Flow\Around::class:
                        $pointcutFilterComposite = $this->pointcutExpressionParser->parse($annotation->pointcutExpression, $this->renderSourceHint($aspectClassName, $methodName, $annotationClass));
                        $advice = new Aop\Advice\AroundAdvice($aspectClassName, $methodName);
                        $pointcut = new Aop\Pointcut\Pointcut($annotation->pointcutExpression, $pointcutFilterComposite, $aspectClassName);
                        $advisor = new Aop\Advisor($advice, $pointcut);
                        $aspectContainer->addAdvisor($advisor);
                        break;
                    case Flow\Before::class:
                        $pointcutFilterComposite = $this->pointcutExpressionParser->parse($annotation->pointcutExpression, $this->renderSourceHint($aspectClassName, $methodName, $annotationClass));
                        $advice = new Aop\Advice\BeforeAdvice($aspectClassName, $methodName);
                        $pointcut = new Aop\Pointcut\Pointcut($annotation->pointcutExpression, $pointcutFilterComposite, $aspectClassName);
                        $advisor = new Aop\Advisor($advice, $pointcut);
                        $aspectContainer->addAdvisor($advisor);
                        break;
                    case Flow\AfterReturning::class:
                        $pointcutFilterComposite = $this->pointcutExpressionParser->parse($annotation->pointcutExpression, $this->renderSourceHint($aspectClassName, $methodName, $annotationClass));
                        $advice = new Aop\Advice\AfterReturningAdvice($aspectClassName, $methodName);
                        $pointcut = new Aop\Pointcut\Pointcut($annotation->pointcutExpression, $pointcutFilterComposite, $aspectClassName);
                        $advisor = new Aop\Advisor($advice, $pointcut);
                        $aspectContainer->addAdvisor($advisor);
                        break;
                    case Flow\AfterThrowing::class:
                        $pointcutFilterComposite = $this->pointcutExpressionParser->parse($annotation->pointcutExpression, $this->renderSourceHint($aspectClassName, $methodName, $annotationClass));
                        $advice = new Aop\Advice\AfterThrowingAdvice($aspectClassName, $methodName);
                        $pointcut = new Aop\Pointcut\Pointcut($annotation->pointcutExpression, $pointcutFilterComposite, $aspectClassName);
                        $advisor = new Aop\Advisor($advice, $pointcut);
                        $aspectContainer->addAdvisor($advisor);
                        break;
                    case Flow\After::class:
                        $pointcutFilterComposite = $this->pointcutExpressionParser->parse($annotation->pointcutExpression, $this->renderSourceHint($aspectClassName, $methodName, $annotationClass));
                        $advice = new Aop\Advice\AfterAdvice($aspectClassName, $methodName);
                        $pointcut = new Aop\Pointcut\Pointcut($annotation->pointcutExpression, $pointcutFilterComposite, $aspectClassName);
                        $advisor = new Aop\Advisor($advice, $pointcut);
                        $aspectContainer->addAdvisor($advisor);
                        break;
                    case Flow\Pointcut::class:
                        $pointcutFilterComposite = $this->pointcutExpressionParser->parse($annotation->expression, $this->renderSourceHint($aspectClassName, $methodName, $annotationClass));
                        $pointcut = new Aop\Pointcut\Pointcut($annotation->expression, $pointcutFilterComposite, $aspectClassName, $methodName);
                        $aspectContainer->addPointcut($pointcut);
                        break;
                }
            }
        }
        $introduceAnnotation = $this->reflectionService->getClassAnnotation($aspectClassName, Flow\Introduce::class);
        if ($introduceAnnotation !== null) {
            if ($introduceAnnotation->interfaceName === null && $introduceAnnotation->traitName === null) {
                throw new Aop\Exception('The introduction in class "' . $aspectClassName . '" does neither contain an interface name nor a trait name, at least one is required.', 1172694761);
            }
            $pointcutFilterComposite = $this->pointcutExpressionParser->parse($introduceAnnotation->pointcutExpression, $this->renderSourceHint($aspectClassName, $introduceAnnotation->interfaceName, Flow\Introduce::class));
            $pointcut = new Aop\Pointcut\Pointcut($introduceAnnotation->pointcutExpression, $pointcutFilterComposite, $aspectClassName);

            if ($introduceAnnotation->interfaceName !== null) {
                $introduction = new Aop\InterfaceIntroduction($aspectClassName, $introduceAnnotation->interfaceName, $pointcut);
                $aspectContainer->addInterfaceIntroduction($introduction);
            }

            if ($introduceAnnotation->traitName !== null) {
                $introduction = new TraitIntroduction($aspectClassName, $introduceAnnotation->traitName, $pointcut);
                $aspectContainer->addTraitIntroduction($introduction);
            }
        }

        foreach ($this->reflectionService->getClassPropertyNames($aspectClassName) as $propertyName) {
            $introduceAnnotation = $this->reflectionService->getPropertyAnnotation($aspectClassName, $propertyName, Flow\Introduce::class);
            if ($introduceAnnotation !== null) {
                $pointcutFilterComposite = $this->pointcutExpressionParser->parse($introduceAnnotation->pointcutExpression, $this->renderSourceHint($aspectClassName, $propertyName, Flow\Introduce::class));
                $pointcut = new Aop\Pointcut\Pointcut($introduceAnnotation->pointcutExpression, $pointcutFilterComposite, $aspectClassName);
                $introduction = new PropertyIntroduction($aspectClassName, $propertyName, $pointcut);
                $aspectContainer->addPropertyIntroduction($introduction);
            }
        }
        if (count($aspectContainer->getAdvisors()) < 1 &&
            count($aspectContainer->getPointcuts()) < 1 &&
            count($aspectContainer->getInterfaceIntroductions()) < 1 &&
            count($aspectContainer->getTraitIntroductions()) < 1 &&
            count($aspectContainer->getPropertyIntroductions()) < 1) {
            throw new Aop\Exception('The class "' . $aspectClassName . '" is tagged to be an aspect but doesn\'t contain advices nor pointcut or introduction declarations.', 1169124534);
        }
        return $aspectContainer;
    }

    /**
     * Builds methods for a single AOP proxy class for the specified class.
     *
     * @param string $targetClassName Name of the class to create a proxy class file for
     * @param array &$aspectContainers The array of aspect containers from the AOP Framework
     * @return boolean TRUE if the proxy class could be built, FALSE otherwise.
     */
    public function buildProxyClass($targetClassName, array &$aspectContainers)
    {
        $interfaceIntroductions = $this->getMatchingInterfaceIntroductions($aspectContainers, $targetClassName);
        $introducedInterfaces = $this->getInterfaceNamesFromIntroductions($interfaceIntroductions);
        $introducedTraits = $this->getMatchingTraitNamesFromIntroductions($aspectContainers, $targetClassName);

        $propertyIntroductions = $this->getMatchingPropertyIntroductions($aspectContainers, $targetClassName);

        $methodsFromTargetClass = $this->getMethodsFromTargetClass($targetClassName);
        $methodsFromIntroducedInterfaces = $this->getIntroducedMethodsFromInterfaceIntroductions($interfaceIntroductions, $targetClassName);

        $interceptedMethods = [];
        $this->addAdvicedMethodsToInterceptedMethods($interceptedMethods, array_merge($methodsFromTargetClass, $methodsFromIntroducedInterfaces), $targetClassName, $aspectContainers);
        $this->addIntroducedMethodsToInterceptedMethods($interceptedMethods, $methodsFromIntroducedInterfaces);

        if (count($interceptedMethods) < 1 && count($introducedInterfaces) < 1 && count($propertyIntroductions) < 1) {
            return false;
        }

        $proxyClass = $this->compiler->getProxyClass($targetClassName);
        if ($proxyClass === false) {
            return false;
        }

        $proxyClass->addInterfaces($introducedInterfaces);
        $proxyClass->addTraits($introducedTraits);

        /** @var $propertyIntroduction PropertyIntroduction */
        foreach ($propertyIntroductions as $propertyIntroduction) {
            $propertyName = $propertyIntroduction->getPropertyName();
            $declaringAspectClassName = $propertyIntroduction->getDeclaringAspectClassName();
            $possiblePropertyTypes = $this->reflectionService->getPropertyTagValues($declaringAspectClassName, $propertyName, 'var');
            if (count($possiblePropertyTypes) > 0 && !$this->reflectionService->isPropertyAnnotatedWith($declaringAspectClassName, $propertyName, Flow\Transient::class)) {
                $classSchema = $this->reflectionService->getClassSchema($targetClassName);
                if ($classSchema !== null) {
                    $classSchema->addProperty($propertyName, $possiblePropertyTypes[0]);
                }
            }
            $propertyReflection = new PropertyReflection($declaringAspectClassName, $propertyName);
            $propertyReflection->setIsAopIntroduced(true);
            $this->reflectionService->reflectClassProperty($targetClassName, $propertyReflection, new ClassReflection($declaringAspectClassName));

            $proxyClass->addProperty($propertyName, var_export($propertyIntroduction->getInitialValue(), true), $propertyIntroduction->getPropertyVisibility(), $propertyIntroduction->getPropertyDocComment());
        }

        $proxyClass->getMethod('Flow_Aop_Proxy_buildMethodsAndAdvicesArray')->addPreParentCallCode("        if (method_exists(get_parent_class(), 'Flow_Aop_Proxy_buildMethodsAndAdvicesArray') && is_callable('parent::Flow_Aop_Proxy_buildMethodsAndAdvicesArray')) parent::Flow_Aop_Proxy_buildMethodsAndAdvicesArray();\n");
        $proxyClass->getMethod('Flow_Aop_Proxy_buildMethodsAndAdvicesArray')->addPreParentCallCode($this->buildMethodsAndAdvicesArrayCode($interceptedMethods));
        $proxyClass->getMethod('Flow_Aop_Proxy_buildMethodsAndAdvicesArray')->overrideMethodVisibility('protected');

        $callBuildMethodsAndAdvicesArrayCode = "\n        \$this->Flow_Aop_Proxy_buildMethodsAndAdvicesArray();\n";
        $proxyClass->getConstructor()->addPreParentCallCode($callBuildMethodsAndAdvicesArrayCode);
        $proxyClass->getMethod('__wakeup')->addPreParentCallCode($callBuildMethodsAndAdvicesArrayCode);
        $proxyClass->getMethod('__clone')->addPreParentCallCode($callBuildMethodsAndAdvicesArrayCode);

        if (!$this->reflectionService->hasMethod($targetClassName, '__wakeup')) {
            $proxyClass->getMethod('__wakeup')->addPostParentCallCode("        if (method_exists(get_parent_class(), '__wakeup') && is_callable('parent::__wakeup')) parent::__wakeup();\n");
        }

        $proxyClass->addTraits(['\\' . AdvicesTrait::class]);

        $this->buildMethodsInterceptorCode($targetClassName, $interceptedMethods);

        $proxyClass->addProperty('Flow_Aop_Proxy_targetMethodsAndGroupedAdvices', 'array()');
        $proxyClass->addProperty('Flow_Aop_Proxy_groupedAdviceChains', 'array()');
        $proxyClass->addProperty('Flow_Aop_Proxy_methodIsInAdviceMode', 'array()');

        return true;
    }

    /**
     * Makes sure that any sub classes of an adviced class also build the advices array on construction.
     *
     * @param string $className The adviced class name
     * @param ClassNameIndex $targetClassNameCandidates target class names for advices
     * @param ClassNameIndex $treatedSubClasses Already treated (sub) classes to avoid duplication
     * @return ClassNameIndex The new collection of already treated classes
     */
    protected function proxySubClassesOfClassToEnsureAdvices($className, ClassNameIndex $targetClassNameCandidates, ClassNameIndex $treatedSubClasses)
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
     */
    protected function addBuildMethodsAndAdvicesCodeToClass($className, ClassNameIndex $treatedSubClasses)
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

        $callBuildMethodsAndAdvicesArrayCode = "        if (method_exists(get_parent_class(), 'Flow_Aop_Proxy_buildMethodsAndAdvicesArray') && is_callable('parent::Flow_Aop_Proxy_buildMethodsAndAdvicesArray')) parent::Flow_Aop_Proxy_buildMethodsAndAdvicesArray();\n";
        $proxyClass->getConstructor()->addPreParentCallCode($callBuildMethodsAndAdvicesArrayCode);
        $proxyClass->getMethod('__wakeup')->addPreParentCallCode($callBuildMethodsAndAdvicesArrayCode);

        return $treatedSubClasses;
    }

    /**
     * Returns the methods of the target class.
     *
     * @param string $targetClassName Name of the target class
     * @return array Method information with declaring class and method name pairs
     */
    protected function getMethodsFromTargetClass($targetClassName)
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
    protected function buildMethodsAndAdvicesArrayCode(array $methodsAndGroupedAdvices)
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
        return  $methodsAndAdvicesArrayCode;
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
    protected function buildMethodsInterceptorCode($targetClassName, array $interceptedMethods)
    {
        foreach ($interceptedMethods as $methodName => $methodMetaInformation) {
            if (count($methodMetaInformation['groupedAdvices']) === 0) {
                throw new Aop\Exception\VoidImplementationException(sprintf('Refuse to introduce method %s into target class %s because it has no implementation code. You might want to create an around advice which implements this method.', $methodName, $targetClassName), 1303224472);
            }
            $builderType = 'Adviced' . ($methodName === '__construct' ? 'Constructor' : 'Method');
            $this->methodInterceptorBuilders[$builderType]->build($methodName, $interceptedMethods, $targetClassName);
        }
    }

    /**
     * Traverses all aspect containers, their aspects and their advisors and adds the
     * methods and their advices to the (usually empty) array of intercepted methods.
     *
     * @param array &$interceptedMethods An array (empty or not) which contains the names of the intercepted methods and additional information
     * @param array $methods An array of class and method names which are matched against the pointcut (class name = name of the class or interface the method was declared)
     * @param string $targetClassName Name of the class the pointcut should match with
     * @param array &$aspectContainers All aspects to take into consideration
     * @return void
     */
    protected function addAdvicedMethodsToInterceptedMethods(array &$interceptedMethods, array $methods, $targetClassName, array &$aspectContainers)
    {
        $pointcutQueryIdentifier = 0;

        foreach ($aspectContainers as $aspectContainer) {
            if (!$aspectContainer->getCachedTargetClassNameCandidates()->hasClassName($targetClassName)) {
                continue;
            }
            foreach ($aspectContainer->getAdvisors() as $advisor) {
                $pointcut = $advisor->getPointcut();
                foreach ($methods as $method) {
                    list($methodDeclaringClassName, $methodName) = $method;

                    if ($this->reflectionService->isMethodFinal($targetClassName, $methodName)) {
                        continue;
                    }

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
    }

    /**
     * Traverses all methods which were introduced by interfaces and adds them to the
     * intercepted methods array if they didn't exist already.
     *
     * @param array &$interceptedMethods An array (empty or not) which contains the names of the intercepted methods and additional information
     * @param array $methodsFromIntroducedInterfaces An array of class and method names from introduced interfaces
     * @return void
     */
    protected function addIntroducedMethodsToInterceptedMethods(array &$interceptedMethods, array $methodsFromIntroducedInterfaces)
    {
        foreach ($methodsFromIntroducedInterfaces as $interfaceAndMethodName) {
            list($interfaceName, $methodName) = $interfaceAndMethodName;
            if (!isset($interceptedMethods[$methodName])) {
                $interceptedMethods[$methodName]['groupedAdvices'] = [];
                $interceptedMethods[$methodName]['declaringClassName'] = $interfaceName;
            }
        }
    }

    /**
     * Traverses all aspect containers and returns an array of interface
     * introductions which match the target class.
     *
     * @param array &$aspectContainers All aspects to take into consideration
     * @param string $targetClassName Name of the class the pointcut should match with
     * @return array array of interface names
     */
    protected function getMatchingInterfaceIntroductions(array &$aspectContainers, $targetClassName)
    {
        $introductions = [];
        foreach ($aspectContainers as $aspectContainer) {
            if (!$aspectContainer->getCachedTargetClassNameCandidates()->hasClassName($targetClassName)) {
                continue;
            }
            foreach ($aspectContainer->getInterfaceIntroductions() as $introduction) {
                $pointcut = $introduction->getPointcut();
                if ($pointcut->matches($targetClassName, null, null, uniqid())) {
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
     * @param array &$aspectContainers All aspects to take into consideration
     * @param string $targetClassName Name of the class the pointcut should match with
     * @return array|PropertyIntroduction[] array of property introductions
     */
    protected function getMatchingPropertyIntroductions(array &$aspectContainers, $targetClassName)
    {
        $introductions = [];
        foreach ($aspectContainers as $aspectContainer) {
            if (!$aspectContainer->getCachedTargetClassNameCandidates()->hasClassName($targetClassName)) {
                continue;
            }
            foreach ($aspectContainer->getPropertyIntroductions() as $introduction) {
                $pointcut = $introduction->getPointcut();
                if ($pointcut->matches($targetClassName, null, null, uniqid())) {
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
     * @param array &$aspectContainers All aspects to take into consideration
     * @param string $targetClassName Name of the class the pointcut should match with
     * @return array array of trait names
     */
    protected function getMatchingTraitNamesFromIntroductions(array &$aspectContainers, $targetClassName)
    {
        $introductions = [];
        /** @var AspectContainer $aspectContainer */
        foreach ($aspectContainers as $aspectContainer) {
            if (!$aspectContainer->getCachedTargetClassNameCandidates()->hasClassName($targetClassName)) {
                continue;
            }
            /** @var TraitIntroduction $introduction */
            foreach ($aspectContainer->getTraitIntroductions() as $introduction) {
                $pointcut = $introduction->getPointcut();
                if ($pointcut->matches($targetClassName, null, null, uniqid())) {
                    $introductions[] = '\\' . $introduction->getTraitName();
                }
            }
        }

        return $introductions;
    }

    /**
     * Returns an array of interface names introduced by the given introductions
     *
     * @param array $interfaceIntroductions An array of interface introductions
     * @return array Array of interface names
     */
    protected function getInterfaceNamesFromIntroductions(array $interfaceIntroductions)
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
     * @param array $interfaceIntroductions An array of Aop\InterfaceIntroduction
     * @return array An array of method information (interface, method name)
     * @throws Aop\Exception
     */
    protected function getIntroducedMethodsFromInterfaceIntroductions(array $interfaceIntroductions)
    {
        $methods = [];
        $methodsAndIntroductions = [];
        foreach ($interfaceIntroductions as $introduction) {
            $interfaceName = $introduction->getInterfaceName();
            $methodNames = get_class_methods($interfaceName);
            if (is_array($methodNames)) {
                foreach ($methodNames as $newMethodName) {
                    if (isset($methodsAndIntroductions[$newMethodName])) {
                        throw new Aop\Exception('Method name conflict! Method "' . $newMethodName . '" introduced by "' . $introduction->getInterfaceName() . '" declared in aspect "' . $introduction->getDeclaringAspectClassName() . '" has already been introduced by "' . $methodsAndIntroductions[$newMethodName]->getInterfaceName() . '" declared in aspect "' . $methodsAndIntroductions[$newMethodName]->getDeclaringAspectClassName() . '".', 1173020942);
                    }
                    $methods[] = [$interfaceName, $newMethodName];
                    $methodsAndIntroductions[$newMethodName] = $introduction;
                }
            }
        }
        return $methods;
    }

    /**
     * Renders a short message which gives a hint on where the currently parsed pointcut expression was defined.
     *
     * @param string $aspectClassName
     * @param string $methodName
     * @param string $tagName
     * @return string
     */
    protected function renderSourceHint($aspectClassName, $methodName, $tagName)
    {
        return sprintf('%s::%s (%s advice)', $aspectClassName, $methodName, $tagName);
    }
}
