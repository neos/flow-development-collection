<?php
namespace TYPO3\Flow\Package;

use TYPO3\Flow\Composer\ComposerUtility;

/**
 * A simple package dependency order solver. Just sorts by simple dependencies, does no checking or versions.
 */
class PackageOrderResolver
{
    /**
     * @var array
     */
    protected $manifestData;

    /**
     * @var array
     */
    protected $packageStates;

    /**
     * @var array
     */
    protected $sortedPackages;

    /**
     * @param array $packages The array of package states to order by dependencies
     * @param array $manifestData The manifests of all given packages for reading dependencies
     */
    public function __construct(array $packages, array $manifestData)
    {
        $this->manifestData = $manifestData;
        $this->packageStates = $packages;
    }

    /**
     * Sorts the packages and returns the sorted packages array
     *
     * @return array
     */
    public function sort()
    {
        if ($this->sortedPackages === null) {
            $unsortedPackageKeys = array_fill_keys(array_keys($this->packageStates), 0);
            $sortedPackages = array();

            while (!empty($unsortedPackageKeys)) {
                $resolved = $this->sortPackagesByDependencies(key($unsortedPackageKeys), $sortedPackages, $unsortedPackageKeys);
                if ($resolved) {
                    reset($unsortedPackageKeys);
                } else {
                    next($unsortedPackageKeys);
                }
            }

            $this->sortedPackages = $sortedPackages;
        }


        return $this->sortedPackages;
    }

    /**
     * Recursively sort dependencies of a package. This is a depth-first approach that recursively
     * adds all dependent packages to the sorted list before adding the given package. Visited
     * packages are flagged to break up cyclic dependencies.
     *
     * @param string $packageKey Package key to process
     * @param array $sortedPackages Array to sort packages into
     * @param array $unsortedPackages Array with state information of still unsorted packages
     * @return boolean
     */
    protected function sortPackagesByDependencies($packageKey, array &$sortedPackages, array &$unsortedPackages)
    {
        if (!isset($this->packageStates[$packageKey])) {
            return true;
        }

        /** @var array $packageState */
        $packageState = $this->packageStates[$packageKey];

        // $iteationForPackage will be -1 if the package is already worked on in a stack, in that case we will return instantly.
        $iterationForPackage = $unsortedPackages[$packageKey];

        if ($iterationForPackage === -1) {
            return false;
        }

        if (!isset($unsortedPackages[$packageKey])) {
            return true;
        }

        $unsortedPackages[$packageKey] = -1;
        $packageComposerManifest = $this->manifestData[$packageKey];
        $packageRequirements = isset($packageComposerManifest['require']) ? array_keys($packageComposerManifest['require']) : [];
        $unresolvedDependencies = 0;

        foreach ($packageRequirements as $requiredComposerName) {
            if (!$this->packageRequirementIsComposerPackage($requiredComposerName)) {
                continue;
            }

            if (isset($sortedPackages[$packageKey])) {
                continue;
            }

            if (isset($unsortedPackages[$requiredComposerName])) {
                $resolved = $this->sortPackagesByDependencies($requiredComposerName, $sortedPackages, $unsortedPackages);
                if (!$resolved) {
                    $unresolvedDependencies++;
                }
            }
        }

        $unsortedPackages[$packageKey] = $iterationForPackage + 1;

        if ($unresolvedDependencies === 0 || $unsortedPackages[$packageKey] > 20) {
            unset($unsortedPackages[$packageKey]);
            $sortedPackages[$packageKey] = $packageState;
            return true;
        }

        return false;
    }

    /**
     * Check whether the given package requirement (like "typo3/flow" or "php") is a composer package or not
     *
     * @param string $requirement the composer requirement string
     * @return boolean TRUE if $requirement is a composer package (contains a slash), FALSE otherwise
     */
    protected function packageRequirementIsComposerPackage($requirement)
    {
        return (strpos($requirement, '/') !== false);
    }
}
