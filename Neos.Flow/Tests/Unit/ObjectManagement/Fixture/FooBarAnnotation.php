<?php
namespace Neos\Flow\Tests\Unit\ObjectManagement\Proxy;

/**
 * fixture "annotation" for the above test case
 */
class FooBarAnnotation
{
    public $value;

    public function __construct($value = 1.2)
    {
        $this->value = $value;
    }
}
