<?php
namespace Neos\Flow\Utility;

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
 * This utility class can be used to extract information about PHP files without having to instantiate/reflect classes.
 *
 * Usage:
 *
 * // extract the FQN e.g. "Some\Namespace\SomeClassName"
 * $fullyQualifiedClassName = (new PhpAnalyzer($fileContents))->extractFullyQualifiedClassName();
 *
 * // extract the namespace "Some\Namespace"
 * $namespace = (new PhpAnalyzer($fileContents))->extractNamespace();
 *
 * // extract just the class name "SomeClassName"
 * $className = (new PhpAnalyzer($fileContents))->extractClassName();
 */
class PhpAnalyzer
{
    /**
     * @var string
     */
    protected $phpCode;

    /**
     * @param string $phpCode
     */
    public function __construct($phpCode)
    {
        $this->phpCode = $phpCode;
    }

    /**
     * Extracts the Fully Qualified Class name from the given PHP code
     *
     * @return string FQN in the format "Some\Fully\Qualified\ClassName" or NULL if no class was detected
     */
    public function extractFullyQualifiedClassName()
    {
        $fullyQualifiedClassName = $this->extractClassName();
        if ($fullyQualifiedClassName === null) {
            return null;
        }
        $namespace = $this->extractNamespace();
        if ($namespace !== null) {
            $fullyQualifiedClassName = $namespace . '\\' . $fullyQualifiedClassName;
        }
        return $fullyQualifiedClassName;
    }

    /**
     * Extracts the PHP namespace from the given PHP code
     *
     * @return string the PHP namespace in the form "Some\Namespace" (w/o leading backslash) - or NULL if no namespace modifier was found
     */
    public function extractNamespace()
    {
        $namespaceParts = [];
        $tokens = token_get_all($this->phpCode);
        $numberOfTokens = count($tokens);
        for ($i = 0; $i < $numberOfTokens; $i++) {
            $token = $tokens[$i];
            if (is_string($token) || $token[0] !== T_NAMESPACE) {
                continue;
            }
            for (++$i; $i < $numberOfTokens; $i++) {
                $token = $tokens[$i];
                if (is_string($token)) {
                    break;
                }
                list($type, $value) = $token;
                if ($type === T_STRING) {
                    $namespaceParts[] = $value;
                    continue;
                }
                if ($type !== T_NS_SEPARATOR && $type !== T_WHITESPACE) {
                    break;
                }
            }
            break;
        }
        if ($namespaceParts === []) {
            return null;
        }
        return implode('\\', $namespaceParts);
    }

    /**
     * Extracts the className of the given PHP code
     * Note: This only returns the class name without namespace, @see extractFullyQualifiedClassName()
     *
     * @return string
     */
    public function extractClassName()
    {
        $tokens = token_get_all($this->phpCode);
        $numberOfTokens = count($tokens);
        for ($i = 0; $i < $numberOfTokens; $i++) {
            $token = $tokens[$i];
            if (is_string($token) || $token[0] !== T_CLASS) {
                continue;
            }
            for (++$i; $i < $numberOfTokens; $i++) {
                $token = $tokens[$i];
                if (is_string($token)) {
                    break;
                }
                list($type, $value) = $token;
                if ($type === T_STRING) {
                    return $value;
                }
                if ($type !== T_WHITESPACE) {
                    break;
                }
            }
        }
        return null;
    }
}
