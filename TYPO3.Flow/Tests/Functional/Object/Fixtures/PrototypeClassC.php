<?php
namespace TYPO3\Flow\Tests\Functional\Object\Fixtures;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

/**
 * A class of scope prototype (but without explicit scope annotation)
 */
class PrototypeClassC
{
    /**
     * @var string
     */
    public $settingsArgument;

    /**
     * @param string $settingsArgument
     */
    public function __construct($settingsArgument)
    {
        $this->settingsArgument = $settingsArgument;
    }

    /**
     * @return string
     */
    public function getSettingsArgument()
    {
        return $this->settingsArgument;
    }
}
