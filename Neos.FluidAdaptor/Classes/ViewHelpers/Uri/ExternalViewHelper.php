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
     * @param string $uri target URI
     * @param string $defaultScheme scheme the href attribute will be prefixed with if specified $uri does not contain a scheme already
     * @return string Rendered URI
     * @api
     */
    public function render($uri, $defaultScheme = 'http')
    {
        $scheme = parse_url($uri, PHP_URL_SCHEME);
        if ($scheme === null && $defaultScheme !== '') {
            $uri = $defaultScheme . '://' . $uri;
        }
        return $uri;
    }
}
