<?php

use Mi2\Import\ImportManager;

set_time_limit(0);

$ignoreAuth = true;
$fake_register_globals = false;
$sanitize_all_escapes = true;

require_once __DIR__.'/../../../globals.php';
// require_once __DIR__.'/vendor/autoload.php';

/**
 * This function is called by background services,
 * processes the spreadsheets in the aa_import_batch
 * tabletable
 */
function start_import()
{
    // Execute processing of the file
    $importManager = $GLOBALS["kernel"]->getContainer()->get('import-manager');
    $importManager->execute();
}

