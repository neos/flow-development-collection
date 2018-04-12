<?php
namespace Neos\FluidAdaptor\ViewHelpers\Uri;

/*
 * This file is part of the Neos.FluidAdaptor package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\FluidAdaptor\Core\ViewHelper\AbstractViewHelper;

/**
 * A view helper for creating URIs to external targets.
 * Currently the specified URI is simply passed through.
 *
 * = Examples =
 *
 * <code>
 * <f:uri.external uri="https://www.neos.io" />
 * </code>
 * <output>
 * https://www.neos.io
 * </output>
 *
 * <code title="custom default scheme">
 * <f:uri.external uri="neos.io" defaultScheme="sftp" />
 * </code>
 * <output>
 * sftp://neos.io
 * </output>
 *
 * @api
 */
class ExternalViewHelper extends AbstractViewHelper
{

    /**
     * Arguments initialization
     *
     * @return void
     */
    public function initializeArguments()
    {
        $this->registerArgument('uri', 'string', 'target URI', true);
        $this->registerArgument('defaultScheme', 'string', 'target URI', false, 'http');
    }

    /**
     * @return string Rendered URI
     * @api
     */
    public function render()
    {
        $uri = $this->arguments['uri'];
        $defaultScheme = $this->arguments['defaultScheme'];

        $scheme = parse_url($uri, PHP_URL_SCHEME);
        if ($scheme === null && $defaultScheme !== '') {
            $uri = $defaultScheme . '://' . $uri;
        }
        return $uri;
    }
}
