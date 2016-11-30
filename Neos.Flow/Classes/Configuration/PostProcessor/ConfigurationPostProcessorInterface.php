<?php
namespace Neos\Flow\Configuration\PostProcessor;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Symfony\Component\Yaml\Yaml;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Configuration\Exception\ParseErrorException;
use Neos\Flow\Utility\Arrays;

/**
 * Post processor for configurations
 *
 * @api
 */
interface ConfigurationPostProcessorInterface
{

    /**
     * Post process configuration
     *
     * @param array $configuration input configuration
     * @return void
     */
    public function process(array &$configuration);

}
