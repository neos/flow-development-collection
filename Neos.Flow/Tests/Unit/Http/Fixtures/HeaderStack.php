<?php
namespace Neos\Flow\Tests\Unit\Http\Fixtures;

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
 * The headers the RequestHandler sent, in the order it sent them – see HeaderFunction.php
 */
class HeaderStack
{
    /**
     * @var array<array{header: string, replace: bool, responseCode: int}>
     */
    protected static $headers = [];

    public static function push(string $header, bool $replace, int $responseCode): void
    {
        self::$headers[] = ['header' => $header, 'replace' => $replace, 'responseCode' => $responseCode];
    }

    /**
     * @return array<array{header: string, replace: bool, responseCode: int}>
     */
    public static function stack(): array
    {
        return self::$headers;
    }

    public static function reset(): void
    {
        self::$headers = [];
    }
}
