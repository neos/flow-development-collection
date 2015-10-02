<?php
namespace TYPO3\Fluid;

/*
 * This file is part of the TYPO3.Fluid package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Core\Bootstrap;
use TYPO3\Flow\Package\Package as BasePackage;

/**
 * The Fluid Package
 *
 */
class Package extends BasePackage
{
    /**
     * @var boolean
     */
    protected $protected = true;

    /**
     * Invokes custom PHP code directly after the package manager has been initialized.
     *
     * @param Bootstrap $bootstrap The current bootstrap
     * @return void
     */
    public function boot(Bootstrap $bootstrap)
    {
        $dispatcher = $bootstrap->getSignalSlotDispatcher();

        $context = $bootstrap->getContext();
        if (!$context->isProduction()) {
            $dispatcher->connect('TYPO3\Flow\Core\Booting\Sequence', 'afterInvokeStep', function ($step) use ($bootstrap, $dispatcher) {
                if ($step->getIdentifier() === 'typo3.flow:systemfilemonitor') {
                    $templateFileMonitor = \TYPO3\Flow\Monitor\FileMonitor::createFileMonitorAtBoot('Fluid_TemplateFiles', $bootstrap);
                    $packageManager = $bootstrap->getEarlyInstance('TYPO3\Flow\Package\PackageManagerInterface');
                    foreach ($packageManager->getActivePackages() as $packageKey => $package) {
                        if ($packageManager->isPackageFrozen($packageKey)) {
                            continue;
                        }

                        foreach (array('Templates', 'Partials', 'Layouts') as $path) {
                            $templatesPath = $package->getResourcesPath() . 'Private/' . $path;

                            if (is_dir($templatesPath)) {
                                $templateFileMonitor->monitorDirectory($templatesPath);
                            }
                        }
                    }

                    $templateFileMonitor->detectChanges();
                    $templateFileMonitor->shutdownObject();
                }
            });
        }

            // Use a closure to invoke the TemplateCompiler, since the object is not registered during compiletime
        $flushTemplates = function ($identifier, $changedFiles) use ($bootstrap) {
            if ($identifier !== 'Flow_ClassFiles') {
                return;
            }

            $objectManager = $bootstrap->getObjectManager();
            if ($objectManager->isRegistered('TYPO3\Fluid\Core\Compiler\TemplateCompiler')) {
                $templateCompiler = $objectManager->get('TYPO3\Fluid\Core\Compiler\TemplateCompiler');
                $templateCompiler->flushTemplatesOnViewHelperChanges($changedFiles);
            }
        };
        $dispatcher->connect('TYPO3\Flow\Monitor\FileMonitor', 'filesHaveChanged', $flushTemplates);
    }
}
