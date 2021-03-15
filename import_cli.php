<?php

use Mi2\Import\ImportManager;

set_time_limit(0);

$ignoreAuth = true;
$fake_register_globals = false;
$sanitize_all_escapes = true;

require_once __DIR__.'/../../../globals.php';

// read the file or directory from the command line
$file_or_dir = $argv[1];
$importManager = $GLOBALS["kernel"]->getContainer()->get('import-manager');
if (is_dir($file_or_dir)) {
    // Create a new batch for each file in the dir
    foreach (glob($file_or_dir . '/*.*') as $file) {
        $importManager->createBatchFromFile($file);
    }
} else {
    $importManager->createBatchFromFile($file_or_dir);
}

// Execute processing of the file(s)
$importManager->execute();
