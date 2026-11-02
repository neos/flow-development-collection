<?php

declare(strict_types=1);

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
use Neos\Flow\Aop;
use Neos\Flow\Aop\AspectContainer;
use Neos\Flow\Aop\Exception;
use Neos\Flow\Aop\Exception\InvalidPointcutExpressionException;
use Neos\Flow\Aop\Pointcut\Pointcut;
use Neos\Flow\Aop\Pointcut\PointcutExpressionParser;
use Neos\Flow\Aop\PropertyIntroduction;
use Neos\Flow\Aop\TraitIntroduction;
use Neos\Flow\Reflection\ReflectionService;
use Neos\Flow\Utility\Exception as UtilityException;
use Neos\Utility\Exception\FilesException;

/**
 * Builds the aspect containers for all classes tagged as aspect.
 *
 * Used by the AOP proxy class builder and by the aspect inspection command.
 */
#[Flow\Proxy(false)]
#[Flow\Scope("singleton")]
class AspectContainerBuilder
{
    protected ReflectionService $reflectionService;
    protected PointcutExpressionParser $pointcutExpressionParser;

    /**
     * The aspect containers built by the last call to buildFromReflection()
     *
     * @var array<class-string,AspectContainer>
     */
    protected array $aspectContainers = [];

    public function injectReflectionService(ReflectionService $reflectionService): void
    {
        $this->reflectionService = $reflectionService;
    }

    public function injectPointcutExpressionParser(PointcutExpressionParser $pointcutExpressionParser): void
    {
        $this->pointcutExpressionParser = $pointcutExpressionParser;
    }

    /**
     * Builds the aspect containers for all classes annotated as aspect.
     *
     * @return array<class-string,AspectContainer> The aspect containers, indexed and sorted by aspect class name
     * @throws Exception
     * @throws FilesException
     * @throws InvalidPointcutExpressionException
     * @throws \ReflectionException
     * @throws UtilityException
     */
    public function buildFromReflection(): array
    {
        $aspectClassNames = $this->reflectionService->getClassNamesByAnnotation(Flow\Aspect::class);
        sort($aspectClassNames);

        $this->aspectContainers = [];
        foreach ($aspectClassNames as $aspectClassName) {
            $this->aspectContainers[$aspectClassName] = $this->buildAspectContainer($aspectClassName);
        }
        return $this->aspectContainers;
    }

    /**
     * Traverses the aspect containers built last to find a pointcut from the given
     * aspect class and pointcut method name.
     *
     * Pointcuts may reference named pointcuts of other aspects, so this must look at all
     * aspect containers, not only the one of the aspect being parsed.
     *
     * @param string $aspectClassName Name of the aspect class where the pointcut has been declared
     * @param string $pointcutMethodName Method name of the pointcut
     * @return Pointcut|false The pointcut or false if none was found
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
     * Creates and returns an aspect from the annotations found in a class which
     * is tagged as an aspect. The object acting as an advice will already be
     * fetched (and therefore instantiated if necessary).
     *
     * @param class-string $aspectClassName Name of the class which forms the aspect, contains advices etc.
     * @return AspectContainer The aspect container containing one or more advisors
     * @throws Exception
     * @throws InvalidPointcutExpressionException
     * @throws UtilityException
     * @throws FilesException
     * @throws \ReflectionException
     */
    protected function buildAspectContainer(string $aspectClassName): AspectContainer
    {
        $aspectContainer = new AspectContainer($aspectClassName);
        if (!class_exists($aspectClassName)) {
            throw new Exception\InvalidTargetClassException(sprintf('The class "%s" is not loadable for AOP proxy building. This is most likely an inconsistency with the caches. Try running `./flow flow:cache:flush` and if that does not help, check the class exists and is correctly namespaced.', $aspectClassName), 1607422151);
        }
        $methodNames = get_class_methods($aspectClassName);

        foreach ($methodNames as $methodName) {
            foreach ($this->reflectionService->getMethodAnnotations($aspectClassName, $methodName) as $annotation) {
                $annotationClass = get_class($annotation);
                switch ($annotationClass) {
                    case Flow\Around::class:
                        /** @var Flow\Around $annotation */
                        $pointcutFilterComposite = $this->pointcutExpressionParser->parse($annotation->pointcutExpression, $this->renderSourceHint($aspectClassName, $methodName, $annotationClass));
                        $advice = new Aop\Advice\AroundAdvice($aspectClassName, $methodName);
                        $pointcut = new Pointcut($annotation->pointcutExpression, $pointcutFilterComposite, $aspectClassName);
                        $advisor = new Aop\Advisor($advice, $pointcut);
                        $aspectContainer->addAdvisor($advisor);
                        break;
                    case Flow\Before::class:
                        /** @var Flow\Before $annotation */
                        $pointcutFilterComposite = $this->pointcutExpressionParser->parse($annotation->pointcutExpression, $this->renderSourceHint($aspectClassName, $methodName, $annotationClass));
                        $advice = new Aop\Advice\BeforeAdvice($aspectClassName, $methodName);
                        $pointcut = new Pointcut($annotation->pointcutExpression, $pointcutFilterComposite, $aspectClassName);
                        $advisor = new Aop\Advisor($advice, $pointcut);
                        $aspectContainer->addAdvisor($advisor);
                        break;
                    case Flow\AfterReturning::class:
                        /** @var Flow\AfterReturning $annotation */
                        $pointcutFilterComposite = $this->pointcutExpressionParser->parse($annotation->pointcutExpression, $this->renderSourceHint($aspectClassName, $methodName, $annotationClass));
                        $advice = new Aop\Advice\AfterReturningAdvice($aspectClassName, $methodName);
                        $pointcut = new Pointcut($annotation->pointcutExpression, $pointcutFilterComposite, $aspectClassName);
                        $advisor = new Aop\Advisor($advice, $pointcut);
                        $aspectContainer->addAdvisor($advisor);
                        break;
                    case Flow\AfterThrowing::class:
                        /** @var Flow\AfterThrowing $annotation */
                        $pointcutFilterComposite = $this->pointcutExpressionParser->parse($annotation->pointcutExpression, $this->renderSourceHint($aspectClassName, $methodName, $annotationClass));
                        $advice = new Aop\Advice\AfterThrowingAdvice($aspectClassName, $methodName);
                        $pointcut = new Pointcut($annotation->pointcutExpression, $pointcutFilterComposite, $aspectClassName);
                        $advisor = new Aop\Advisor($advice, $pointcut);
                        $aspectContainer->addAdvisor($advisor);
                        break;
                    case Flow\After::class:
                        /** @var Flow\After $annotation */
                        $pointcutFilterComposite = $this->pointcutExpressionParser->parse($annotation->pointcutExpression, $this->renderSourceHint($aspectClassName, $methodName, $annotationClass));
                        $advice = new Aop\Advice\AfterAdvice($aspectClassName, $methodName);
                        $pointcut = new Pointcut($annotation->pointcutExpression, $pointcutFilterComposite, $aspectClassName);
                        $advisor = new Aop\Advisor($advice, $pointcut);
                        $aspectContainer->addAdvisor($advisor);
                        break;
                    case Flow\Pointcut::class:
                        /** @var Flow\Pointcut $annotation */
                        $pointcutFilterComposite = $this->pointcutExpressionParser->parse($annotation->expression, $this->renderSourceHint($aspectClassName, $methodName, $annotationClass));
                        $pointcut = new Pointcut($annotation->expression, $pointcutFilterComposite, $aspectClassName, $methodName);
                        $aspectContainer->addPointcut($pointcut);
                        break;
                }
            }
        }
        $introduceAnnotation = $this->reflectionService->getClassAnnotation($aspectClassName, Flow\Introduce::class);
        if ($introduceAnnotation instanceof Flow\Introduce) {
            if ($introduceAnnotation->interfaceName === null && $introduceAnnotation->traitName === null) {
                throw new Aop\Exception('The introduction in class "' . $aspectClassName . '" does neither contain an interface name nor a trait name, at least one is required.', 1172694761);
            }
            $pointcutFilterComposite = $this->pointcutExpressionParser->parse($introduceAnnotation->pointcutExpression, $this->renderSourceHint($aspectClassName, (string)$introduceAnnotation->interfaceName, Flow\Introduce::class));
            $pointcut = new Pointcut($introduceAnnotation->pointcutExpression, $pointcutFilterComposite, $aspectClassName);

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
                $pointcut = new Pointcut($introduceAnnotation->pointcutExpression, $pointcutFilterComposite, $aspectClassName);
                $introduction = new PropertyIntroduction($aspectClassName, $propertyName, $pointcut);
                $aspectContainer->addPropertyIntroduction($introduction);
            }
        }
        if (count($aspectContainer->getAdvisors()) < 1 &&
            count($aspectContainer->getPointcuts()) < 1 &&
            count($aspectContainer->getInterfaceIntroductions()) < 1 &&
            count($aspectContainer->getTraitIntroductions()) < 1 &&
            count($aspectContainer->getPropertyIntroductions()) < 1) {
            throw new Aop\Exception('The class "' . $aspectClassName . '" is tagged to be an aspect but does not contain advices nor pointcut or introduction declarations.', 1169124534);
        }
        return $aspectContainer;
    }

    /**
     * Renders a short message which gives a hint on where the currently parsed pointcut expression was defined.
     */
    protected function renderSourceHint(string $aspectClassName, string $methodName, string $tagName): string
    {
        return sprintf('%s::%s (%s advice)', $aspectClassName, $methodName, $tagName);
    }
}
