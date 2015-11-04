<?php
namespace TYPO3\Fluid\ViewHelpers\Uri;

/*
 * This file is part of the TYPO3.Fluid package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * A view helper for creating URIs to external targets.
 * Currently the specified URI is simply passed through.
 *
 * = Examples =
 *
 * <code>
 * <f:uri.external uri="http://www.typo3.org" />
 * </code>
 * <output>
 * http://www.typo3.org
 * </output>
 *
 * <code title="custom default scheme">
 * <f:uri.external uri="typo3.org" defaultScheme="ftp" />
 * </code>
 * <output>
 * ftp://typo3.org
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
