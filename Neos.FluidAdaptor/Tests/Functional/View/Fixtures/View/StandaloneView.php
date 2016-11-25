<?php
namespace Neos\FluidAdaptor\Tests\Functional\View\Fixtures\View;

/*
 * This file is part of the Neos.FluidAdaptor package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

/**
 * Extended StandaloneView for testing purposes
 */
class StandaloneView extends \Neos\FluidAdaptor\View\StandaloneView
{
    protected $fileIdentifierPrefix = '';

    /**
     * Constructor
     *
     * @param \Neos\Flow\Mvc\ActionRequest $request The current action request. If none is specified it will be created from the environment.
     * @param string $fileIdentifierPrefix
     * @param array $options
     */
    public function __construct(\Neos\Flow\Mvc\ActionRequest $request = null, $fileIdentifierPrefix = '', array $options = [])
    {
        $this->fileIdentifierPrefix = $fileIdentifierPrefix;
        parent::__construct($request, $options);
    }


    protected function createIdentifierForFile($pathAndFilename, $prefix)
    {
        $prefix = $this->fileIdentifierPrefix . $prefix;
        return parent::createIdentifierForFile($pathAndFilename, $prefix);
    }
}
