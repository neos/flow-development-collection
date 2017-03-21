<?php
namespace Neos\Flow\Error;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Configuration\ConfigurationManager;
use Neos\Flow\ObjectManagement\Configuration\Configuration;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\ObjectManagement\Proxy\ProxyInterface;
use Neos\Utility\Arrays;
use Neos\Utility\ObjectAccess;

/**
 * A debugging utility class
 *
 * @Flow\Proxy(false)
 */
class Debugger
{
    /**
     * @var ObjectManagerInterface
     */
    protected static $objectManager;

    /**
     *
     * @var array
     */
    protected static $renderedObjects = [];

    /**
     * Hardcoded list of Flow class names (regex) which should not be displayed during debugging.
     * This is a fallback in case the classes could not be fetched from the configuration
     *
     * @var array
     */
    protected static $ignoredClassesFallback = [
        'Neos\\\\Flow\\\\Aop.*' => true,
        'Neos\\\\Flow\\\\Cac.*' => true,
        'Neos\\\\Flow\\\\Core\\\\.*' => true,
        'Neos\\\\Flow\\\\Con.*' => true,
        'Neos\\\\Flow\\\\Http\\\\RequestHandler' => true,
        'Neos\\\\Flow\\\\Uti.*' => true,
        'Neos\\\\Flow\\\\Mvc\\\\Routing.*' => true,
        'Neos\\\\Flow\\\\Log.*' => true,
        'Neos\\\\Flow\\\\Obj.*' => true,
        'Neos\\\\Flow\\\\Pac.*' => true,
        'Neos\\\\Flow\\\\Persistence\\\\(?!Doctrine\\\\Mapping).*' => true,
        'Neos\\\\Flow\\\\Pro.*' => true,
        'Neos\\\\Flow\\\\Ref.*' => true,
        'Neos\\\\Flow\\\\Sec.*' => true,
        'Neos\\\\Flow\\\\Sig.*' => true,
        'Neos\\\\Flow\\\\.*ResourceManager' => true,
        'Neos\\\\FluidAdaptor\\\\.*' => true,
        '.+Service$' => true,
        '.+Repository$' => true,
        'PHPUnit_Framework_MockObject_InvocationMocker' => true
    ];

    /**
     * @var string
     */
    protected static $ignoredClassesRegex = '';

    /**
     * @var string
     */
    protected static $blacklistedPropertyNames = '/
		(Flow_Aop_.*)
		/xs';

    /**
     * Is set to TRUE once the CSS file is included in the current page to prevent double inclusions of the CSS file.
     *
     * @var boolean
     */
    public static $stylesheetEchoed = false;

    /**
     * Injects the Object Manager
     *
     * @param ObjectManagerInterface $objectManager
     * @return void
     */
    public static function injectObjectManager(ObjectManagerInterface $objectManager)
    {
        self::$objectManager = $objectManager;
    }

    /**
     * Clear the state of the debugger
     *
     * @return void
     */
    public static function clearState()
    {
        self::$renderedObjects = [];
        self::$ignoredClassesRegex = '';
    }

    /**
     * Renders a dump of the given variable
     *
     * @param mixed $variable
     * @param integer $level
     * @param boolean $plaintext
     * @param boolean $ansiColors
     * @return string
     */
    public static function renderDump($variable, $level, $plaintext = false, $ansiColors = false)
    {
        if ($level > 50) {
            return 'RECURSION ... ' . chr(10);
        }
        if (is_string($variable)) {
            $croppedValue = (strlen($variable) > 2000) ? substr($variable, 0, 2000) . '…' : $variable;
            if ($plaintext) {
                $dump = 'string ' . self::ansiEscapeWrap('"' . $croppedValue . '"', '33', $ansiColors) . ' (' . strlen($variable) . ')';
            } else {
                $dump = sprintf('\'<span class="debug-string">%s</span>\' (%s)', htmlspecialchars($croppedValue), strlen($variable));
            }
        } elseif (is_numeric($variable)) {
            $dump = sprintf('%s %s', gettype($variable), self::ansiEscapeWrap($variable, '35', $ansiColors));
        } elseif (is_array($variable)) {
            $dump = self::renderArrayDump($variable, $level + 1, $plaintext, $ansiColors);
        } elseif (is_object($variable)) {
            $dump = self::renderObjectDump($variable, $level + 1, true, $plaintext, $ansiColors);
        } elseif (is_bool($variable)) {
            $dump = $variable ? self::ansiEscapeWrap('TRUE', '32', $ansiColors) : self::ansiEscapeWrap('FALSE', '31', $ansiColors);
        } elseif (is_null($variable) || is_resource($variable)) {
            $dump = gettype($variable);
        } else {
            $dump = '[unhandled type]';
        }

        return $dump;
    }

    /**
     * Renders a dump of the given array
     *
     * @param array $array
     * @param integer $level
     * @param boolean $plaintext
     * @param boolean $ansiColors
     * @return string
     */
    protected static function renderArrayDump($array, $level, $plaintext = false, $ansiColors = false)
    {
        $type = is_array($array) ? 'array' : get_class($array);
        $dump = $type . (count($array) ? '(' . count($array) . ')' : '(empty)');
        foreach ($array as $key => $value) {
            $dump .= chr(10) . str_repeat(' ', $level) . self::renderDump($key, 0, $plaintext, $ansiColors) . ' => ';
            $dump .= self::renderDump($value, $level + 1, $plaintext, $ansiColors);
        }

        return $dump;
    }

    /**
     * Renders a dump of the given object
     *
     * @param object $object
     * @param integer $level
     * @param boolean $renderProperties
     * @param boolean $plaintext
     * @param boolean $ansiColors
     * @return string
     */
    protected static function renderObjectDump($object, $level, $renderProperties = true, $plaintext = false, $ansiColors = false)
    {
        $dump = '';
        $scope = '';
        $additionalAttributes = '';

        if ($object instanceof \Doctrine\Common\Collections\Collection || $object instanceof \ArrayObject) {
            return self::renderArrayDump(\Doctrine\Common\Util\Debug::export($object, 3), $level, $plaintext, $ansiColors);
        }

        // Objects returned from Doctrine's Debug::export function are stdClass with special properties:
        try {
            $objectIdentifier = ObjectAccess::getProperty($object, 'Persistence_Object_Identifier', true);
        } catch (\Neos\Utility\Exception\PropertyNotAccessibleException $exception) {
            $objectIdentifier = spl_object_hash($object);
        }
        $className = ($object instanceof \stdClass && isset($object->__CLASS__)) ? $object->__CLASS__ : get_class($object);
        if (isset(self::$renderedObjects[$objectIdentifier]) || preg_match(self::getIgnoredClassesRegex(), $className) !== 0) {
            $renderProperties = false;
        }
        self::$renderedObjects[$objectIdentifier] = true;

        if (self::$objectManager !== null) {
            $objectName = self::$objectManager->getObjectNameByClassName(get_class($object));
            if ($objectName !== false) {
                switch (self::$objectManager->getScope($objectName)) {
                    case Configuration::SCOPE_PROTOTYPE:
                        $scope = 'prototype';
                        break;
                    case Configuration::SCOPE_SINGLETON:
                        $scope = 'singleton';
                        break;
                    case Configuration::SCOPE_SESSION:
                        $scope = 'session';
                        break;
                }
            } else {
                $additionalAttributes .= ' debug-unregistered';
            }
        }

        if ($renderProperties === true && !$plaintext) {
            if ($scope === '') {
                $scope = 'prototype';
            }
            $scope .= '<a id="o' . $objectIdentifier . '"></a>';
        }

        if ($plaintext) {
            $dump .= $className;
            $dump .= ($scope !== '') ? ' ' . self::ansiEscapeWrap($scope, '44;37', $ansiColors) : '';
        } else {
            $dump .= '<span class="debug-object' . $additionalAttributes . '" title="' . $objectIdentifier . '">' . $className . '</span>';
            $dump .= ($scope !== '') ? '<span class="debug-scope">' . $scope . '</span>' : '';
        }

        if (property_exists($object, 'Persistence_Object_Identifier')) {
            $persistenceIdentifier = $objectIdentifier;
            $persistenceType = 'persistable';
        } elseif ($object instanceof \Closure) {
            $persistenceIdentifier = 'n/a';
            $persistenceType = 'closure';
        } else {
            $persistenceIdentifier = 'unknown';
            $persistenceType = 'object';
        }

        if ($plaintext) {
            $dump .= ' ' . self::ansiEscapeWrap($persistenceType, '42;37', $ansiColors);
        } else {
            $dump .= '<span class="debug-ptype" title="' . $persistenceIdentifier . '">' . $persistenceType . '</span>';
        }

        if ($object instanceof ProxyInterface || (property_exists($object, '__IS_PROXY__') && $object->__IS_PROXY__ === true)) {
            if ($plaintext) {
                $dump .= ' ' . self::ansiEscapeWrap('proxy', '41;37', $ansiColors);
            } else {
                $dump .= '<span class="debug-proxy" title="' . $className . '">proxy</span>';
            }
        }

        if ($renderProperties === true) {
            if ($object instanceof \SplObjectStorage) {
                $dump .= ' (' . (count($object) ?: 'empty') . ')';
                foreach ($object as $value) {
                    $dump .= chr(10);
                    $dump .= str_repeat(' ', $level);
                    $dump .= self::renderObjectDump($value, 0, false, $plaintext, $ansiColors);
                }
            } else {
                $objectReflection = new \ReflectionObject($object);
                $properties = $objectReflection->getProperties();
                foreach ($properties as $property) {
                    if (preg_match(self::$blacklistedPropertyNames, $property->getName())) {
                        continue;
                    }
                    $dump .= chr(10);
                    $dump .= str_repeat(' ', $level) . ($plaintext ? '' : '<span class="debug-property">') . self::ansiEscapeWrap($property->getName(), '36', $ansiColors) . ($plaintext ? '' : '</span>') . ' => ';
                    $property->setAccessible(true);
                    $value = $property->getValue($object);
                    if (is_array($value)) {
                        $dump .= self::renderDump($value, $level + 1, $plaintext, $ansiColors);
                    } elseif (is_object($value)) {
                        $dump .= self::renderObjectDump($value, $level + 1, true, $plaintext, $ansiColors);
                    } else {
                        $dump .= self::renderDump($value, $level, $plaintext, $ansiColors);
                    }
                }
            }
        } elseif (isset(self::$renderedObjects[$objectIdentifier])) {
            if (!$plaintext) {
                $dump = '<a href="#o' . $objectIdentifier . '" onclick="document.location.hash=\'#o' . $objectIdentifier . '\'; return false;" class="debug-seeabove" title="see above">' . $dump . '</a>';
            }
        }

        return $dump;
    }

    /**
     * Renders some backtrace
     *
     * @param array $trace The trace
     * @param boolean $includeCode Include code snippet
     * @param boolean $plaintext
     * @return string Backtrace information
     */
    public static function getBacktraceCode(array $trace, $includeCode = true, $plaintext = false)
    {
        if ($plaintext) {
            return static::getBacktraceCodePlaintext($trace, $includeCode);
        }
        $backtraceCode = '<ol class="Flow-Debug-Backtrace" reversed>';
        foreach ($trace as $index => $step) {
            $backtraceCode .= '<li class="Flow-Debug-Backtrace-Step">';
            $class = isset($step['class']) ? $step['class'] . '<span class="color-muted">::</span>' : '';

            $arguments = '';
            if (isset($step['args']) && is_array($step['args'])) {
                foreach ($step['args'] as $argument) {
                    $arguments .= (strlen($arguments) === 0) ? '' : '<span class="color-muted">,</span> ';
                    $arguments .= '<span class="color-text-inverted">';
                    if (is_object($argument)) {
                        $arguments .= '<em>' . get_class($argument) . '</em>';
                    } elseif (is_string($argument)) {
                        $preparedArgument = (strlen($argument) < 100) ? $argument : substr($argument, 0, 50) . '…' . substr($argument, -50);
                        $preparedArgument = htmlspecialchars($preparedArgument);
                        $preparedArgument = str_replace('…', '<span class="color-muted">…</span>', $preparedArgument);
                        $preparedArgument = str_replace("\n", '<span class="color-muted">⏎</span>', $preparedArgument);
                        $arguments .= '"<span title="' . htmlspecialchars($argument) . '">' . $preparedArgument . '</span>"';
                    } elseif (is_numeric($argument)) {
                        $arguments .= (string)$argument;
                    } elseif (is_bool($argument)) {
                        $arguments .= ($argument === true ? 'TRUE' : 'FALSE');
                    } elseif (is_array($argument)) {
                        $arguments .= sprintf(
                            '<em title="%s">array|%d|</em>',
                            htmlspecialchars(self::renderArrayDump($argument, 0, true)),
                            count($argument)
                        );
                    } else {
                        $arguments .= '<em>' . gettype($argument) . '</em>';
                    }
                    $arguments .= '</span>';
                }
            }

            $backtraceCode .= '<pre class="Flow-Debug-Backtrace-Step-Function">';
            $backtraceCode .= $class . $step['function'] . '<span class="color-muted">(' . $arguments . ')</span>';
            $backtraceCode .= '</pre>';

            if (isset($step['file']) && $includeCode) {
                $backtraceCode .= self::getCodeSnippet($step['file'], $step['line']);
            }
            $backtraceCode .= '</li>';
        }
        $backtraceCode .= '</ol>';
        return $backtraceCode;
    }

    /**
     * @param array $trace
     * @param bool $includeCode
     * @return string
     */
    protected static function getBacktraceCodePlaintext(array $trace, $includeCode = true)
    {
        $backtraceCode = '';
        foreach ($trace as $index => $step) {
            $class = isset($step['class']) ? $step['class'] . '::' : '';

            $arguments = '';
            if (isset($step['args']) && is_array($step['args'])) {
                foreach ($step['args'] as $argument) {
                    $arguments .= (strlen($arguments) === 0) ? '' : ', ';
                    if (is_object($argument)) {
                        $arguments .= get_class($argument);
                    } elseif (is_string($argument)) {
                        $preparedArgument = (strlen($argument) < 100) ? $argument : substr($argument, 0, 50) . '…' . substr($argument, -50);
                        $arguments .= '"' . $preparedArgument . '"';
                    } elseif (is_numeric($argument)) {
                        $arguments .= (string)$argument;
                    } elseif (is_bool($argument)) {
                        $arguments .= ($argument === true ? 'TRUE' : 'FALSE');
                    } elseif (is_array($argument)) {
                        $arguments .= 'array|' . count($argument) . '|';
                    } else {
                        $arguments .= gettype($argument);
                    }
                }
            }

            $backtraceCode .= (count($trace) - $index) . ' ' . $class . $step['function'] . '(' . $arguments . ')';

            if (isset($step['file']) && $includeCode) {
                $backtraceCode .= self::getCodeSnippetPlaintext($step['file'], $step['line']);
            }
            $backtraceCode .= PHP_EOL;
        }

        return $backtraceCode;
    }

    /**
     * Returns a code snippet from the specified file.
     *
     * @param string $filePathAndName Absolute path and filename of the PHP file
     * @param integer $lineNumber Line number defining the center of the code snippet
     * @param boolean $plaintext
     * @return string The code snippet
     */
    public static function getCodeSnippet($filePathAndName, $lineNumber, $plaintext = false)
    {
        if ($plaintext) {
            return static::getCodeSnippetPlaintext($filePathAndName, $lineNumber);
        }

        $codeSnippet = '<br />';
        if (@file_exists($filePathAndName)) {
            $phpFile = @file($filePathAndName);
            if (!is_array($phpFile)) {
                return $codeSnippet;
            }
            $filePaths = static::findProxyAndShortFilePath($filePathAndName);

            $codeSnippet = '<div class="Flow-Debug-CodeSnippet">';
            $filePathReal = $filePaths['proxy'] !== '' ? $filePaths['proxy'] : $filePaths['short'];
            $codeSnippet .= '<div class="Flow-Debug-CodeSnippet-File">' . $filePathReal . '</div>';
            if ($filePaths['proxy'] !== '') {
                $codeSnippet .= '<div class="Flow-Debug-CodeSnippet-File">Original File: ' . $filePaths['short'] . '</div>';
            }

            $codeSnippet .= '<div class="Flow-Debug-CodeSnippet-Code">';

            $startLine = ($lineNumber > 2) ? ($lineNumber - 2) : 1;
            $endLine = ($lineNumber < (count($phpFile) - 2)) ? ($lineNumber + 3) : count($phpFile) + 1;
            for ($line = $startLine; $line < $endLine; $line++) {
                $codeSnippet .= '<pre class="' . (($line === $lineNumber) ? 'Flow-Debug-CodeSnippet-Code-Highlighted' : '') . '">';
                $codeLine = str_replace("\t", ' ', $phpFile[$line - 1]);
                $codeSnippet .= sprintf('%05d', $line) . ': ';
                $codeSnippet .= htmlspecialchars($codeLine);
                $codeSnippet .= '</pre>';
            }
            $codeSnippet .= '</div></div>';
        }

        return $codeSnippet;
    }

    protected static function getCodeSnippetPlaintext($filePathAndName, $lineNumber)
    {
        $codeSnippet = PHP_EOL;
        if (@file_exists($filePathAndName)) {
            $phpFile = @file($filePathAndName);
            if (!is_array($phpFile)) {
                return $codeSnippet;
            }

            $filePaths = static::findProxyAndShortFilePath($filePathAndName);
            $startLine = ($lineNumber > 2) ? ($lineNumber - 2) : 1;
            $endLine = ($lineNumber < (count($phpFile) - 2)) ? ($lineNumber + 3) : count($phpFile) + 1;
            if ($endLine > $startLine) {
                $codeSnippet = PHP_EOL . $filePaths['short'] . ($filePaths['proxy'] !== '' ? ' (actually in ' . $filePaths['proxy'] . ')' : '') . ':' . PHP_EOL;
                for ($line = $startLine; $line < $endLine; $line++) {
                    $codeSnippet .= sprintf('%05d', $line) . ': ';
                    $codeSnippet .= str_replace("\t", ' ', $phpFile[$line - 1]);
                }
            }
        }

        return $codeSnippet;
    }

    /**
     * @param string $file
     * @return array
     */
    public static function findProxyAndShortFilePath($file)
    {
        $flowRoot = defined('FLOW_PATH_ROOT') ? FLOW_PATH_ROOT : '';
        $originalPath = $file;
        $proxyClassPathPosition = strpos($file, 'Flow_Object_Classes/');
        if ($proxyClassPathPosition) {
            $fileContent = @file($file);
            $originalPath = trim(substr($fileContent[count($fileContent) - 2], 19));
        }

        $originalPath = str_replace($flowRoot, '', $originalPath);
        $file = str_replace($flowRoot, '', $file);

        return [
            'short' => $originalPath,
            'proxy' => ($originalPath !== $file) ? $file : ''
        ];
    }

    /**
     * Wrap a string with the ANSI escape sequence for colorful output
     *
     * @param string $string The string to wrap
     * @param string $ansiColors The ansi color sequence (e.g. "1;37")
     * @param boolean $enable If FALSE, the raw string will be returned
     * @return string The wrapped or raw string
     */
    protected static function ansiEscapeWrap($string, $ansiColors, $enable = true)
    {
        if ($enable) {
            return "\x1B[" . $ansiColors . 'm' . $string . "\x1B[0m";
        } else {
            return $string;
        }
    }

    /**
     * Tries to load the 'Neos.Flow.error.debugger.ignoredClasses' setting
     * to build a regular expression that can be used to filter ignored class names
     * If settings can't be loaded it uses self::$ignoredClassesFallback.
     *
     * @return string
     */
    public static function getIgnoredClassesRegex()
    {
        if (self::$ignoredClassesRegex !== '') {
            return self::$ignoredClassesRegex;
        }

        $ignoredClassesConfiguration = self::$ignoredClassesFallback;
        $ignoredClasses = [];

        if (self::$objectManager instanceof ObjectManagerInterface) {
            $configurationManager = self::$objectManager->get(ConfigurationManager::class);
            if ($configurationManager instanceof ConfigurationManager) {
                $ignoredClassesFromSettings = $configurationManager->getConfiguration('Settings', 'Neos.Flow.error.debugger.ignoredClasses');
                if (is_array($ignoredClassesFromSettings)) {
                    $ignoredClassesConfiguration = Arrays::arrayMergeRecursiveOverrule($ignoredClassesConfiguration, $ignoredClassesFromSettings);
                }
            }
        }

        foreach ($ignoredClassesConfiguration as $classNamePattern => $active) {
            if ($active === true) {
                $ignoredClasses[] = $classNamePattern;
            }
        }

        self::$ignoredClassesRegex = sprintf('/^%s$/xs', implode('$|^', $ignoredClasses));

        return self::$ignoredClassesRegex;
    }
}

namespace Neos\Flow;

use Neos\Flow\Error\Debugger;

/**
 * A var_dump function optimized for Flow's object structures
 *
 * @param mixed $variable The variable to display a dump of
 * @param string $title optional custom title for the debug output
 * @param boolean $return if TRUE, the dump is returned for displaying it embedded in custom HTML. If FALSE (default), the variable dump is directly displayed.
 * @param boolean $plaintext If TRUE, the dump is in plain text, if FALSE the debug output is in HTML format. If not specified, the mode is guessed from FLOW_SAPITYPE
 * @return void|string if $return is TRUE, the variable dump is returned. By default, the dump is directly displayed, and nothing is returned.
 * @api
 */
function var_dump($variable, $title = null, $return = false, $plaintext = null)
{
    if ($plaintext === null) {
        $plaintext = (FLOW_SAPITYPE === 'CLI');
        $ansiColors = $plaintext && DIRECTORY_SEPARATOR === '/';
    } else {
        $ansiColors = false;
    }

    if ($title === null) {
        $title = 'Flow Variable Dump';
    }
    if ($ansiColors) {
        $title = "\x1B[1m" . $title . "\x1B[0m";
    }
    Debugger::clearState();

    if (!$plaintext && Debugger::$stylesheetEchoed === false) {
        echo '<style type="text/css">' . file_get_contents('resource://Neos.Flow/Public/Error/Debugger.css') . '</style>';
        Debugger::$stylesheetEchoed = true;
    }

    if ($plaintext) {
        $output = $title . chr(10) . Debugger::renderDump($variable, 0, true, $ansiColors) . chr(10) . chr(10);
    } else {
        $output = '
			<div class="Flow-Error-Debugger-VarDump ' . ($return ? 'Flow-Error-Debugger-VarDump-Inline' : 'Flow-Error-Debugger-VarDump-Floating') . '">
				<div class="Flow-Error-Debugger-VarDump-Top">
					' . htmlspecialchars($title) . '
				</div>
				<div class="Flow-Error-Debugger-VarDump-Center">
					<pre dir="ltr">' . Debugger::renderDump($variable, 0, false, false) . '</pre>
				</div>
			</div>
		';
    }

    if ($return === true) {
        return $output;
    } else {
        echo $output;
    }
}
