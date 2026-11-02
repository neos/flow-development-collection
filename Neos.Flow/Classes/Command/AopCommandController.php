<?php

declare(strict_types=1);

namespace Neos\Flow\Command;

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
use Neos\Flow\Aop\AspectContainer;
use Neos\Flow\Aop\Builder\AspectContainerBuilder;
use Neos\Flow\Aop\Builder\ClassNameIndex;
use Neos\Flow\Aop\Builder\ProxyableClassesFilter;
use Neos\Flow\Cli\CommandController;
use Neos\Flow\ObjectManagement\PackageClassFileProvider;
use Neos\Flow\Package\PackageManager;

/**
 * Command controller for inspecting the AOP setup
 */
#[Flow\Scope("singleton")]
class AopCommandController extends CommandController
{
    protected AspectContainerBuilder $aspectContainerBuilder;
    protected PackageManager $packageManager;

    /**
     * @var array<string,mixed>
     */
    protected array $objectSettings = [];

    public function injectAspectContainerBuilder(AspectContainerBuilder $aspectContainerBuilder): void
    {
        $this->aspectContainerBuilder = $aspectContainerBuilder;
    }

    public function injectPackageManager(PackageManager $packageManager): void
    {
        $this->packageManager = $packageManager;
    }

    /**
     * @param array<string,mixed> $settings
     */
    public function injectSettings(array $settings): void
    {
        $this->objectSettings = $settings['object'] ?? [];
    }

    /**
     * List aspects and the classes they target
     *
     * Shows all aspects found in the system, the advices they declare and the classes
     * those advices apply to. Introduced interfaces, properties and traits can be shown, too.
     *
     * This is a compile time command without property mapping, therefore boolean
     * options are best given as 0 or 1.
     *
     * @param string $onlyAspect Class name of an aspect class to focus on
     * @param bool $advisors Output advisors and their targets, this would be all advices declared within the aspect, enabled by default, use 0 to disable
     * @param bool $interfaceIntroductions Output interface introductions from this aspect and where they are introduced, disabled by default
     * @param bool $propertyIntroductions Output property introductions from this aspect and where they are introduced, disabled by default
     * @param bool $traitIntroductions Output trait introductions from this aspect and where they are introduced, disabled by default
     * @return void
     * @throws \Neos\Flow\Aop\Exception
     * @throws \Neos\Flow\Aop\Exception\InvalidPointcutExpressionException
     * @throws \Neos\Flow\Configuration\Exception\InvalidConfigurationTypeException
     * @throws \ReflectionException
     */
    public function aspectTargetsCommand(string $onlyAspect = '', bool $advisors = true, bool $interfaceIntroductions = false, bool $propertyIntroductions = false, bool $traitIntroductions = false): void
    {
        $classNamesByPackage = (new PackageClassFileProvider())->build($this->packageManager->getAvailablePackages(), $this->objectSettings);
        $aspectContainers = $this->aspectContainerBuilder->buildFromReflection();
        $possibleTargetClassNames = (new ProxyableClassesFilter())->getProxyableClasses($classNamesByPackage, array_keys($aspectContainers));

        foreach ($aspectContainers as $aspectClassName => $aspectContainer) {
            if ($onlyAspect !== '' && $aspectClassName !== $onlyAspect) {
                continue;
            }

            $this->outputFormatted('<b>Aspect container: "%s"</b>', [$aspectClassName]);
            $classNameIndex = new ClassNameIndex();
            $classNameIndex->setClassNames($possibleTargetClassNames);

            if ($advisors) {
                $this->outputAdvisors($aspectContainer, $classNameIndex);
            }
            if ($interfaceIntroductions) {
                $this->outputInterfaceIntroductions($aspectContainer, $classNameIndex);
            }
            if ($propertyIntroductions) {
                $this->outputPropertyIntroductions($aspectContainer, $classNameIndex);
            }
            if ($traitIntroductions) {
                $this->outputTraitIntroductions($aspectContainer, $classNameIndex);
            }
        }
    }

    protected function outputAdvisors(AspectContainer $aspectContainer, ClassNameIndex $classNameIndex): void
    {
        foreach ($aspectContainer->getAdvisors() as $advisor) {
            $this->outputLine('- %s with pointcut', [get_class($advisor->getAdvice())]);
            $this->outputLine('  %s', [$advisor->getPointcut()->getPointcutExpression()]);
            $this->outputLine('  applies to:');
            $this->outputTargetClassNames($advisor->getPointcut()->reduceTargetClassNames($classNameIndex));
        }
    }

    protected function outputInterfaceIntroductions(AspectContainer $aspectContainer, ClassNameIndex $classNameIndex): void
    {
        foreach ($aspectContainer->getInterfaceIntroductions() as $interfaceIntroduction) {
            $this->outputLine('- Introducing interface "%s" to:', [$interfaceIntroduction->getInterfaceName()]);
            $this->outputTargetClassNames($interfaceIntroduction->getPointcut()->reduceTargetClassNames($classNameIndex));
        }
    }

    protected function outputPropertyIntroductions(AspectContainer $aspectContainer, ClassNameIndex $classNameIndex): void
    {
        foreach ($aspectContainer->getPropertyIntroductions() as $propertyIntroduction) {
            $this->outputLine('- Introducing property "%s" to:', [$propertyIntroduction->getPropertyName()]);
            $this->outputTargetClassNames($propertyIntroduction->getPointcut()->reduceTargetClassNames($classNameIndex));
        }
    }

    protected function outputTraitIntroductions(AspectContainer $aspectContainer, ClassNameIndex $classNameIndex): void
    {
        foreach ($aspectContainer->getTraitIntroductions() as $traitIntroduction) {
            $this->outputLine('- Introducing trait "%s" to:', [$traitIntroduction->getTraitName()]);
            $this->outputTargetClassNames($traitIntroduction->getPointcut()->reduceTargetClassNames($classNameIndex));
        }
    }

    protected function outputTargetClassNames(ClassNameIndex $targetClassNames): void
    {
        foreach ($targetClassNames->getClassNames() as $targetClassName) {
            $this->outputLine('    class: "%s"', [$targetClassName]);
        }
    }
}
