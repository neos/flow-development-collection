<?php
namespace Neos\Eel\Validation;

use Neos\Eel\EelParser;
use Neos\Flow\Validation\Validator\AbstractValidator;

/**
 * A validator which checks for the correct syntax of an eel expression (without the wrapping ${…}).
 * This is basically done by giving it to the parser and checking if its result is valid.
 *
 * @api
 */
class ExpressionSyntaxValidator extends AbstractValidator
{
    /**
     * Check if $value is valid. If it is not valid, needs to add an error
     * to Result.
     *
     * @param mixed $value
     * @return void
     * @api
     */
    protected function isValid($value)
    {
        /** @phpstan-ignore-next-line */
        $parser = new EelParser($value);
        $result = $parser->match_Expression();

        if ($result === false) {
            $this->addError('Expression "%s" could not be parsed.', 1421940748, [$value]);
            /** @phpstan-ignore-next-line */
        } elseif ($parser->pos !== strlen($value)) {
            /** @phpstan-ignore-next-line */
            $this->addError('Expression "%s" could not be parsed. Error starting at character %d: "%s".', 1421940760, [$value, $parser->pos, substr($value, $parser->pos)]);
        }
    }
}
