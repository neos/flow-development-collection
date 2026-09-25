<?php
namespace Neos\Flow\Http;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Tests\Unit\Http\Fixtures\HeaderStack;

/**
 * Records the headers the RequestHandler sends, instead of sending them
 *
 * The RequestHandler calls header() from within this namespace without a leading backslash, so PHP calls this
 * function rather than the global one, once it is defined
 */
function header(string $header, bool $replace = true, int $response_code = 0): void
{
    HeaderStack::push($header, $replace, $response_code);
}
