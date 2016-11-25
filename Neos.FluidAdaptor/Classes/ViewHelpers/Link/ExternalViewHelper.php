<?php
namespace Neos\FluidAdaptor\ViewHelpers\Link;

/*
 * This file is part of the Neos.FluidAdaptor package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\FluidAdaptor\Core\ViewHelper\AbstractTagBasedViewHelper;

/**
 * A view helper for creating links to external targets.
 *
 * = Examples =
 *
 * <code>
 * <f:link.external uri="https://www.neos.io" target="_blank">external link</f:link.external>
 * </code>
 * <output>
 * <a href="https://www.neos.io" target="_blank">external link</a>
 * </output>
 *
 * <code title="custom default scheme">
 * <f:link.external uri="neos.io" defaultScheme="sftp">external ftp link</f:link.external>
 * </code>
 * <output>
 * <a href="sftp://neos.io">external ftp link</a>
 * </output>
 *
 * @api
 */
class ExternalViewHelper extends AbstractTagBasedViewHelper
{
    /**
     * @var string
     */
    protected $tagName = 'a';

    /**
     * Initialize arguments
     *
     * @return void
     * @api
     */
    public function initializeArguments()
    {
        $this->registerUniversalTagAttributes();
        $this->registerTagAttribute('name', 'string', 'Specifies the name of an anchor');
        $this->registerTagAttribute('rel', 'string', 'Specifies the relationship between the current document and the linked document');
        $this->registerTagAttribute('rev', 'string', 'Specifies the relationship between the linked document and the current document');
        $this->registerTagAttribute('target', 'string', 'Specifies where to open the linked document');
    }

    /**
     * @param string $uri the URI that will be put in the href attribute of the rendered link tag
     * @param string $defaultScheme scheme the href attribute will be prefixed with if specified $uri does not contain a scheme already
     * @return string Rendered link
     * @api
     */
    public function render($uri, $defaultScheme = 'http')
    {
        $scheme = parse_url($uri, PHP_URL_SCHEME);
        if ($scheme === null && $defaultScheme !== '') {
            $uri = $defaultScheme . '://' . $uri;
        }
        $this->tag->addAttribute('href', $uri);
        $this->tag->setContent($this->renderChildren());
        $this->tag->forceClosingTag(true);

        return $this->tag->render();
    }
}
