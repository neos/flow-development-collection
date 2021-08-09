<?php
declare(strict_types=1);

namespace Neos\Flow\Configuration\Loader;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Configuration\Source\YamlSource;
use Neos\Flow\Core\ApplicationContext;

class AppendLoader implements LoaderInterface
{

    /**
     * @var YamlSource
     */
    private $yamlSource;

    /**
     * @var string
     */
    private $filePrefix;

    public function __construct(YamlSource $yamlSource, string $filePrefix)
    {
        $this->yamlSource = $yamlSource;
        $this->filePrefix = $filePrefix;
    }

    public function load(array $packages, ApplicationContext $context): array
    {
        $configuration = [];
        foreach ($packages as $package) {
            $configuration[] = $this->yamlSource->load($package->getConfigurationPath() . $this->filePrefix, true);
        }
        $configuration[] = $this->yamlSource->load(FLOW_PATH_CONFIGURATION . $this->filePrefix, true);

        foreach ($context->getHierarchy() as $contextName) {
            foreach ($packages as $package) {
                $configuration[] = $this->yamlSource->load($package->getConfigurationPath() . $contextName . '/' . $this->filePrefix, true);
            }
            $configuration[] = $this->yamlSource->load(FLOW_PATH_CONFIGURATION . $contextName . '/' . $this->filePrefix, true);
        }

        return array_merge([], ...$configuration);
    }
}
