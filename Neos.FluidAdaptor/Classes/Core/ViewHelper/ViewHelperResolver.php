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
use Neos\Flow\Package\PackageManager;
use TYPO3Fluid\Fluid\Core\ViewHelper\ViewHelperInterface;

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
     * @var PackageManager
     */
    protected $packageManager;

    /**
     * Custom merged namespace for Neos Fluid adapter;
     * will look for classes in both namespaces starting
     * from the bottom.
     *
     * @var array
     */
    protected array $namespaces = [];

    /**
     * @Flow\InjectConfiguration(path="namespaces")
     * @var array
     */
    protected $namespacesFromConfiguration;

    public function initializeObject($reason): void
    {
        if ($reason === ObjectManagerInterface::INITIALIZATIONCAUSE_RECREATED) {
            return;
        }

        /** @var Package $package */
        foreach ($this->packageManager->getAvailablePackages() as $package) {
            foreach ($package->getNamespaces() as $namespace) {
                $viewHelperNamespace = $namespace;
                if (str_ends_with($namespace, '\\') === false) {
                    $viewHelperNamespace .= '\\';
                }
                $viewHelperNamespace .= 'ViewHelpers';
                $this->addNamespace(strtolower($package->getPackageKey()), $viewHelperNamespace);
            }
        }

        foreach ($this->namespacesFromConfiguration as $identifier => $namespace) {
            $this->addNamespace($identifier, $namespace);
        }
    }

    public function createViewHelperInstanceFromClassName(string $viewHelperClassName): ViewHelperInterface
    {
        $possibleViewHelper = $this->objectManager->get($viewHelperClassName);
        if ($possibleViewHelper instanceof ViewHelperInterface) {
            return $possibleViewHelper;
        }

        throw new \RuntimeException('Given ViewHelper class "' . $viewHelperClassName . '" does not implement ViewHelperInterface');
    }
}
