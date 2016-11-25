<?php
namespace Neos\FluidAdaptor;

/*
 * This file is part of the Neos.FluidAdaptor package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Cache\CacheManager;
use Neos\Flow\Core\Booting\Sequence;
use Neos\Flow\Core\Bootstrap;
use Neos\Flow\Monitor\FileMonitor;
use Neos\Flow\Package\Package as BasePackage;
use Neos\Flow\Package\PackageManagerInterface;

/**
 * The Fluid Package
 *
 */
class Package extends BasePackage
{
    /**
     * @var boolean
     */
    protected $protected = false;

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
            $dispatcher->connect(Sequence::class, 'afterInvokeStep', function ($step) use ($bootstrap, $dispatcher) {
                if ($step->getIdentifier() === 'neos.flow:systemfilemonitor') {
                    $templateFileMonitor = FileMonitor::createFileMonitorAtBoot('Fluid_TemplateFiles', $bootstrap);
                    $packageManager = $bootstrap->getEarlyInstance(PackageManagerInterface::class);
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
            if ($identifier !== 'Fluid_TemplateFiles') {
                return;
            }

            if ($changedFiles === []) {
                return;
            }

            $templateCache = $bootstrap->getObjectManager()->get(CacheManager::class)->getCache('Fluid_TemplateCache');
            $templateCache->flush();
        };
        $dispatcher->connect(FileMonitor::class, 'filesHaveChanged', $flushTemplates);
    }
}
