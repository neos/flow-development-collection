<?php
declare(strict_types=1);

namespace Neos\Flow\ObjectManagement;

use Neos\Flow\Composer\ComposerUtility;
use Neos\Flow\Configuration\Exception\InvalidConfigurationTypeException;
use Neos\Flow\Package\FlowPackageInterface;
use Neos\Flow\Package\PackageInterface;
use Psr\Log\LoggerInterface;

/**
 * Provides the list of classes that could be proxied.
 * Takes into account package type and configured filters.
 */
class PackageClassFileProvider
{
    protected LoggerInterface|null $logger = null;

    public function injectLogger(LoggerInterface|null $logger): void
    {
        $this->logger = $logger;
    }

    /**
     * Traverses through all class files of the active packages and registers collects the class names as
     * "all available class names". If the respective Flow settings say so, also function test classes
     * are registered.
     *
     * For performance reasons this function ignores classes whose name ends with "Exception".
     *
     * @param PackageInterface[] $packages A list of packages to consider
     * @param array{"includeClasses"?: mixed|array<string, string|bool>, "registerFunctionalTestClasses"?: bool} $objectConfigurationSettings
     *
     * @return array<string, string[]> A list of class names which were discovered in the given packages
     *
     * @throws InvalidConfigurationTypeException
     */
    public function build(array $packages, array $objectConfigurationSettings): array
    {
        $includeClassesConfiguration = [];
        if (isset($objectConfigurationSettings['includeClasses'])) {
            if (!is_array($objectConfigurationSettings['includeClasses'])) {
                throw new InvalidConfigurationTypeException('The setting "Neos.Flow.object.includeClasses" is invalid, it must be an array if set. Check the syntax in the YAML file.', 1422357285);
            }

            $includeClassesConfiguration = $objectConfigurationSettings['includeClasses'];
        }

        $availableClassNames = ['' => ['DateTime']];

        $shouldRegisterFunctionalTestClasses = (bool)($objectConfigurationSettings['registerFunctionalTestClasses'] ?? false);

        foreach ($packages as $packageKey => $package) {
            $packageType = (string)$package->getComposerManifest('type');
            if (isset($includeClassesConfiguration[$packageKey]) || ComposerUtility::isFlowPackageType($packageType)) {
                foreach ($package->getClassFiles() as $fullClassName => $path) {
                    if (!str_ends_with($fullClassName, 'Exception')) {
                        $availableClassNames[$packageKey][] = $fullClassName;
                    }
                }
                if ($package instanceof FlowPackageInterface && $shouldRegisterFunctionalTestClasses) {
                    foreach ($package->getFunctionalTestsClassFiles() as $fullClassName => $path) {
                        if (!str_ends_with($fullClassName, 'Exception')) {
                            $availableClassNames[$packageKey][] = $fullClassName;
                        }
                    }
                }
                if (isset($availableClassNames[$packageKey])) {
                    $availableClassNames[$packageKey] = array_unique($availableClassNames[$packageKey]);
                }
            }
        }
        return $this->applyClassFilterConfiguration($availableClassNames, $includeClassesConfiguration);
    }

    /**
     * Filters the classnames available for object management by filter expressions that includes classes.
     *
     * @param array<string, string[]> $classNames All classnames per package
     * @param array<string, array<string, string|bool>> $filterConfiguration The filter configuration to apply
     * @return array<string, string[]> the remaining classes
     * @throws InvalidConfigurationTypeException
     */
    protected function applyClassFilterConfiguration(array $classNames, array $filterConfiguration): array
    {
        foreach ($filterConfiguration as $packageKey => $filterExpressions) {
            if (!array_key_exists($packageKey, $classNames)) {
                $this->logger?->debug('The package "' . $packageKey . '" specified in the setting "Neos.Flow.object.includeClasses" was either excluded or is not loaded.');
                continue;
            }
            if (!is_array($filterExpressions)) {
                throw new InvalidConfigurationTypeException('The value given for setting "Neos.Flow.object.includeClasses.\'' . $packageKey . '\'" is  invalid. It should be an array of expressions. Check the syntax in the YAML file.', 1422357272);
            }

            $classesForPackageUnderInspection = $classNames[$packageKey];
            $classNames[$packageKey] = [];

            foreach ($filterExpressions as $filterExpression) {
                $classesForPackageUnderInspection = array_filter(
                    $classesForPackageUnderInspection,
                    static function ($className) use ($filterExpression) {
                        $match = preg_match('/' . $filterExpression . '/', $className);
                        return $match === 1;
                    }
                );
                $classNames[$packageKey] = array_merge($classNames[$packageKey], $classesForPackageUnderInspection);
                $classesForPackageUnderInspection = $classNames[$packageKey];
            }

            if ($classNames[$packageKey] === []) {
                unset($classNames[$packageKey]);
            }
        }

        return $classNames;
    }
}
