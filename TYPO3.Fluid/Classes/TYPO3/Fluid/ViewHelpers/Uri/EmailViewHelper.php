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
 * Email uri view helper.
 * Currently the specified email is simply prepended by "mailto:" but we might add spam protection.
 *
 * = Examples =
 *
 * <code title="basic email uri">
 * <f:uri.email email="foo@bar.tld" />
 * </code>
 * <output>
 * mailto:foo@bar.tld
 * </output>
 *
 * @api
 */
class EmailViewHelper extends AbstractViewHelper
{
    /**
     * @param string $email The email address to be turned into a mailto uri.
     * @return string Rendered email uri
     * @api
     */
    public function render($email)
    {
        return 'mailto:' . $email;
    }
}
