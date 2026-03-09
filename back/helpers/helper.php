<?php

// REPLACE ANY INCLUSION OF helper/helper.php BY Core/init.php:
include_once dirname(__DIR__).DIRECTORY_SEPARATOR.'Core'.DIRECTORY_SEPARATOR.'init.php';

$trace = debug_backtrace();
$fileCallingThisFile = $trace[0]['file'];

acym_enqueueMessage(
    'One of your extensions is calling a deprecated AcyMailing file that will be removed in the version 11.0.<br>Please ask the developer to update their integration to avoid any interruption of service for your website.<br><br><b>File causing this error:</b> '.$fileCallingThisFile,
    'error',
    true,
    [
        [
            'name' => 'helper_deprecation',
            'removable' => 1,
        ],
    ],
    false
);
