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
use TYPO3\Flow\Aop\Advice\AfterAdvice;
use TYPO3\Flow\Aop\Advice\AfterReturningAdvice;
use TYPO3\Flow\Aop\Advice\AfterThrowingAdvice;
use TYPO3\Flow\Aop\Advice\AroundAdvice;
use TYPO3\Flow\Aop\Advice\BeforeAdvice;
use TYPO3\Flow\Aop\Advisor;
use TYPO3\Flow\Aop\AspectContainer;
use TYPO3\Flow\Aop\Exception;
use TYPO3\Flow\Aop\Exception\VoidImplementationException;
use TYPO3\Flow\Aop\InterfaceIntroduction;
use TYPO3\Flow\Aop\Pointcut\Pointcut;
use TYPO3\Flow\Aop\Pointcut\PointcutExpressionParser;
use TYPO3\Flow\Aop\PropertyIntroduction;
use TYPO3\Flow\Cache\Frontend\VariableFrontend;
use TYPO3\Flow\Log\SystemLoggerInterface;
use TYPO3\Flow\Object\CompileTimeObjectManager;
use TYPO3\Flow\Object\Proxy\Compiler;
use TYPO3\Flow\Reflection\ClassReflection;
use TYPO3\Flow\Reflection\PropertyReflection;
use TYPO3\Flow\Reflection\ReflectionService;

/**
 * The main class of the AOP (Aspect Oriented Programming) framework.
 *
 * @Flow\Proxy(false)
 * @Flow\Scope("singleton")
 */
class ProxyClassBuilder
{
    /**
     * @var Compiler
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
     * @var PointcutExpressionParser
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
    protected $blacklistedSubPackages = ['TYPO3\Flow\Aop\\', 'TYPO3\Flow\Cach', 'TYPO3\Flow\Erro', 'TYPO3\Flow\Log\\', 'TYPO3\Flow\Moni', 'TYPO3\Flow\Obje', 'TYPO3\Flow\Pack', 'TYPO3\Flow\Prop', 'TYPO3\Flow\Refl', 'TYPO3\Flow\Util', 'TYPO3\Flow\Vali'];

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
     * @param Compiler $compiler
     * @return void
     */
    public function injectCompiler(Compiler $compiler)
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
     * @param PointcutExpressionParser $pointcutExpressionParser
     * @return void
     */
    public function injectPointcutExpressionParser(PointcutExpressionParser $pointcutExpressionParser)
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
     * Returns an array of method names and advices which were applied to the specified class. If the
     * target class has no adviced methods, an empty array is returned.
     *
     * @param string $targetClassName Name of the target class
     * @return mixed An array of method names and their advices as array of \TYPO3\Flow\Aop\Advice\AdviceInterface
     * @throws Exception
     */
    public function getAdvicedMethodsInformationByTargetClass($targetClassName)
    {
        throw new Exception('This method is currently not supported.');

        if (!isset($this->advicedMethodsInformationByTargetClass[$targetClassName])) {
            return [];
        } else {
            return $this->advicedMethodsInformationByTargetClass[$targetClassName];
        }
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
                if (!in_array(substr($className, 0, 15), $this->blacklistedSubPackages)) {
                    if (!$this->reflectionService->isClassAnnotatedWith($className, Flow\Aspect::class) &&
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
     * @throws Exception
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
                        $advice = new AroundAdvice($aspectClassName, $methodName);
                        $pointcut = new Pointcut($annotation->pointcutExpression, $pointcutFilterComposite, $aspectClassName);
                        $advisor = new Advisor($advice, $pointcut);
                        $aspectContainer->addAdvisor($advisor);
                    break;
                    case Flow\Before::class:
                        $pointcutFilterComposite = $this->pointcutExpressionParser->parse($annotation->pointcutExpression, $this->renderSourceHint($aspectClassName, $methodName, $annotationClass));
                        $advice = new BeforeAdvice($aspectClassName, $methodName);
                        $pointcut = new Pointcut($annotation->pointcutExpression, $pointcutFilterComposite, $aspectClassName);
                        $advisor = new Advisor($advice, $pointcut);
                        $aspectContainer->addAdvisor($advisor);
                    break;
                    case Flow\AfterReturning::class:
                        $pointcutFilterComposite = $this->pointcutExpressionParser->parse($annotation->pointcutExpression, $this->renderSourceHint($aspectClassName, $methodName, $annotationClass));
                        $advice = new AfterReturningAdvice($aspectClassName, $methodName);
                        $pointcut = new Pointcut($annotation->pointcutExpression, $pointcutFilterComposite, $aspectClassName);
                        $advisor = new Advisor($advice, $pointcut);
                        $aspectContainer->addAdvisor($advisor);
                    break;
                    case Flow\AfterThrowing::class:
                        $pointcutFilterComposite = $this->pointcutExpressionParser->parse($annotation->pointcutExpression, $this->renderSourceHint($aspectClassName, $methodName, $annotationClass));
                        $advice = new AfterThrowingAdvice($aspectClassName, $methodName);
                        $pointcut = new Pointcut($annotation->pointcutExpression, $pointcutFilterComposite, $aspectClassName);
                        $advisor = new Advisor($advice, $pointcut);
                        $aspectContainer->addAdvisor($advisor);
                    break;
                    case Flow\After::class:
                        $pointcutFilterComposite = $this->pointcutExpressionParser->parse($annotation->pointcutExpression, $this->renderSourceHint($aspectClassName, $methodName, $annotationClass));
                        $advice = new AfterAdvice($aspectClassName, $methodName);
                        $pointcut = new Pointcut($annotation->pointcutExpression, $pointcutFilterComposite, $aspectClassName);
                        $advisor = new Advisor($advice, $pointcut);
                        $aspectContainer->addAdvisor($advisor);
                    break;
                    case Flow\Pointcut::class:
                        $pointcutFilterComposite = $this->pointcutExpressionParser->parse($annotation->expression, $this->renderSourceHint($aspectClassName, $methodName, $annotationClass));
                        $pointcut = new Pointcut($annotation->expression, $pointcutFilterComposite, $aspectClassName, $methodName);
                        $aspectContainer->addPointcut($pointcut);
                    break;
                }
            }
        }
        $introduceAnnotation = $this->reflectionService->getClassAnnotation($aspectClassName, Flow\Introduce::class);
        if ($introduceAnnotation !== null) {
            if ($introduceAnnotation->interfaceName === null) {
                throw new Exception('The interface introduction in class "' . $aspectClassName . '" does not contain the required interface name).', 1172694761);
            }
            $pointcutFilterComposite = $this->pointcutExpressionParser->parse($introduceAnnotation->pointcutExpression, $this->renderSourceHint($aspectClassName, $introduceAnnotation->interfaceName, Flow\Introduce::class));
            $pointcut = new Pointcut($introduceAnnotation->pointcutExpression, $pointcutFilterComposite, $aspectClassName);
            $introduction = new InterfaceIntroduction($aspectClassName, $introduceAnnotation->interfaceName, $pointcut);
            $aspectContainer->addInterfaceIntroduction($introduction);
        }

        foreach ($this->reflectionService->getClassPropertyNames($aspectClassName) as $propertyName) {
            $introduceAnnotation = $this->reflectionService->getPropertyAnnotation($aspectClassName, $propertyName, Flow\Introduce::class);
            if ($introduceAnnotation !== null) {
                $pointcutFilterComposite = $this->pointcutExpressionParser->parse($introduceAnnotation->pointcutExpression, $this->renderSourceHint($aspectClassName, $propertyName, Flow\Introduce::class));
                $pointcut = new Pointcut($introduceAnnotation->pointcutExpression, $pointcutFilterComposite, $aspectClassName);
                $introduction = new PropertyIntroduction($aspectClassName, $propertyName, $pointcut);
                $aspectContainer->addPropertyIntroduction($introduction);
            }
        }
        if (count($aspectContainer->getAdvisors()) < 1 &&
            count($aspectContainer->getPointcuts()) < 1 &&
            count($aspectContainer->getInterfaceIntroductions()) < 1 &&
            count($aspectContainer->getPropertyIntroductions()) < 1) {
            throw new Exception('The class "' . $aspectClassName . '" is tagged to be an aspect but doesn\'t contain advices nor pointcut or introduction declarations.', 1169124534);
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

            $proxyClass->addProperty($propertyName, 'NULL', $propertyIntroduction->getPropertyVisibility(), $propertyIntroduction->getPropertyDocComment());
        }

        $proxyClass->getMethod('Flow_Aop_Proxy_buildMethodsAndAdvicesArray')->addPreParentCallCode("\t\tif (method_exists(get_parent_class(), 'Flow_Aop_Proxy_buildMethodsAndAdvicesArray') && is_callable('parent::Flow_Aop_Proxy_buildMethodsAndAdvicesArray')) parent::Flow_Aop_Proxy_buildMethodsAndAdvicesArray();\n");
        $proxyClass->getMethod('Flow_Aop_Proxy_buildMethodsAndAdvicesArray')->addPreParentCallCode($this->buildMethodsAndAdvicesArrayCode($interceptedMethods));
        $proxyClass->getMethod('Flow_Aop_Proxy_buildMethodsAndAdvicesArray')->overrideMethodVisibility('protected');

        $callBuildMethodsAndAdvicesArrayCode = "\n\t\t\$this->Flow_Aop_Proxy_buildMethodsAndAdvicesArray();\n";
        $proxyClass->getConstructor()->addPreParentCallCode($callBuildMethodsAndAdvicesArrayCode);
        $proxyClass->getMethod('__wakeup')->addPreParentCallCode($callBuildMethodsAndAdvicesArrayCode);

        if (!$this->reflectionService->hasMethod($targetClassName, '__wakeup')) {
            $proxyClass->getMethod('__wakeup')->addPostParentCallCode("\t\tif (method_exists(get_parent_class(), '__wakeup') && is_callable('parent::__wakeup')) parent::__wakeup();\n");
        }

        // FIXME this can be removed again once Doctrine is fixed (see fixMethodsAndAdvicesArrayForDoctrineProxiesCode())
        $proxyClass->getMethod('Flow_Aop_Proxy_fixMethodsAndAdvicesArrayForDoctrineProxies')->addPreParentCallCode($this->fixMethodsAndAdvicesArrayForDoctrineProxiesCode());
        // FIXME this can be removed again once Doctrine is fixed (see fixInjectedPropertiesForDoctrineProxiesCode())
        $proxyClass->getMethod('Flow_Aop_Proxy_fixInjectedPropertiesForDoctrineProxies')->addPreParentCallCode($this->fixInjectedPropertiesForDoctrineProxiesCode());

        $this->buildGetAdviceChainsMethodCode($targetClassName);
        $this->buildInvokeJoinPointMethodCode($targetClassName);
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
     *	$this->Flow_Aop_Proxy_targetMethodsAndGroupedAdvices = array(
     *		'getSomeProperty' => array(
     *			'TYPO3\Flow\Aop\Advice\AroundAdvice' => array(
     *				new \TYPO3\Flow\Aop\Advice\AroundAdvice('TYPO3\Foo\SomeAspect', 'aroundAdvice', \\TYPO3\\Flow\\Core\\Bootstrap::$staticObjectManager, function() { ... }),
     *			),
     *		),
     *	);
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

        $methodsAndAdvicesArrayCode = "\n\t\t\$objectManager = \\TYPO3\\Flow\\Core\\Bootstrap::\$staticObjectManager;\n";
        $methodsAndAdvicesArrayCode .= "\t\t\$this->Flow_Aop_Proxy_targetMethodsAndGroupedAdvices = array(\n";
        foreach ($methodsAndGroupedAdvices as $methodName => $advicesAndDeclaringClass) {
            $methodsAndAdvicesArrayCode .= "\t\t\t'" . $methodName . "' => array(\n";
            foreach ($advicesAndDeclaringClass['groupedAdvices'] as $adviceType => $adviceConfigurations) {
                $methodsAndAdvicesArrayCode .= "\t\t\t\t'" . $adviceType . "' => array(\n";
                foreach ($adviceConfigurations as $adviceConfiguration) {
                    $advice = $adviceConfiguration['advice'];
                    $methodsAndAdvicesArrayCode .= "\t\t\t\t\tnew \\" . get_class($advice) . "('" . $advice->getAspectObjectName() . "', '" . $advice->getAdviceMethodName() . "', \$objectManager, " . $adviceConfiguration['runtimeEvaluationsClosureCode'] . "),\n";
                }
                $methodsAndAdvicesArrayCode .= "\t\t\t\t),\n";
            }
            $methodsAndAdvicesArrayCode .= "\t\t\t),\n";
        }
        $methodsAndAdvicesArrayCode .= "\t\t);\n";
        return  $methodsAndAdvicesArrayCode;
    }

    /**
     * Creates code that builds the targetMethodsAndGroupedAdvices array if it does not exist. This happens when a Doctrine
     * lazy loading proxy for an object is created for some specific purpose, but filled afterwards "on the fly" if this object
     * is part of a wide range "findBy" query.
     *
     * @todo Remove once doctrine is fixed
     * @return string
     */
    protected function fixMethodsAndAdvicesArrayForDoctrineProxiesCode()
    {
        $code = <<<EOT
		if (!isset(\$this->Flow_Aop_Proxy_targetMethodsAndGroupedAdvices) || empty(\$this->Flow_Aop_Proxy_targetMethodsAndGroupedAdvices)) {
			\$this->Flow_Aop_Proxy_buildMethodsAndAdvicesArray();
			if (method_exists(get_parent_class(), 'Flow_Aop_Proxy_fixMethodsAndAdvicesArrayForDoctrineProxies') && is_callable('parent::Flow_Aop_Proxy_fixMethodsAndAdvicesArrayForDoctrineProxies')) parent::Flow_Aop_Proxy_fixMethodsAndAdvicesArrayForDoctrineProxies();
		}
EOT;
        return $code;
    }

    /**
     * Creates code that reinjects dependencies if they do not exist. This is necessary because in certain circumstances
     * Doctrine loads a proxy in UnitOfWork->createEntity() without calling __wakeup and thus does not initialize DI.
     * This happens when a Doctrine lazy loading proxy for an object is created for some specific purpose, but filled
     * afterwards "on the fly" if this object is part of a wide range "findBy" query.
     *
     * @todo Remove once doctrine is fixed
     * @return string
     */
    protected function fixInjectedPropertiesForDoctrineProxiesCode()
    {
        $code = <<<EOT
		if (!\$this instanceof \Doctrine\ORM\Proxy\Proxy || isset(\$this->Flow_Proxy_injectProperties_fixInjectedPropertiesForDoctrineProxies)) {
			return;
		}
		\$this->Flow_Proxy_injectProperties_fixInjectedPropertiesForDoctrineProxies = TRUE;
		if (method_exists(get_class(), 'Flow_Proxy_injectProperties') && is_callable(array(\$this, 'Flow_Proxy_injectProperties'))) {
			\$this->Flow_Proxy_injectProperties();
		}
EOT;
        return $code;
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
     * @throws VoidImplementationException
     */
    protected function buildMethodsInterceptorCode($targetClassName, array $interceptedMethods)
    {
        foreach ($interceptedMethods as $methodName => $methodMetaInformation) {
            if (count($methodMetaInformation['groupedAdvices']) === 0) {
                throw new VoidImplementationException(sprintf('Refuse to introduce method %s into target class %s because it has no implementation code. You might want to create an around advice which implements this method.', $methodName, $targetClassName), 1303224472);
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
                    $pointcutQueryIdentifier ++;
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
     * @return array array of property introductions
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
     * @param array $interfaceIntroductions An array of \TYPO3\Flow\Aop\InterfaceIntroduction
     * @return array An array of method information (interface, method name)
     * @throws Exception
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
                        throw new Exception('Method name conflict! Method "' . $newMethodName . '" introduced by "' . $introduction->getInterfaceName() . '" declared in aspect "' . $introduction->getDeclaringAspectClassName() . '" has already been introduced by "' . $methodsAndIntroductions[$newMethodName]->getInterfaceName() . '" declared in aspect "' . $methodsAndIntroductions[$newMethodName]->getDeclaringAspectClassName() . '".', 1173020942);
                    }
                    $methods[] = [$interfaceName, $newMethodName];
                    $methodsAndIntroductions[$newMethodName] = $introduction;
                }
            }
        }
        return $methods;
    }

    /**
     * Adds a "getAdviceChains()" method to the current proxy class.
     *
     * @param string $targetClassName
     * @return void
     */
    protected function buildGetAdviceChainsMethodCode($targetClassName)
    {
        $proxyMethod = $this->compiler->getProxyClass($targetClassName)->getMethod('Flow_Aop_Proxy_getAdviceChains');
        $proxyMethod->setMethodParametersCode('$methodName');
        $proxyMethod->overrideMethodVisibility('private');

        $code = <<<'EOT'
		$adviceChains = array();
		if (isset($this->Flow_Aop_Proxy_groupedAdviceChains[$methodName])) {
			$adviceChains = $this->Flow_Aop_Proxy_groupedAdviceChains[$methodName];
		} else {
			if (isset($this->Flow_Aop_Proxy_targetMethodsAndGroupedAdvices[$methodName])) {
				$groupedAdvices = $this->Flow_Aop_Proxy_targetMethodsAndGroupedAdvices[$methodName];
				if (isset($groupedAdvices['TYPO3\Flow\Aop\Advice\AroundAdvice'])) {
					$this->Flow_Aop_Proxy_groupedAdviceChains[$methodName]['TYPO3\Flow\Aop\Advice\AroundAdvice'] = new \TYPO3\Flow\Aop\Advice\AdviceChain($groupedAdvices['TYPO3\Flow\Aop\Advice\AroundAdvice']);
					$adviceChains = $this->Flow_Aop_Proxy_groupedAdviceChains[$methodName];
				}
			}
		}
		return $adviceChains;

EOT;
        $proxyMethod->addPreParentCallCode($code);
    }

    /**
     * Adds a "invokeJoinPoint()" method to the current proxy class.
     *
     * @param string $targetClassName
     * @return void
     */
    protected function buildInvokeJoinPointMethodCode($targetClassName)
    {
        $proxyMethod = $this->compiler->getProxyClass($targetClassName)->getMethod('Flow_Aop_Proxy_invokeJoinPoint');
        $proxyMethod->setMethodParametersCode('\TYPO3\Flow\Aop\JoinPointInterface $joinPoint');
        $code = <<<'EOT'
		if (__CLASS__ !== $joinPoint->getClassName()) return parent::Flow_Aop_Proxy_invokeJoinPoint($joinPoint);
		if (isset($this->Flow_Aop_Proxy_methodIsInAdviceMode[$joinPoint->getMethodName()])) {
			return call_user_func_array(array('self', $joinPoint->getMethodName()), $joinPoint->getMethodArguments());
		}

EOT;
        $proxyMethod->addPreParentCallCode($code);
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
