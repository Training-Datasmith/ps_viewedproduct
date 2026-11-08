<?php

if (!isset($argv[1])) {
    fwrite(STDERR, "Missing target path\n");
    exit(2);
}

$target = $argv[1];
$mode = isset($argv[2]) ? $argv[2] : 'index';

if ($mode === 'module') {
    if (!class_exists('Module')) {
        class Module
        {
        }
    }

    if (!interface_exists('PrestaShop\\PrestaShop\\Core\\Module\\WidgetInterface')) {
        eval('namespace PrestaShop\\PrestaShop\\Core\\Module { interface WidgetInterface {} }');
    }
} else {
    if (!defined('_PS_VERSION_')) {
        define('_PS_VERSION_', '8.1.7.0');
    }
}

include $target;
echo 'SENTINEL';
