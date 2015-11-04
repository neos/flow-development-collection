<?php
namespace TYPO3\Fluid\Tests\Functional\View\Fixtures\View;

/*
 * This file is part of the TYPO3.Fluid package.
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
class StandaloneView extends \TYPO3\Fluid\View\StandaloneView
{
    protected $fileIdentifierPrefix = '';

    /**
     * Constructor
     *
     * @param \TYPO3\Flow\Mvc\ActionRequest $request The current action request. If none is specified it will be created from the environment.
     * @param string $fileIdentifierPrefix
     */
    public function __construct(\TYPO3\Flow\Mvc\ActionRequest $request = null, $fileIdentifierPrefix = '')
    {
        $this->request = $request;
        $this->fileIdentifierPrefix = $fileIdentifierPrefix;
    }


    protected function createIdentifierForFile($pathAndFilename, $prefix)
    {
        $prefix = $this->fileIdentifierPrefix . $prefix;
        return parent::createIdentifierForFile($pathAndFilename, $prefix);
    }
}
