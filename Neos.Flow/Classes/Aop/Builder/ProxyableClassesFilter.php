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

/**
 * Removes classes which must not be AOP proxied, for security, technical or conceptual reasons.
 */
#[Flow\Proxy(false)]
class ProxyableClassesFilter
{
    /**
     * Hardcoded list of Flow sub packages (first 15 characters) which must be immune to AOP proxying for security, technical or conceptual reasons.
     *
     * @var array<int,string>
     */
    protected array $excludedSubPackages = ['Neos\Flow\Aop\\', 'Neos\Flow\Cach', 'Neos\Flow\Erro', 'Neos\Flow\Log\\', 'Neos\Flow\Moni', 'Neos\Flow\Obje', 'Neos\Flow\Pack', 'Neos\Flow\Prop', 'Neos\Flow\Refl', 'Neos\Flow\Util', 'Neos\Flow\Vali'];

    /**
     * Determines which of the given classes are potentially proxyable
     * and returns their names in a sorted array.
     *
     * @param array<string,array<class-string>> $classNamesByPackage Names of the classes to check
     * @param array<class-string> $aspectClassNames Names of the aspect classes, which are never proxyable
     * @return array<int,class-string> Names of classes which can be proxied
     */
    public function getProxyableClasses(array $classNamesByPackage, array $aspectClassNames): array
    {
        $aspectClassNamesIndex = array_flip($aspectClassNames);
        $proxyableClasses = [];
        foreach ($classNamesByPackage as $classNames) {
            foreach ($classNames as $className) {
                if (in_array(substr($className, 0, 15), $this->excludedSubPackages, true)) {
                    continue;
                }
                if (isset($aspectClassNamesIndex[$className])) {
                    continue;
                }
                $proxyableClasses[] = $className;
            }
        }
        sort($proxyableClasses);
        return $proxyableClasses;
    }
}
