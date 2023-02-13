<?php
/*
 * This file configures dynamic return type support for factory methods in PhpStorm
 * see https://www.jetbrains.com/help/phpstorm/ide-advanced-metadata.html
 */

namespace PHPSTORM_META {

    override(
        \Neos\Flow\ObjectManagement\ObjectManagerInterface::get(),
        map(['' => '@'])
    );

    override(
        \Neos\Flow\Core\Bootstrap::getEarlyInstance(),
        map(['' => '@'])
    );
}
