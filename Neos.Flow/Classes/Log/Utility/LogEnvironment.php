<?php
declare(strict_types=1);

namespace Neos\Flow\Log\Utility;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Core\Bootstrap;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\Package\PackageInterface;
use Neos\Flow\Package\PackageKeyAwareInterface;
use Neos\Flow\Package\PackageManager;
use Neos\Flow\Annotations as Flow;

abstract class LogEnvironment
{

    /**
     * @var array
     */
    protected static $packageKeys = [];

    /**
     * @var bool
     */
    protected static $initialized = false;

    /**
     * Returns an array containing the log environment variables
     * under the key FLOW_LOG_ENVIRONMENT to be set as part of the additional data
     * in an log method call.
     *
     * @param string $methodName
     * @return array
     */
    public static function fromMethodName(string $methodName): array
    {
        if (strpos($methodName, '::') > 0) {
            list($className, $functionName) = explode('::', $methodName);
        } elseif (substr($methodName, -9, 9) === '{closure}') {
            $className = substr($methodName, 0, -9);
            $functionName = '{closure}';
        }

        return [
            'FLOW_LOG_ENVIRONMENT' => [
                'packageKey' => self::getPackageKeyFromClassName($className),
                'className' => $className,
                'methodName' => $functionName
            ]
        ];
    }

    /**
     * @param string $className
     * @return string
     */
    protected static function getPackageKeyFromClassName(string $className): string
    {
        $packageKeys = static::getPackageKeys();
        $classPathArray = explode('\\', $className);

        $determinedPackageKey = array_shift($classPathArray);
        $packageKeyCandidate = $determinedPackageKey;

        foreach ($classPathArray as $classPathSegment) {
            $packageKeyCandidate = $packageKeyCandidate . '.' . $classPathSegment;

            if (!isset($packageKeys[$packageKeyCandidate])) {
                continue;
            }

            $determinedPackageKey = $packageKeyCandidate;
        }

        return $determinedPackageKey;
    }

    /**
     * @return array
     * @Flow\CompileStatic
     */
    protected static function getPackageKeys(): array
    {
        if (self::$initialized === false) {
            if (!Bootstrap::$staticObjectManager instanceof ObjectManagerInterface) {
                return [];
            }

            /** @var PackageManager $packageManager */
            $packageManager = Bootstrap::$staticObjectManager->get(PackageManager::class);

            /** @var PackageInterface $package */
            foreach ($packageManager->getAvailablePackages() as $package) {
                if ($package instanceof PackageKeyAwareInterface) {
                    self::$packageKeys[$package->getPackageKey()] = true;
                }
            }
            self::$initialized = true;
        }

        return self::$packageKeys;
    }
}
