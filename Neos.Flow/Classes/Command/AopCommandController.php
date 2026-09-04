<?php
namespace Neos\Flow\Command;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Aop\AspectContainer;
use Neos\Flow\Aop\Builder\AspectContainerBuilder;
use Neos\Flow\Aop\Builder\ClassNameIndex;
use Neos\Flow\Aop\Builder\ProxyableClassesFilter;
use Neos\Flow\Aop\Builder\ProxyClassBuilder;
use Neos\Flow\Aop\Pointcut\PointcutExpressionParser;
use Neos\Flow\Cli\CommandController;
use Neos\Flow\ObjectManagement\PackageClassFileProvider;
use Neos\Flow\Package\PackageManager;
use Neos\Flow\Reflection\ReflectionService;

/**
 *
 */
#[Flow\Scope("singleton")]
class AopCommandController extends CommandController
{
    protected AspectContainerBuilder $aspectContainerBuilder;
    protected PointcutExpressionParser $pointcutExpressionParser;
    protected PackageManager $packageManager;
    protected ReflectionService $reflectionService;
    protected array $objectSettings;

    public function injectAspectContainerBuilder(AspectContainerBuilder $aspectContainerBuilder): void
    {
        $this->aspectContainerBuilder = $aspectContainerBuilder;
    }

    public function injectPointcutExpressionParser(PointcutExpressionParser $pointcutExpressionParser): void
    {
        $this->pointcutExpressionParser = $pointcutExpressionParser;
    }

    public function injectPackageManager(PackageManager $packageManager): void
    {
        $this->packageManager = $packageManager;
    }

    public function injectReflectionService(ReflectionService $reflectionService): void
    {
        $this->reflectionService = $reflectionService;
    }

    public function injectSettings(array $settings): void
    {
        $this->objectSettings = $settings['object'];
    }

    /**
     * @param class-string $onlyAspect Class name of an aspect class to focus on
     * @param bool $advisors Output advisors and their targets, this would be all advices declared within the aspect, enabled by default, use 0 to disable
     * @param bool $interfaceIntroductions Output interface introductions from this advice and where they are introduced, disabled by default
     * @param bool $propertyIntroductions Output property introductions from this advice and where they are introduced, disabled by default
     * @param bool $traitIntroductions Output trait introductions from this advice and where they are introduced, disabled by default
     * @return void
     * @throws \Neos\Flow\Aop\Exception
     * @throws \Neos\Flow\Aop\Exception\InvalidPointcutExpressionException
     * @throws \Neos\Flow\Aop\Exception\InvalidTargetClassException
     * @throws \Neos\Flow\Configuration\Exception\InvalidConfigurationTypeException
     * @throws \Neos\Flow\Reflection\Exception\ClassLoadingForReflectionFailedException
     * @throws \Neos\Flow\Reflection\Exception\InvalidClassException
     * @throws \ReflectionException
     */
    public function aspectTargetsCommand(string $onlyAspect = '', bool $advisors = true, bool $interfaceIntroductions = false, bool $propertyIntroductions = false, bool $traitIntroductions = false): void
    {
        $packageClassFileProvider = new PackageClassFileProvider();
        $packageClassFiles = $packageClassFileProvider->build($this->packageManager->getAvailablePackages(), $this->objectSettings);

        $this->pointcutExpressionParser->injectReflectionService($this->reflectionService);
        $this->pointcutExpressionParser->injectObjectManager($this->objectManager);

        $this->aspectContainerBuilder->injectReflectionService($this->reflectionService);
        $this->aspectContainerBuilder->injectPointcutExpressionParser($this->pointcutExpressionParser);
        $this->aspectContainerBuilder->injectObjectManager($this->objectManager);
        $aspectContainers = $this->aspectContainerBuilder->buildFromReflection();

        $proxyableClassesFilter = new ProxyableClassesFilter();
        $possibleTargetClassNames = $proxyableClassesFilter->getProxyableClasses($packageClassFiles, array_keys($aspectContainers));

        foreach ($aspectContainers as $packageName => $aspectContainer) {
            if ($onlyAspect !== '' && $aspectContainer->getClassName() !== $onlyAspect) {
                continue;
            }

            $this->outputFormatted('<b>Aspect container: "%s"</b>', [$packageName]);
            $classNameIndex = new ClassNameIndex();
            $classNameIndex->setClassNames($possibleTargetClassNames);
//            $targetClassNames = $aspectContainer->reduceTargetClassNames($classNameIndex);
//            foreach ($aspectContainer->getPointcuts() as $pointcut) {
//                $this->outputLine('- Pointcut "%s" applies to:', [$pointcut->getPointcutExpression()]);
//                foreach ($pointcut->reduceTargetClassNames($classNameIndex)->getClassNames() as $targetClassName) {
//                    $this->outputLine('class: "%s"', [$targetClassName]);
//                }
//            }

            $advisors && $this->outputAdvisors($aspectContainer, $classNameIndex);
            $interfaceIntroductions && $this->outputInterfaceIntroductions($aspectContainer, $classNameIndex);
            $propertyIntroductions && $this->outputPropertyInjections($aspectContainer, $classNameIndex);
            $traitIntroductions && $this->outputTraitIntroductions($aspectContainer, $classNameIndex);

//            foreach ($targetClassNames->getClassNames() as $targetClassName) {
//                $this->outputLine('Target class: "%s"', [$targetClassName]);
//            }
        }
    }

    protected function outputAdvisors(AspectContainer $aspectContainer, ClassNameIndex $classNameIndex): void
    {
        foreach ($aspectContainer->getAdvisors() as $advisor) {
            $this->outputLine('- %s with pointcut', [get_class($advisor->getAdvice())]);
            $this->outputLine('  %s', [$advisor->getPointcut()->getPointcutExpression()]);
            $this->outputLine('  applies to:');
            foreach ($advisor->getPointcut()->reduceTargetClassNames($classNameIndex)->getClassNames() as $targetClassName) {
                $this->outputLine('    class: "%s"', [$targetClassName]);
            }
        }
    }

    protected function outputInterfaceIntroductions(AspectContainer $aspectContainer, ClassNameIndex $classNameIndex): void
    {
        foreach ($aspectContainer->getInterfaceIntroductions() as $interfaceIntroduction) {
            $this->outputLine(' - Introducing interface "%s" to:', [$interfaceIntroduction->getInterfaceName()]);
            foreach ($interfaceIntroduction->getPointcut()->reduceTargetClassNames($classNameIndex)->getClassNames() as $targetClassName) {
                $this->outputLine('    class: "%s"', [$targetClassName]);
            }
        }
    }

    protected function outputPropertyInjections(AspectContainer $aspectContainer, ClassNameIndex $classNameIndex): void
    {
        foreach ($aspectContainer->getPropertyIntroductions() as $propertyIntroduction) {
            $this->outputLine(' - Introducing property "%s" to:', [$propertyIntroduction->getPropertyName()]);
            foreach ($propertyIntroduction->getPointcut()->reduceTargetClassNames($classNameIndex)->getClassNames() as $targetClassName) {
                $this->outputLine('    class: "%s"', [$targetClassName]);
            }
        }
    }

    protected function outputTraitIntroductions(AspectContainer $aspectContainer, ClassNameIndex $classNameIndex): void
    {
        foreach ($aspectContainer->getTraitIntroductions() as $traitIntroduction) {
            $this->outputLine(' - Introducing trait "%s" to:', [$traitIntroduction->getTraitName()]);
            foreach ($traitIntroduction->getPointcut()->reduceTargetClassNames($classNameIndex)->getClassNames() as $targetClassName) {
                $this->outputLine('    class: "%s"', [$targetClassName]);
            }
        }
    }
}
