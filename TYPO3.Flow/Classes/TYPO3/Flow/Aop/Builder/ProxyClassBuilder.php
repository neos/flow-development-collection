<?php
namespace TYPO3\Flow\Aop\Builder;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Aop\AspectContainer;
use TYPO3\Flow\Aop\PropertyIntroduction;
use TYPO3\Flow\Reflection\ClassReflection;
use TYPO3\Flow\Reflection\PropertyReflection;
use TYPO3\Flow\Aop\TraitIntroduction;

/**
 * The main class of the AOP (Aspect Oriented Programming) framework.
 *
 * @Flow\Proxy(false)
 * @Flow\Scope("singleton")
 */
class ProxyClassBuilder
{
    /**
     * @var \TYPO3\Flow\Object\Proxy\Compiler
     */
    protected $compiler;

    /**
     * The Flow settings
     * @var array
     */
    protected $settings;

    /**
     * @var \TYPO3\Flow\Reflection\ReflectionService
     */
    protected $reflectionService;

    /**
     * @var \TYPO3\Flow\Log\SystemLoggerInterface
     */
    protected $systemLogger;

    /**
     * An instance of the pointcut expression parser
     * @var \TYPO3\Flow\Aop\Pointcut\PointcutExpressionParser
     */
    protected $pointcutExpressionParser;

    /**
     * @var \TYPO3\Flow\Aop\Builder\ProxyClassBuilder
     */
    protected $proxyClassBuilder;

    /**
     * @var \TYPO3\Flow\Cache\Frontend\VariableFrontend
     */
    protected $objectConfigurationCache;

    /**
     * @var \TYPO3\Flow\Object\CompileTimeObjectManager
     */
    protected $objectManager;

    /**
     * Hardcoded list of Flow sub packages (first 15 characters) which must be immune to AOP proxying for security, technical or conceptual reasons.
     * @var array
     */
    protected $blacklistedSubPackages = array('TYPO3\Flow\Aop\\', 'TYPO3\Flow\Cach', 'TYPO3\Flow\Erro', 'TYPO3\Flow\Log\\', 'TYPO3\Flow\Moni', 'TYPO3\Flow\Obje', 'TYPO3\Flow\Pack', 'TYPO3\Flow\Prop', 'TYPO3\Flow\Refl', 'TYPO3\Flow\Util', 'TYPO3\Flow\Vali');

    /**
     * A registry of all known aspects
     * @var array
     */
    protected $aspectContainers = array();

    /**
     * @var array
     */
    protected $methodInterceptorBuilders = array();

    /**
     * @param \TYPO3\Flow\Object\Proxy\Compiler $compiler
     * @return void
     */
    public function injectCompiler(\TYPO3\Flow\Object\Proxy\Compiler $compiler)
    {
        $this->compiler = $compiler;
    }

    /**
     * Injects the reflection service
     *
     * @param \TYPO3\Flow\Reflection\ReflectionService $reflectionService
     * @return void
     */
    public function injectReflectionService(\TYPO3\Flow\Reflection\ReflectionService $reflectionService)
    {
        $this->reflectionService = $reflectionService;
    }

    /**
     * @param \TYPO3\Flow\Log\SystemLoggerInterface $systemLogger
     * @return void
     */
    public function injectSystemLogger(\TYPO3\Flow\Log\SystemLoggerInterface $systemLogger)
    {
        $this->systemLogger = $systemLogger;
    }

    /**
     * Injects an instance of the pointcut expression parser
     *
     * @param \TYPO3\Flow\Aop\Pointcut\PointcutExpressionParser $pointcutExpressionParser
     * @return void
     */
    public function injectPointcutExpressionParser(\TYPO3\Flow\Aop\Pointcut\PointcutExpressionParser $pointcutExpressionParser)
    {
        $this->pointcutExpressionParser = $pointcutExpressionParser;
    }

    /**
     * Injects the cache for storing information about objects
     *
     * @param \TYPO3\Flow\Cache\Frontend\VariableFrontend $objectConfigurationCache
     * @return void
     * @Flow\Autowiring(false)
     */
    public function injectObjectConfigurationCache(\TYPO3\Flow\Cache\Frontend\VariableFrontend $objectConfigurationCache)
    {
        $this->objectConfigurationCache = $objectConfigurationCache;
    }

    /**
     * Injects the Adviced Constructor Interceptor Builder
     *
     * @param \TYPO3\Flow\Aop\Builder\AdvicedConstructorInterceptorBuilder $builder
     * @return void
     */
    public function injectAdvicedConstructorInterceptorBuilder(\TYPO3\Flow\Aop\Builder\AdvicedConstructorInterceptorBuilder $builder)
    {
        $this->methodInterceptorBuilders['AdvicedConstructor'] = $builder;
    }

    /**
     * Injects the Adviced Method Interceptor Builder
     *
     * @param \TYPO3\Flow\Aop\Builder\AdvicedMethodInterceptorBuilder $builder
     * @return void
     */
    public function injectAdvicedMethodInterceptorBuilder(\TYPO3\Flow\Aop\Builder\AdvicedMethodInterceptorBuilder $builder)
    {
        $this->methodInterceptorBuilders['AdvicedMethod'] = $builder;
    }

    /**
     * @param \TYPO3\Flow\Object\CompileTimeObjectManager $objectManager
     * @return void
     */
    public function injectObjectManager(\TYPO3\Flow\Object\CompileTimeObjectManager $objectManager)
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
     * In general, the command typo3.flow:core:compile is responsible for compilation
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
        $actualAspectClassNames = $this->reflectionService->getClassNamesByAnnotation(\TYPO3\Flow\Annotations\Aspect::class);
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

        foreach ($targetClassNameCandidates->getClassNames() as $targetClassName) {
            $isUnproxied = $this->objectConfigurationCache->has('unproxiedClass-' . str_replace('\\', '_', $targetClassName));
            $hasCacheEntry = $this->compiler->hasCacheEntryForClass($targetClassName) || $isUnproxied;
            if ($rebuildEverything === true || $hasCacheEntry === false) {
                $proxyBuildResult = $this->buildProxyClass($targetClassName, $this->aspectContainers);
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
     * @return mixed The \TYPO3\Flow\Aop\Pointcut\Pointcut or FALSE if none was found
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
        $proxyableClasses = array();
        foreach ($classNamesByPackage as $classNames) {
            foreach ($classNames as $className) {
                if (!in_array(substr($className, 0, 15), $this->blacklistedSubPackages)) {
                    if (!$this->reflectionService->isClassAnnotatedWith($className, \TYPO3\Flow\Annotations\Aspect::class) &&
                        !$this->reflectionService->isClassFinal($className)) {
                        $proxyableClasses[] = $className;
                    }
                }
            }
        }
        return $proxyableClasses;
    }

    /**
     * Checks the annotations of the specified classes for aspect tags
     * and creates an aspect with advisors accordingly.
     *
     * @param array &$classNames Classes to check for aspect tags.
     * @return array An array of \TYPO3\Flow\Aop\AspectContainer for all aspects which were found.
     */
    protected function buildAspectContainers(array &$classNames)
    {
        $aspectContainers = array();
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
     * @throws \TYPO3\Flow\Aop\Exception
     */
    protected function buildAspectContainer($aspectClassName)
    {
        $aspectContainer = new \TYPO3\Flow\Aop\AspectContainer($aspectClassName);
        $methodNames = get_class_methods($aspectClassName);

        foreach ($methodNames as $methodName) {
            foreach ($this->reflectionService->getMethodAnnotations($aspectClassName, $methodName) as $annotation) {
                $annotationClass = get_class($annotation);
                switch ($annotationClass) {
                    case \TYPO3\Flow\Annotations\Around::class:
                        $pointcutFilterComposite = $this->pointcutExpressionParser->parse($annotation->pointcutExpression, $this->renderSourceHint($aspectClassName, $methodName, $annotationClass));
                        $advice = new \TYPO3\Flow\Aop\Advice\AroundAdvice($aspectClassName, $methodName);
                        $pointcut = new \TYPO3\Flow\Aop\Pointcut\Pointcut($annotation->pointcutExpression, $pointcutFilterComposite, $aspectClassName);
                        $advisor = new \TYPO3\Flow\Aop\Advisor($advice, $pointcut);
                        $aspectContainer->addAdvisor($advisor);
                    break;
                    case \TYPO3\Flow\Annotations\Before::class:
                        $pointcutFilterComposite = $this->pointcutExpressionParser->parse($annotation->pointcutExpression, $this->renderSourceHint($aspectClassName, $methodName, $annotationClass));
                        $advice = new \TYPO3\Flow\Aop\Advice\BeforeAdvice($aspectClassName, $methodName);
                        $pointcut = new \TYPO3\Flow\Aop\Pointcut\Pointcut($annotation->pointcutExpression, $pointcutFilterComposite, $aspectClassName);
                        $advisor = new \TYPO3\Flow\Aop\Advisor($advice, $pointcut);
                        $aspectContainer->addAdvisor($advisor);
                    break;
                    case \TYPO3\Flow\Annotations\AfterReturning::class:
                        $pointcutFilterComposite = $this->pointcutExpressionParser->parse($annotation->pointcutExpression, $this->renderSourceHint($aspectClassName, $methodName, $annotationClass));
                        $advice = new \TYPO3\Flow\Aop\Advice\AfterReturningAdvice($aspectClassName, $methodName);
                        $pointcut = new \TYPO3\Flow\Aop\Pointcut\Pointcut($annotation->pointcutExpression, $pointcutFilterComposite, $aspectClassName);
                        $advisor = new \TYPO3\Flow\Aop\Advisor($advice, $pointcut);
                        $aspectContainer->addAdvisor($advisor);
                    break;
                    case \TYPO3\Flow\Annotations\AfterThrowing::class:
                        $pointcutFilterComposite = $this->pointcutExpressionParser->parse($annotation->pointcutExpression, $this->renderSourceHint($aspectClassName, $methodName, $annotationClass));
                        $advice = new \TYPO3\Flow\Aop\Advice\AfterThrowingAdvice($aspectClassName, $methodName);
                        $pointcut = new \TYPO3\Flow\Aop\Pointcut\Pointcut($annotation->pointcutExpression, $pointcutFilterComposite, $aspectClassName);
                        $advisor = new \TYPO3\Flow\Aop\Advisor($advice, $pointcut);
                        $aspectContainer->addAdvisor($advisor);
                    break;
                    case \TYPO3\Flow\Annotations\After::class:
                        $pointcutFilterComposite = $this->pointcutExpressionParser->parse($annotation->pointcutExpression, $this->renderSourceHint($aspectClassName, $methodName, $annotationClass));
                        $advice = new \TYPO3\Flow\Aop\Advice\AfterAdvice($aspectClassName, $methodName);
                        $pointcut = new \TYPO3\Flow\Aop\Pointcut\Pointcut($annotation->pointcutExpression, $pointcutFilterComposite, $aspectClassName);
                        $advisor = new \TYPO3\Flow\Aop\Advisor($advice, $pointcut);
                        $aspectContainer->addAdvisor($advisor);
                    break;
                    case \TYPO3\Flow\Annotations\Pointcut::class:
                        $pointcutFilterComposite = $this->pointcutExpressionParser->parse($annotation->expression, $this->renderSourceHint($aspectClassName, $methodName, $annotationClass));
                        $pointcut = new \TYPO3\Flow\Aop\Pointcut\Pointcut($annotation->expression, $pointcutFilterComposite, $aspectClassName, $methodName);
                        $aspectContainer->addPointcut($pointcut);
                    break;
                }
            }
        }
        $introduceAnnotation = $this->reflectionService->getClassAnnotation($aspectClassName, \TYPO3\Flow\Annotations\Introduce::class);
        if ($introduceAnnotation !== null) {
            if ($introduceAnnotation->interfaceName === null && $introduceAnnotation->traitName === null) {
                throw new \TYPO3\Flow\Aop\Exception('The introduction in class "' . $aspectClassName . '" does neither contain an interface name nor a trait name, at least one is required.', 1172694761);
            }
            $pointcutFilterComposite = $this->pointcutExpressionParser->parse($introduceAnnotation->pointcutExpression, $this->renderSourceHint($aspectClassName, $introduceAnnotation->interfaceName, \TYPO3\Flow\Annotations\Introduce::class));
            $pointcut = new \TYPO3\Flow\Aop\Pointcut\Pointcut($introduceAnnotation->pointcutExpression, $pointcutFilterComposite, $aspectClassName);

            if ($introduceAnnotation->interfaceName !== null) {
                $introduction = new \TYPO3\Flow\Aop\InterfaceIntroduction($aspectClassName, $introduceAnnotation->interfaceName, $pointcut);
                $aspectContainer->addInterfaceIntroduction($introduction);
            }

            if ($introduceAnnotation->traitName !== null) {
                $introduction = new \TYPO3\Flow\Aop\TraitIntroduction($aspectClassName, $introduceAnnotation->traitName, $pointcut);
                $aspectContainer->addTraitIntroduction($introduction);
            }
        }

        foreach ($this->reflectionService->getClassPropertyNames($aspectClassName) as $propertyName) {
            $introduceAnnotation = $this->reflectionService->getPropertyAnnotation($aspectClassName, $propertyName, \TYPO3\Flow\Annotations\Introduce::class);
            if ($introduceAnnotation !== null) {
                $pointcutFilterComposite = $this->pointcutExpressionParser->parse($introduceAnnotation->pointcutExpression, $this->renderSourceHint($aspectClassName, $propertyName, \TYPO3\Flow\Annotations\Introduce::class));
                $pointcut = new \TYPO3\Flow\Aop\Pointcut\Pointcut($introduceAnnotation->pointcutExpression, $pointcutFilterComposite, $aspectClassName);
                $introduction = new PropertyIntroduction($aspectClassName, $propertyName, $pointcut);
                $aspectContainer->addPropertyIntroduction($introduction);
            }
        }
        if (count($aspectContainer->getAdvisors()) < 1 &&
            count($aspectContainer->getPointcuts()) < 1 &&
            count($aspectContainer->getInterfaceIntroductions()) < 1 &&
            count($aspectContainer->getTraitIntroductions()) < 1 &&
            count($aspectContainer->getPropertyIntroductions()) < 1) {
            throw new \TYPO3\Flow\Aop\Exception('The class "' . $aspectClassName . '" is tagged to be an aspect but doesn\'t contain advices nor pointcut or introduction declarations.', 1169124534);
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

        $interceptedMethods = array();
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
            if (count($possiblePropertyTypes) > 0 && !$this->reflectionService->isPropertyAnnotatedWith($declaringAspectClassName, $propertyName, \TYPO3\Flow\Annotations\Transient::class)) {
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

        if (!$this->reflectionService->hasMethod($targetClassName, '__wakeup')) {
            $proxyClass->getMethod('__wakeup')->addPostParentCallCode("        if (method_exists(get_parent_class(), '__wakeup') && is_callable('parent::__wakeup')) parent::__wakeup();\n");
        }

        $proxyClass->addTraits(['\TYPO3\Flow\Object\Proxy\DoctrineProxyFixingTrait', '\TYPO3\Flow\Aop\AdvicesTrait']);

        $this->buildMethodsInterceptorCode($targetClassName, $interceptedMethods);

        $proxyClass->addProperty('Flow_Aop_Proxy_targetMethodsAndGroupedAdvices', 'array()');
        $proxyClass->addProperty('Flow_Aop_Proxy_groupedAdviceChains', 'array()');
        $proxyClass->addProperty('Flow_Aop_Proxy_methodIsInAdviceMode', 'array()');

        return true;
    }

    /**
     * Returns the methods of the target class.
     *
     * @param string $targetClassName Name of the target class
     * @return array Method information with declaring class and method name pairs
     */
    protected function getMethodsFromTargetClass($targetClassName)
    {
        $methods = array();
        $class = new \ReflectionClass($targetClassName);

        foreach (array('__construct', '__clone') as $builtInMethodName) {
            if (!$class->hasMethod($builtInMethodName)) {
                $methods[] = array($targetClassName, $builtInMethodName);
            }
        }

        foreach ($class->getMethods() as $method) {
            $methods[] = array($targetClassName, $method->getName());
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
     * 			\TYPO3\Flow\Aop\Advice\AroundAdvice::class => array(
     * 				new \TYPO3\Flow\Aop\Advice\AroundAdvice(\TYPO3\Foo\SomeAspect::class, 'aroundAdvice', \TYPO3\Flow\Core\Bootstrap::$staticObjectManager, function() { ... }),
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

        $methodsAndAdvicesArrayCode = "\n        \$objectManager = \\TYPO3\\Flow\\Core\\Bootstrap::\$staticObjectManager;\n";
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
     * @throws \TYPO3\Flow\Aop\Exception\VoidImplementationException
     */
    protected function buildMethodsInterceptorCode($targetClassName, array $interceptedMethods)
    {
        foreach ($interceptedMethods as $methodName => $methodMetaInformation) {
            if (count($methodMetaInformation['groupedAdvices']) === 0) {
                throw new \TYPO3\Flow\Aop\Exception\VoidImplementationException(sprintf('Refuse to introduce method %s into target class %s because it has no implementation code. You might want to create an around advice which implements this method.', $methodName, $targetClassName), 1303224472);
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
                        $interceptedMethods[$methodName]['groupedAdvices'][get_class($advice)][] = array(
                            'advice' => $advice,
                            'runtimeEvaluationsClosureCode' => $pointcut->getRuntimeEvaluationsClosureCode()
                        );
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
                $interceptedMethods[$methodName]['groupedAdvices'] = array();
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
        $introductions = array();
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
        $introductions = array();
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
        $interfaceNames = array();
        foreach ($interfaceIntroductions as $introduction) {
            $interfaceNames[] = '\\' . $introduction->getInterfaceName();
        }
        return $interfaceNames;
    }

    /**
     * Returns all methods declared by the introduced interfaces
     *
     * @param array $interfaceIntroductions An array of \TYPO3\Flow\Aop\InterfaceIntroduction
     * @return array An array of method information (interface, method name)
     * @throws \TYPO3\Flow\Aop\Exception
     */
    protected function getIntroducedMethodsFromInterfaceIntroductions(array $interfaceIntroductions)
    {
        $methods = array();
        $methodsAndIntroductions = array();
        foreach ($interfaceIntroductions as $introduction) {
            $interfaceName = $introduction->getInterfaceName();
            $methodNames = get_class_methods($interfaceName);
            if (is_array($methodNames)) {
                foreach ($methodNames as $newMethodName) {
                    if (isset($methodsAndIntroductions[$newMethodName])) {
                        throw new \TYPO3\Flow\Aop\Exception('Method name conflict! Method "' . $newMethodName . '" introduced by "' . $introduction->getInterfaceName() . '" declared in aspect "' . $introduction->getDeclaringAspectClassName() . '" has already been introduced by "' . $methodsAndIntroductions[$newMethodName]->getInterfaceName() . '" declared in aspect "' . $methodsAndIntroductions[$newMethodName]->getDeclaringAspectClassName() . '".', 1173020942);
                    }
                    $methods[] = array($interfaceName, $newMethodName);
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
