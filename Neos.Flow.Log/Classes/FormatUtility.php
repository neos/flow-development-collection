<?php
namespace Neos\Flow\Log;

/**
 *
 */
abstract class FormatUtility
{
    /**
     * Returns a suitable form of a variable (be it a string, array, object ...) for logfile output
     *
     * @param mixed $var The variable
     * @param integer $spaces Indent for this var dump
     * @param int $continuationSpaces Running total indentation
     * @return string text output
     */
    public static function renderVariableAsPlaintext($var, $spaces = 4, $continuationSpaces = 0)
    {
        $currentIndent = str_repeat(' ', $spaces + $continuationSpaces);
        if ($continuationSpaces > 100) {
            return null;
        }

        $output = '';
        $output .= $currentIndent . '[' . gettype($var) . '] => ';
        if (is_string($var) || is_numeric($var)) {
            $output .= $var;
        }
        if (is_bool($var)) {
            $output .= $var ? 'true' : 'false';
        }
        if (is_null($var)) {
            $output .= '␀';
        }

        if (is_array($var)) {
            $output .= PHP_EOL;
            foreach ($var as $k => $v) {
                $output .= static::renderKeyValue($k, $v, $spaces, $continuationSpaces);
            }
        }

        if (is_object($var)) {
            $output .= '[' . get_class($var) . ']:' . PHP_EOL;
            foreach (get_object_vars($var) as $objVarName => $objVarValue) {
                $output .= static::renderKeyValue($objVarName, $objVarValue, $spaces, $continuationSpaces);
            }
        }

        $output .= PHP_EOL;

        return $output;
    }

    /**
     * @param mixed $key
     * @param mixed $value
     * @param int $spaces
     * @param int $continuationSpaces
     * @return string
     */
    protected static function renderKeyValue($key, $value, $spaces, $continuationSpaces)
    {
        $currentIndent = str_repeat(' ', ($spaces * 2) + $continuationSpaces);
        return ($currentIndent . $key . ':' . PHP_EOL . static::renderVariableAsPlaintext($value, $spaces, $continuationSpaces + $spaces));
    }
}
