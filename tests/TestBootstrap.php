<?php

if(false == defined('PHPUNIT_PROJECT_DIR')) {
	define('PHPUNIT_PROJECT_DIR', dirname(dirname(__FILE__)));
}
if(false == defined('PHPUNIT_TESTS_DIR')) {
	define('PHPUNIT_TESTS_DIR', dirname(__FILE__));
}

require_once __DIR__ . '/../vendor/autoload.php';
