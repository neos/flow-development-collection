<?php
namespace Neos\FluidAdaptor\Core\ViewHelper;

/*
 * This file is part of the Neos.FluidAdaptor package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\Package\Package;
use Neos\Flow\Package\PackageManagerInterface;

/**
 * Class ViewHelperResolver
 *
 * Class whose purpose is dedicated to resolving classes which
 * can be used as ViewHelpers and ExpressionNodes in Fluid.
 *
 * In addition to modifying the behavior or the parser when
 * legacy mode is requested, this ViewHelperResolver is also
 * made capable of "mixing" two different ViewHelper namespaces
 * to effectively create aliases for the Fluid core ViewHelpers
 * to be loaded in the (TYPO3|Neos) scope as well.
 *
 * @Flow\Scope("singleton")
 */
class ViewHelperResolver extends \TYPO3Fluid\Fluid\Core\ViewHelper\ViewHelperResolver
{
    /**
     * @Flow\Inject
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @Flow\Inject
     * @var PackageManagerInterface
     */
    protected $packageManager;

    /**
     * Custom merged namespace for Neos Fluid adapter;
     * will look for classes in both namespaces starting
     * from the bottom.
     *
     * @var array
     */
    protected $namespaces = [];

    /**
     * ViewHelperResolver constructor.
     */
    public function __construct()
    {
        $this->setNamespaces($this->getDefaultNamespaces());
    }

    public function initializeObject($reason)
    {
        if ($reason === ObjectManagerInterface::INITIALIZATIONCAUSE_RECREATED) {
            return;
        }

        /** @var Package $package */
        foreach ($this->packageManager->getActivePackages() as $package) {
            foreach ($package->getNamespaces() as $namespace) {
                $viewHelperNamespace = $namespace;
                if (strpos(strrev($namespace), '\\') !== 0) {
                    $viewHelperNamespace .= '\\';
                }
                $viewHelperNamespace .= 'ViewHelpers';
                $this->addNamespace(strtolower($package->getPackageKey()), $viewHelperNamespace);
            }
        }
    }

    /**
     *
     */
    public function getDefaultNamespaces()
    {
        return [
            'f' => [
                'TYPO3Fluid\\Fluid\\ViewHelpers',
                'Neos\\FluidAdaptor\\ViewHelpers'
            ]
        ];
    }

    /**
     * @param string $viewHelperClassName
     * @return \TYPO3Fluid\Fluid\Core\ViewHelper\ViewHelperInterface
     */
    public function createViewHelperInstanceFromClassName($viewHelperClassName)
    {
        return $this->objectManager->get($viewHelperClassName);
    }

    /**
     * Add a PHP namespace where ViewHelpers can be found and give
     * it an alias/identifier.
     *
     * The provided namespace can be either a single namespace or
     * an array of namespaces, as strings. The identifier/alias is
     * always a single, alpha-numeric ASCII string.
     *
     * Calling this method multiple times with different PHP namespaces
     * for the same alias causes that namespace to be *extended*,
     * meaning that the PHP namespace you provide second, third etc.
     * are also used in lookups and are used *first*, so that if any
     * of the namespaces you add contains a class placed and named the
     * same way as one that exists in an earlier namespace, then your
     * class gets used instead of the earlier one.
     *
     * Example:
     *
     * $resolver->addNamespace('my', 'My\Package\ViewHelpers');
     * // Any ViewHelpers under this namespace can now be accessed using for example {my:example()}
     * // Now, assuming you also have an ExampleViewHelper class in a different
     * // namespace and wish to make that ExampleViewHelper override the other:
     * $resolver->addNamespace('my', 'My\OtherPackage\ViewHelpers');
     * // Now, since ExampleViewHelper exists in both places but the
     * // My\OtherPackage\ViewHelpers namespace was added *last*, Fluid
     * // will find and use My\OtherPackage\ViewHelpers\ExampleViewHelper.
     *
     * Alternatively, setNamespaces() can be used to reset and redefine
     * all previously added namespaces - which is great for cases where
     * you need to remove or replace previously added namespaces. Be aware
     * that setNamespaces() also removes the default "f" namespace, so
     * when you use this method you should always include the "f" namespace.
     *
     * @param string $identifier
     * @param string|array $phpNamespace
     * @return void
     */
    public function addNamespace($identifier, $phpNamespace)
    {
        if ($phpNamespace === null) {
            $this->namespaces[$identifier] = null;
            return;
        }

        if (!is_array($phpNamespace)) {
            $this->addNamespaceInternal($identifier, $phpNamespace);
            return;
        }

        foreach ($phpNamespace as $namespace) {
            $this->addNamespaceInternal($identifier, $namespace);
        }
    }

    /**
     * @param string $identifier
     * @param string $phpNamespace
     */
    protected function addNamespaceInternal($identifier, $phpNamespace)
    {
        if (!isset($this->namespaces[$identifier])) {
            $this->namespaces[$identifier] = [];
        }

        $this->namespaces[$identifier] = array_unique(array_merge($this->namespaces[$identifier], [$phpNamespace]));
    }
}
