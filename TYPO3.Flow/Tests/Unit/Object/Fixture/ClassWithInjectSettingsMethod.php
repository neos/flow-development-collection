<?php
namespace TYPO3\Flow\Tests\Object\Fixture;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

/**
 */
class ClassWithInjectSettingsMethod
{
    public $settings;

    /**
     * Inject the settings of the Flow package
     *
     * @param array $settings
     */
    public function injectSettings(array $settings)
    {
        $this->settings = $settings;
    }
}
