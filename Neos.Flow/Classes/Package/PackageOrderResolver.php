<?php
namespace Neos\Flow\Package;

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
     * Array with state information of still unsorted packages.
     * The key is a package key, the value is "-1" if it is on stack for cycle detection; otherwise it is the number of times it was attempted to sort it already.
     *
     * @var array
     */
    protected $unsortedPackages;

    /**
     * @param array $packages The array of package states to order by dependencies
     * @param array $manifestData The manifests of all given packages for reading dependencies
     */
    public function __construct(array $packages, array $manifestData)
    {
        $this->packageStates = $packages;
        $this->manifestData = $manifestData;
        $this->unsortedPackages = array_fill_keys(array_keys($packages), 0);
    }

    /**
     * Sorts the packages and returns the sorted packages array
     *
     * @return array
     */
    public function sort()
    {
        if ($this->sortedPackages === null) {
            $this->sortedPackages = [];
            reset($this->unsortedPackages);
            while (!empty($this->unsortedPackages)) {
                $resolved = $this->sortPackage(key($this->unsortedPackages));
                if ($resolved) {
                    reset($this->unsortedPackages);
                } else {
                    next($this->unsortedPackages);
                }
            }
        }

        return $this->sortedPackages;
    }

    /**
     * Recursively sort dependencies of a package. This is a depth-first approach that recursively
     * adds all dependent packages to the sorted list before adding the given package. Visited
     * packages are flagged to break up cyclic dependencies.
     *
     * @param string $packageKey Package key to process
     * @return boolean true if package was sorted; false otherwise.
     */
    protected function sortPackage($packageKey)
    {
        if (!isset($this->packageStates[$packageKey])) {
            // Package does not exist; so that means it is just skipped; but that's to the outside as if sorting was successful.
            return true;
        }

        if (!isset($this->unsortedPackages[$packageKey])) {
            // Safeguard: Package is not unsorted anymore.
            return true;
        }
        $iterationForPackage = $this->unsortedPackages[$packageKey];
        // $iterationForPackage will be -1 if the package is already worked on in a stack, in that case we will return instantly.
        if ($iterationForPackage === -1) {
            return false;
        }

        $this->unsortedPackages[$packageKey] = -1;
        $packageComposerManifest = $this->manifestData[$packageKey];
        $unresolvedDependencies = 0;

        $packageRequirements = isset($packageComposerManifest['require']) ? array_keys($packageComposerManifest['require']) : [];
        $unresolvedDependencies += $this->sortListBefore($packageKey, $packageRequirements);

        if (isset($packageComposerManifest['extra']['neos']['loading-order']['after']) && is_array($packageComposerManifest['extra']['neos']['loading-order']['after'])) {
            $sortingConfiguration = $packageComposerManifest['extra']['neos']['loading-order']['after'];
            $unresolvedDependencies += $this->sortListBefore($packageKey, $sortingConfiguration);
        }

        /** @var array $packageState */
        $packageState = $this->packageStates[$packageKey];
        $this->unsortedPackages[$packageKey] = $iterationForPackage + 1;
        if ($unresolvedDependencies === 0) {
            // we are validly able to sort the package to this position.
            unset($this->unsortedPackages[$packageKey]);
            $this->sortedPackages[$packageKey] = $packageState;
            return true;
        }

        if ($this->unsortedPackages[$packageKey] > 20) {
            // SECOND case: ERROR case. This happens with MANY cyclic dependencies, in this case we just degrade by arbitarily sorting the package; and continue. Alternative would be throwing an Exception.
            unset($this->unsortedPackages[$packageKey]);

            // In order to be able to debug this kind of error (if we hit it), we at least try to write to PackageStates.php
            // so if people send it to us, we have some chance of finding the error.
            $packageState['error-sorting-limit-reached'] = true;
            $this->sortedPackages[$packageKey] = $packageState;
            return true;
        }

        return false;
    }

    /**
     * Tries to sort packages from the given list before the named package key.
     * Ignores non existing packages and any composer key without "/" (eg. "php").
     *
     * @param string $packageKey
     * @param string[] $packagesToLoadBefore
     * @return int
     */
    protected function sortListBefore($packageKey, array $packagesToLoadBefore)
    {
        $unresolvedDependencies = 0;
        foreach ($packagesToLoadBefore as $composerNameToLoadBefore) {
            if (!$this->packageRequirementIsComposerPackage($composerNameToLoadBefore)) {
                continue;
            }

            if (isset($this->sortedPackages[$packageKey])) {
                // "Success" case: a required package is already sorted in front of our current $packageKey.
                continue;
            }

            if (isset($this->unsortedPackages[$composerNameToLoadBefore])) {
                $resolved = $this->sortPackage($composerNameToLoadBefore);
                if (!$resolved) {
                    $unresolvedDependencies++;
                }
            }
        }

        return $unresolvedDependencies;
    }

    /**
     * Check whether the given package requirement (like "neos/flow" or "php") is a composer package or not
     *
     * @param string $requirement the composer requirement string
     * @return boolean TRUE if $requirement is a composer package (contains a slash), FALSE otherwise
     */
    protected function packageRequirementIsComposerPackage($requirement)
    {
        return (strpos($requirement, '/') !== false);
    }
}
