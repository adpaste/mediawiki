<?php

define( 'MEDIAWIKI', true );
define( 'MW_PHPUNIT_TEST', true );

// Inject test configuration via callback, bypassing LocalSettings.php
define( 'MW_CONFIG_CALLBACK', '\TestSetup::applyInitialConfig');

// these variables must be defined before setup runs
$GLOBALS['IP'] = __DIR__ . '/../..';
$GLOBALS['wgCommandLineMode'] = true;

require_once __DIR__ . '/../common/TestSetup.php';
require_once __DIR__ . '/../../includes/Setup.php';
require_once __DIR__ . '/../common/TestsAutoLoader.php';

// Remove MWExceptionHandler's handling of PHP errors to allow PHPUnit to replace them
restore_error_handler();
