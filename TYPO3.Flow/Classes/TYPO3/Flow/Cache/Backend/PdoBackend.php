<?php
namespace TYPO3\Flow\Cache\Backend;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Core\ApplicationContext;

/**
 * @deprecated Use \Neos\Cache\Backend\PdoBackend instead
 */
class PdoBackend extends \Neos\Cache\Backend\PdoBackend implements FlowSpecificBackendInterface
{
    use BackendCompatibilityTrait;

    /**
     * Constructs this backend
     *
     * @param ApplicationContext $context Flow's application context
     * @param array $options Configuration options - depends on the actual backend
     */
    public function __construct(ApplicationContext $context, array $options = [])
    {
        $this->context = $context;
        $environmentConfiguration = $this->createEnvironmentConfiguration($context);
        parent::__construct($environmentConfiguration, $options);
    }
}
