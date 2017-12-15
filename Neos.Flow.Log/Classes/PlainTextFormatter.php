<?php
namespace Neos\Flow\Log;

/**
 * Format any value as plain text representation.
 */
class PlainTextFormatter
{
    /**
     * @var mixed
     */
    protected $variable;

    /**
     * Initialize the formatter with any value.
     *
     * @param mixed $variable
     */
    public function __construct($variable)
    {
        $this->variable = $variable;
    }

    /**
     * @param $spaces
     * @return string
     */
    public function format($spaces = 4)
    {
        return $this->renderVariableAsPlaintext($this->variable, $spaces);
    }

    /**
     * Returns a suitable form of a variable (be it a string, array, object ...) for logfile output
     *
     * @param mixed $var The variable
     * @param integer $spaces Indent for this var dump
     * @param int $continuationSpaces Running total indentation (INTERNAL)
     * @return string text output
     */
    protected function renderVariableAsPlaintext($var, $spaces = 4, $continuationSpaces = 0)
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
                $output .= $this->renderKeyValue($k, $v, $spaces, $continuationSpaces);
            }
        }

        if (is_object($var)) {
            $output .= '[' . get_class($var) . ']:' . PHP_EOL;
            foreach (get_object_vars($var) as $objVarName => $objVarValue) {
                $output .= $this->renderKeyValue($objVarName, $objVarValue, $spaces, $continuationSpaces);
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
    protected function renderKeyValue($key, $value, $spaces, $continuationSpaces)
    {
        $currentIndent = str_repeat(' ', ($spaces * 2) + $continuationSpaces);
        return ($currentIndent . $key . ':' . PHP_EOL . $this->renderVariableAsPlaintext($value, $spaces, $continuationSpaces + $spaces));
    }
}
