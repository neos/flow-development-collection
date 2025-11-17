<?php
declare(strict_types=1);

namespace Neos\Flow\Aop\Builder;

/**
 * Removes some classes from being AOP proxyable, for various technical reasons.
 */
class ProxyableClassesFilter
{
    /**
     * Hardcoded list of Flow sub packages (first 15 characters) which must be immune to AOP proxying for security, technical or conceptual reasons.
     * @var string[]
     */
    protected array $excludedSubPackages = ['Neos\Flow\Aop\\', 'Neos\Flow\Cach', 'Neos\Flow\Erro', 'Neos\Flow\Log\\', 'Neos\Flow\Moni', 'Neos\Flow\Obje', 'Neos\Flow\Pack', 'Neos\Flow\Prop', 'Neos\Flow\Refl', 'Neos\Flow\Util', 'Neos\Flow\Vali'];


    /**
     * Determines which of the given classes are potentially proxyable
     * and returns their names in an array.
     *
     * @param array<string, class-string[]> $classNamesByPackage Names of the classes to check
     * @param class-string[] $aspectClasses
     * @return class-string[] Names of classes which can be proxied
     */
    public function getProxyableClasses(array $classNamesByPackage, array $aspectClasses): array
    {
        $proxyableClasses = [];
        foreach ($classNamesByPackage as $classNames) {
            foreach ($classNames as $className) {
                if (in_array(substr($className, 0, 15), $this->excludedSubPackages, true)) {
                    continue;
                }
                if (in_array($className, $aspectClasses, true)) {
                    continue;
                }
                $proxyableClasses[] = $className;
            }
        }

        sort($proxyableClasses);
        return $proxyableClasses;
    }
}
