<?php
declare(strict_types=1);

namespace Neos\Flow\Aop\Builder;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Aop\Advice\AfterAdvice;
use Neos\Flow\Aop\Advice\AfterReturningAdvice;
use Neos\Flow\Aop\Advice\AfterThrowingAdvice;
use Neos\Flow\Aop\Advice\AroundAdvice;
use Neos\Flow\Aop\Advice\BeforeAdvice;
use Neos\Flow\Aop\Advisor;
use Neos\Flow\Aop\AspectContainer;
use Neos\Flow\Aop\Exception;
use Neos\Flow\Aop\Exception\InvalidPointcutExpressionException;
use Neos\Flow\Aop\Exception\InvalidTargetClassException;
use Neos\Flow\Aop\InterfaceIntroduction;
use Neos\Flow\Aop\Pointcut\Pointcut;
use Neos\Flow\Aop\Pointcut\PointcutExpressionParser;
use Neos\Flow\Aop\PropertyIntroduction;
use Neos\Flow\Aop\TraitIntroduction;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\Reflection\Exception\ClassLoadingForReflectionFailedException;
use Neos\Flow\Reflection\Exception\InvalidClassException;
use Neos\Flow\Reflection\ReflectionService;

/**
 * Builds aspect containers, providing information about AOP introductions
 */
#[Flow\Scope("singleton")]
class AspectContainerBuilder
{
    protected ReflectionService $reflectionService;
    protected PointcutExpressionParser $pointcutExpressionParser;

    protected ObjectManagerInterface $objectManager;

    public function injectPointcutExpressionParser(PointcutExpressionParser $pointcutExpressionParser): void
    {
      $this->pointcutExpressionParser = $pointcutExpressionParser;
    }

    public function injectReflectionService(ReflectionService $reflectionService): void
    {
        $this->reflectionService = $reflectionService;
    }

    public function injectObjectManager(ObjectManagerInterface $objectManager): void
    {
        $this->objectManager = $objectManager;
    }

    /**
     * Builds aspect containers by checking reflection for Aspect attribute/annotation
     * Note that this has a runtime cache
     *
     * @return array<class-string, AspectContainer>
     * @throws ClassLoadingForReflectionFailedException
     * @throws Exception
     * @throws InvalidClassException
     * @throws InvalidPointcutExpressionException
     * @throws InvalidTargetClassException
     * @throws \ReflectionException
     */
    public function buildFromReflection(): array
    {
        $actualAspectClassNames = $this->reflectionService->getClassNamesByAnnotation(Flow\Aspect::class);
        sort($actualAspectClassNames);
        return $this->build($actualAspectClassNames);
    }

    /**
     * /**
     * Checks the annotations of the specified classes for aspect tags
     * and creates an aspect with advisors accordingly.
     *
     * @param class-string[] $classNames Classes to check for aspect tags.
     * @return array<class-string, AspectContainer> An array of Aop\AspectContainer for all aspects which were found.
     * @throws ClassLoadingForReflectionFailedException
     * @throws Exception
     * @throws InvalidClassException
     * @throws InvalidPointcutExpressionException
     * @throws InvalidTargetClassException
     * @throws \ReflectionException
     */
    protected function build(array $classNames): array
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
     * @param class-string $aspectClassName Name of the class which forms the aspect, contains advices etc.
     * @throws Exception
     * @throws InvalidTargetClassException
     * @throws InvalidPointcutExpressionException
     * @throws ClassLoadingForReflectionFailedException
     * @throws InvalidClassException
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
                        assert($annotation instanceof Flow\Around);
                        $pointcutFilterComposite = $this->pointcutExpressionParser->parse($annotation->pointcutExpression, $this->renderSourceHint($aspectClassName, $methodName, $annotationClass), $aspectContainer);
                        $advice = new AroundAdvice($aspectClassName, $methodName, $this->objectManager);
                        $pointcut = new Pointcut($annotation->pointcutExpression, $pointcutFilterComposite, $aspectClassName);
                        $advisor = new Advisor($advice, $pointcut);
                        $aspectContainer->addAdvisor($advisor);
                        break;
                    case Flow\Before::class:
                        assert($annotation instanceof Flow\Before);
                        $pointcutFilterComposite = $this->pointcutExpressionParser->parse($annotation->pointcutExpression, $this->renderSourceHint($aspectClassName, $methodName, $annotationClass), $aspectContainer);
                        $advice = new BeforeAdvice($aspectClassName, $methodName, $this->objectManager);
                        $pointcut = new Pointcut($annotation->pointcutExpression, $pointcutFilterComposite, $aspectClassName);
                        $advisor = new Advisor($advice, $pointcut);
                        $aspectContainer->addAdvisor($advisor);
                        break;
                    case Flow\AfterReturning::class:
                        assert($annotation instanceof Flow\AfterReturning);
                        $pointcutFilterComposite = $this->pointcutExpressionParser->parse($annotation->pointcutExpression, $this->renderSourceHint($aspectClassName, $methodName, $annotationClass), $aspectContainer);
                        $advice = new AfterReturningAdvice($aspectClassName, $methodName, $this->objectManager);
                        $pointcut = new Pointcut($annotation->pointcutExpression, $pointcutFilterComposite, $aspectClassName);
                        $advisor = new Advisor($advice, $pointcut);
                        $aspectContainer->addAdvisor($advisor);
                        break;
                    case Flow\AfterThrowing::class:
                        assert($annotation instanceof Flow\AfterThrowing);
                        $pointcutFilterComposite = $this->pointcutExpressionParser->parse($annotation->pointcutExpression, $this->renderSourceHint($aspectClassName, $methodName, $annotationClass), $aspectContainer);
                        $advice = new AfterThrowingAdvice($aspectClassName, $methodName, $this->objectManager);
                        $pointcut = new Pointcut($annotation->pointcutExpression, $pointcutFilterComposite, $aspectClassName);
                        $advisor = new Advisor($advice, $pointcut);
                        $aspectContainer->addAdvisor($advisor);
                        break;
                    case Flow\After::class:
                        assert($annotation instanceof Flow\After);
                        $pointcutFilterComposite = $this->pointcutExpressionParser->parse($annotation->pointcutExpression, $this->renderSourceHint($aspectClassName, $methodName, $annotationClass), $aspectContainer);
                        $advice = new AfterAdvice($aspectClassName, $methodName, $this->objectManager);
                        $pointcut = new Pointcut($annotation->pointcutExpression, $pointcutFilterComposite, $aspectClassName);
                        $advisor = new Advisor($advice, $pointcut);
                        $aspectContainer->addAdvisor($advisor);
                        break;
                    case Flow\Pointcut::class:
                        assert($annotation instanceof Flow\Pointcut);
                        $pointcutFilterComposite = $this->pointcutExpressionParser->parse($annotation->expression, $this->renderSourceHint($aspectClassName, $methodName, $annotationClass), $aspectContainer);
                        $pointcut = new Pointcut($annotation->expression, $pointcutFilterComposite, $aspectClassName, $methodName);
                        $aspectContainer->addPointcut($pointcut);
                        break;
                }
            }
        }
        $introduceAnnotation = $this->reflectionService->getClassAnnotation($aspectClassName, Flow\Introduce::class);
        if ($introduceAnnotation instanceof Flow\Introduce) {
            if ($introduceAnnotation->interfaceName === null && $introduceAnnotation->traitName === null) {
                throw new Exception('The introduction in class "' . $aspectClassName . '" does neither contain an interface name nor a trait name, at least one is required.', 1172694761);
            }
            $pointcutFilterComposite = $this->pointcutExpressionParser->parse($introduceAnnotation->pointcutExpression, $this->renderSourceHint($aspectClassName, (string)$introduceAnnotation->interfaceName, Flow\Introduce::class), $aspectContainer);
            $pointcut = new Pointcut($introduceAnnotation->pointcutExpression, $pointcutFilterComposite, $aspectClassName);

            if ($introduceAnnotation->interfaceName !== null) {
                $introduction = new InterfaceIntroduction($aspectClassName, $introduceAnnotation->interfaceName, $pointcut);
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
                assert($introduceAnnotation instanceof Flow\Introduce);
                $pointcutFilterComposite = $this->pointcutExpressionParser->parse($introduceAnnotation->pointcutExpression, $this->renderSourceHint($aspectClassName, $propertyName, Flow\Introduce::class), $aspectContainer);
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
            throw new Exception('The class "' . $aspectClassName . '" is tagged to be an aspect but does not contain advices nor pointcut or introduction declarations.', 1169124534);
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
