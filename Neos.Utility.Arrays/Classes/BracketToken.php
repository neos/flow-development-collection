<?php

namespace Neos\Utility;

class BracketToken
{
    public function __construct(
        public string $value
    ) {
    }

    public function isOpen()
    {
        return $this->value === "[";
    }

    public function isClosed()
    {
        return $this->value === "]";
    }
}
