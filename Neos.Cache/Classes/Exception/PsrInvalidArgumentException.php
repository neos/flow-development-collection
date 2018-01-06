<?php
namespace Neos\Cache\Exception;

use Psr\Cache\InvalidArgumentException as Psr6InvalidArgumentException;
use Psr\SimpleCache\InvalidArgumentException as Psr16InvalidArgumentException;

/**
 *
 */
class PsrInvalidArgumentException extends \InvalidArgumentException implements Psr6InvalidArgumentException, Psr16InvalidArgumentException
{
}
