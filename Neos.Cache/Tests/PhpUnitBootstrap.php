<?php
if (defined('FLOW_PATH_ROOT')) {
    include_once(FLOW_PATH_ROOT . '/Packages/Libraries/autoload.php');
}

if (is_file('Packages/Libraries/autoload.php')) {
    include_once('Packages/Libraries/autoload.php');
}

if (is_file('vendor/autoload.php')) {
    include_once('vendor/autoload.php');
}
