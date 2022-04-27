<?php

namespace Mi2\Import;

use Mi2\Import\Interfaces\ConventionRequiredInterface;
use Mi2\Import\Interfaces\ImporterServiceInterface;
use Mi2\Import\Interfaces\FileConventionRequiredInterface;
use Mi2\Import\Models\Batch;
use Mi2\Import\Models\Logger;
use Mi2\Import\Models\Response;
use Mi2\Import\Traits\InteractsWithLogger;

class ImportManager
{
    use InteractsWithLogger;

    /**
     * Registered import services
     *
     * @var array
     */
    protected $services = [];

    protected $file;

    // These are for while running batches of import
    protected $current_batch_id;
    protected $messages = [];
    protected $num_inserted;
    protected $num_modified;

    public function __construct()
    {
        $this->logger = new Logger();
    }

    public function register(ImporterServiceInterface $service)
    {
        $this->services[] = $service;
    }

    /**
     * Make the correct importer based on file type
     *
     * @param $file
     */
    public function makeImporter(Batch $batch)
    {
        $importer = new NullImporter();
        $path_parts = pathinfo($batch->getUserFilename());
        $extension = strtolower($path_parts['extension']);

        // Reset messages, create a new logger for each batch
        $this->logger = new Logger();
        $importer->setLogger($this->logger);
        $importerFound = false;
        // Search for the appropriate importer for this file
        foreach ($this->services as $service) {
            if ($service->supports($extension)) {
                // Check to see if the importer has a file-naming convention requirement
                if ($service instanceof ConventionRequiredInterface) {
                    $service->setLogger($this->logger);
                    if ($service->matchesConvention($batch)) {
                        $importer = $service;
                        $importerFound = true;
                        break;
                    } else {
                        // This message will be displayed if no matching importer is found
                        $importerClass = get_class($service);
                        $importer->getLogger()->addMessage("Importer: `{$importerClass}` supports the `{$extension}` but does not support the required file-naming convention.");
                    }
                } else {
                    // the importer doesn't require naming convention, supports file ext is good enough
                    $importer = $service;
                    $importerFound = true;
                    break;
                }
            }
        }

        if ($importerFound === true) {
            // Reset messages, create a new logger for each batch
            $this->logger = new Logger();
            $importer->setLogger($this->logger);
        }

        return $importer;
    }

    public function execute()
    {
        // First, find all the batches that are in 'waiting' state
        $waiting_batches = Batch::fetchByStatus(Batch::STATUS_WAIT);

        while ($row = sqlFetchArray($waiting_batches)) {
            $batch = new Batch($row);
            $this->current_batch_id = $batch->getId();

            Batch::update($batch->getId(), [
                'status' => Batch::STATUS_PROCESSING,
                'start_datetime' => date('Y-m-d H:i:s')
            ]);

            // if the file is an image, run the image importer, if it's a csv run patient importer
            $importer = $this->makeImporter($batch);

            $setup_success = $importer->setup($batch);

            if (
                $setup_success === true &&
                $importer->validate() === true
            ) {
                // Pass the pointer to the open file to the importer
                $response = $importer->import();
            } else {
                // This is the case where the validator fails, get messages and update batch and quit
                Batch::update($batch->getId(), [
                    'status' => Batch::STATUS_ERROR,
                    'messages' => json_encode($this->logger->getMessages()),
                    'end_datetime' => date('Y-m-d H:i:s')
                ]);
                continue;
            }

            if ($response->getResult() === Response::SUCCESS) {
                Batch::update($batch->getId(), [
                    'status' => Batch::STATUS_COMPLETE,
                    'messages' => json_encode($this->logger->getMessages()),
                    'end_datetime' => date('Y-m-d H:i:s')
                ]);
            } else {
                Batch::update($batch->getId(), [
                    'status' => Batch::STATUS_ERROR,
                    'messages' => json_encode($this->logger->getMessages()),
                    'end_datetime' => date('Y-m-d H:i:s')
                ]);
            }
        }
    }

    /**
     *
     * Set the upload file for validation and to use in creation of batch
     * @param $file
     */
    public function setUploadFile($file)
    {
        $this->file = $file;
    }

    /**
     * Do basic validation on file, like make sure columns are correct
     *
     * Returns true if valid, false OW
     *
     * @param $file
     * @return bool
     */
    public function validateFile()
    {
        if (!empty($this->file)) {
            // Make a temporary batch
            $importer = $this->makeImporter(
                new Batch([
                    'filename' => $this->file['tmp_name'], // The name of the file on disk
                    'user_filename' => $this->file['name'], // The name of the file that was uploaded
                    'created_datetime' => date('Y-m-d h:i:s'),
                    'status' => Batch::STATUS_WAIT
                ])
            );
            if ($importer->validateUploadFile($this->file)) {
                return true;
            } else {
                $this->logger->addMessage("Importer validation failed, and the importer does not implement validation messages");
                return false;
            }
        } else {
            $this->logger->addMessage("No file uploaded");
        }
        return false;
    }

    public static function reArrayFiles(&$file_post)
    {
        $isMulti = is_array($file_post['name']);
        $file_count = $isMulti?count($file_post['name']):1;
        $file_keys = array_keys($file_post);
        $file_ary = [];
        for ($i=0; $i<$file_count; $i++) {
            foreach ($file_keys as $key) {
                if ($isMulti) {
                    $file_ary[$i][$key] = $file_post[$key][$i];
                } else {
                    $file_ary[$i][$key] = $file_post[$key];
                }
            }
        }

        return $file_ary;
    }

    /**
     * Create a new batch entry in 'waiting' state for
     * a newly uploaded file
     *
     * @return int
     */
    public function createBatch()
    {
        // Move the tmp file to documents dir
        $directory = $GLOBALS['OE_SITE_DIR'] . DIRECTORY_SEPARATOR . 'documents' . DIRECTORY_SEPARATOR . 'imports';
        if (!file_exists($directory)) {
            if (!mkdir($directory, 0700, true)) {
                $this->logger->addMessage(xl('Unable to create document directory'));
                return false;
            }
        }

        // Create the file with the current date timestamp
        $parts = pathinfo($this->file['name']);
        $date = \DateTime::createFromFormat('U.u', microtime(TRUE));
        $filepath = $directory . DIRECTORY_SEPARATOR . $date->format("Ymdhisu") . "." . $parts['extension'];
        if (false === move_uploaded_file($this->file['tmp_name'], $filepath)) {
            $this->logger->addMessage(xl('Unable to move uploaded file'));
            return false;
        }

        return Batch::create([
            'filename' => $filepath, // The name of the file on disk
            'user_filename' => $this->file['name'], // The name of the file that was uploaded
            'created_datetime' => date('Y-m-d h:i:s'),
            'status' => Batch::STATUS_WAIT
        ]);
    }

    /**
     * Given an absolute path to a file, create a batch in the waiting state from it.
     *
     * @param $file
     */
    public function createBatchFromFile($file)
    {
        // Move the tmp file to documents dir
        $directory = $GLOBALS['OE_SITE_DIR'] . DIRECTORY_SEPARATOR . 'documents' . DIRECTORY_SEPARATOR . 'imports';
        if (!file_exists($directory)) {
            if (!mkdir($directory, 0700, true)) {
                $this->logger->addMessage(xl('Unable to create document directory'));
                return false;
            }
        }

        // Create the file with the current date timestamp
        $parts = pathinfo($file);
        $date = \DateTime::createFromFormat('U.u', microtime(TRUE));
        $filepath = $directory . DIRECTORY_SEPARATOR . $date->format("Ymdhisu") . "." . $parts['extension'];
        if (false === copy($file, $filepath)) {
            $this->logger->addMessage(xl('Unable to move (rename) file'));
            return false;
        }

        return Batch::create([
            'filename' => $filepath, // The name of the file on disk
            'user_filename' => $parts['basename'], // The name of the file that was uploaded
            'created_datetime' => date('Y-m-d h:i:s'),
            'status' => Batch::STATUS_WAIT
        ]);
    }

    /*
     * This function inserts the background process if it doesn't already exist
     */
    public static function insertBackgroundService()
    {
        $sql = "SELECT * FROM `background_services` WHERE `name` = ? LIMIT 1";
        $row = sqlQuery($sql,['IMPORT_SERVICE']);
        if (false === $row) {
            // The background service hasn't been created so create it.
            // Set it to run the mss_service.php script every "1 minute" we want to run it every time
            // background services run
            $sql = "INSERT INTO `background_services` (`name`, `title`, `active`, `running`, `next_run`, `execute_interval`, `function`, `require_once`, `sort_order`) VALUES
            ('IMPORT_SERVICE', 'Import Service', 1, 0, '2021-01-10 11:25:10', 1, 'start_import', '/interface/modules/custom_modules/oe-import/import_service.php', 100);";

            sqlStatement($sql);
        }
    }

    public static function change_key( $array, $old_key, $new_key ) {

        if( ! array_key_exists( $old_key, $array ) )
            return $array;

        $keys = array_keys( $array );
        $keys[ array_search( $old_key, $keys ) ] = $new_key;

        return array_combine( $keys, $array );
    }

    //this returns the count, pid, and id.
    private function ptExists($macprac){

        $row = sqlQuery("Select count(*) as count, pid as pid, id from patient_data where macPrac = ?",
            array($macprac));
        if ($row['count'] > 0) {
            return $row;

        } else{

            return false;
        }

    }
}
