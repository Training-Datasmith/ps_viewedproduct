<?php

if (!defined('_PS_VERSION_')) {
    define('_PS_VERSION_', '8.1.7.0');
}
if (!defined('_DB_PREFIX_')) {
    define('_DB_PREFIX_', 'ps_');
}

require dirname(__FILE__) . '/stubs.php';

Stubs::reset();

$moduleFile = dirname(dirname(__DIR__)) . '/ps_viewedproduct.php';
if (!is_file($moduleFile)) {
    throw new RuntimeException('Missing ps_viewedproduct.php bootstrap target.');
}

require $moduleFile;
