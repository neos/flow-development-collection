<?php
namespace TYPO3\Fluid\Tests\Functional\View\Fixtures\View;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

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
