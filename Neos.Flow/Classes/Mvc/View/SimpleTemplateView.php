<?php
namespace Neos\Flow\Mvc\View;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Utility\ObjectAccess;

/**
 * An abstract View
 *
 * @api
 */
class SimpleTemplateView extends AbstractView
{
    /**
     * @var array
     */
    protected $supportedOptions = [
        'templateSource' => ['', 'Source of the template to render', 'string'],
        'templatePathAndFilename' => [null, 'path and filename where the template source is found', 'string'],
    ];

    /**
     * Renders the view
     *
     * @return string The rendered view
     * @api
     */
    public function render()
    {
        $source = $this->getOption('templateSource');
        $templatePathAndFilename = $this->getOption('templatePathAndFilename');
        if ($templatePathAndFilename !== null) {
            $source = file_get_contents($templatePathAndFilename);
        }

        return preg_replace_callback('/\{([a-zA-Z0-9\-_.]+)\}/', function ($matches) {
            return ObjectAccess::getPropertyPath($this->variables, $matches[1]);
        }, $source);
    }
}
