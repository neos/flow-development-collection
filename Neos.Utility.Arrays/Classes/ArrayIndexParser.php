<?php
declare(strict_types=1);

namespace Neos\Utility;

/*
 * This file is part of the Neos.Utility.Arrays package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

class ArrayIndexParser
{

    /**
     * Parse path for indexing arrays like:
     * (Dots inside braces are preserved)
     *
     * foo.bar.baz
     *
     * foo[bar.buz].bing
     *
     * foo[bar.buz][bar.buz]
     */
    public static function parseFromString(string $input): array
    {
        $result = [];
        $previousToken = null;
        foreach (self::tokensFromString($input) as $token) {
            switch ($token::class) {
                case SegmentToken::class:
                    if ($previousToken instanceof BracketToken && $previousToken->isClosed()) {
                        throw new \Exception(sprintf('Missing dot'), 1677953277708);
                    }
                    $result[] = $token->value;
                    break;
                case DotToken::class:
                case BracketToken::class:
                    if ($previousToken instanceof SegmentToken) {
                        break;
                    }
                    if ($previousToken === null && $token instanceof BracketToken && $token->isOpen()) {
                        break;
                    }
                    if ($previousToken instanceof BracketToken && $previousToken->isClosed() && $token instanceof DotToken) {
                        break;
                    }
                    if ($previousToken instanceof BracketToken && $previousToken->isClosed() && $token instanceof BracketToken && $token->isOpen()) {
                        break;
                    }
                    throw new \Exception(sprintf('Empty path segment'), 1677952251960);
            }
            $previousToken = $token;
        }

        if ($previousToken instanceof DotToken) {
            throw new \Exception(sprintf('Empty path segment'), 1677952251960);
        }

        return $result;
    }

    private static function tokensFromString(string $input): \Generator
    {
        $current = "";
        $length = \strlen($input);

        for ($i = 0; $i < $length; $i++) {
            $char = $input[$i];

            switch ($char) {
                case "[":
                    if ($current !== "") {
                        yield new SegmentToken($current);
                        $current = "";
                    }
                    yield new BracketToken($char);

                    for ($i++; $i < $length; $i++) {
                        $char = $input[$i];
                        switch ($char) {
                            case "[":
                                throw new \Exception(sprintf('Unclosed array index. Unexpected char "%s" at position "%s" in "%s"', $char, $i, $input), 1677944492915);
                            case "]":
                                if ($current !== "") {
                                    yield new SegmentToken($current);
                                    $current = "";
                                }
                                yield new BracketToken($char);
                                break 2;
                            default:
                                if ($i === $length - 1) {
                                    throw new \Exception(sprintf('Unclosed bracket got EOF. Unexpected char "%s" at position "%s" in "%s"', $char, $i, $input), 1677945736908);
                                }
                                $current .= $char;
                        }
                    }
                    break;
                case "]":
                    throw new \Exception(sprintf('Closed array index without opening. Unexpected char "%s" at position "%s" in "%s"', $char, $i, $input), 1677944502145);
                case ".":
                    if ($current !== "") {
                        yield new SegmentToken($current);
                        $current = "";
                    }
                    yield new DotToken($char);
                    break;
                default:
                    $current .= $char;
            }
        }

        if ($current !== "") {
            yield new SegmentToken($current);
        }
    }
}
