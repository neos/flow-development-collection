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

use Neos\Http\Factories\StreamFactoryTrait;
use Neos\Utility\ObjectAccess;
use Psr\Http\Message\StreamInterface;

/**
 * An abstract View
 *
 * @api
 */
class SimpleTemplateView extends AbstractView
{
    use StreamFactoryTrait;

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
     * @api
     */
    public function render(): StreamInterface
    {
        $source = $this->getOption('templateSource');
        $templatePathAndFilename = $this->getOption('templatePathAndFilename');
        if ($templatePathAndFilename !== null) {
            $source = file_get_contents($templatePathAndFilename);
        }

        $content = preg_replace_callback('/\{([a-zA-Z0-9\-_.]+)\}/', function ($matches) {
            return ObjectAccess::getPropertyPath($this->variables, $matches[1]);
        }, $source);

        return $this->createStream($content);
    }
}
