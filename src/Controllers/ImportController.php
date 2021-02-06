<?php
/**
 * Created by PhpStorm.
 * User: kchapple
 * Date: 7/29/19
 * Time: 11:00 AM
 */

namespace Mi2\Import\Controllers;

use Mi2\DataTable_1_9\SearchFilter as SearchFilter;
use Mi2\Framework\AbstractController;
use Mi2\Framework\Response;
use Mi2\Import\ImportService;
use Mi2\Import\Models\Batch;

class ImportController extends AbstractController
{
    protected $importService;

    public function __construct()
    {
        $this->importService = new ImportService();
    }

    /**
     * Starting point for mss (link from admin menu)
     * Display the view with upload form.
     *
     * Also display a list of previous batches, and when one is selected,
     * display a report of the changes made in that batch.
     */
    public function _action_import()
    {
        // Fetch all the batches for display (so we can see status of their processing)
        // and push them onto the view so our view has access to them for our report.
        $batches_result = Batch::all();
        $batches_array = [];
        while ($batch = sqlFetchArray($batches_result)) {
            $batches_array[]= $batch;
        }

        // Get the column mapping
        $this->view->columns = Batch::getColumns();

        // Specify which view script to display
        $this->view->batches = $batches_array;
        $this->view->importService = $this->importService; // If there are any validation messages, they'll be in here
        $this->view->action_url = $this->getBaseUrl() . '/index.php?action=import!do_import';
        $this->setViewScript('import/import.php');
    }

    /**
     * Get the uploaded spreadsheet
     *
     * Do some basic validation on the file so it won't break
     * the backround service.
     *
     * Store the file on the server and create a row in the DB
     * for this batch with a reference to the file and an indicator
     * that it has not been processed.
     */
    public function _action_do_import()
    {
        if (isset($_POST['do_import'])) {
            // Insert the background service entry in case it doesn't exist.
            ImportService::insertBackgroundService();

            // See if we have an uplaod file
            $file = $_FILES['input_file'];

            // Set the file we're using to create the batch
            // $file contains an assos array with all the file's data
            $this->importService->setUploadFile($file);

            // Do basic validation on file
            // Store messages and display them on the UI
            $valid = $this->importService->validateFile();

            // If the file is valid, store the tmp file in a real document directory.
            // Create the batch, set to "waiting" so the background process
            // can pick it up and process it.
            if (true === $valid) {
                $this->importService->createBatch();
            }
        }

        // Now just do all the things the mss function does for rendering the index page
        $this->_action_import();
    }
}
