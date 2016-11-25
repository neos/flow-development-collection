<?php
namespace Neos\Flow\Http\Client;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

/**
 * An HTTP exception occuring if an endless Location: redirect is suspect to happen
 *
 * @api
 */
class InfiniteRedirectionException extends \Neos\Flow\Exception
{
}
