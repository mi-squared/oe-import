<?php

namespace Mi2\Import\Traits;

use Mi2\Import\Models\Response;

trait InteractsWithLists
{
    public function insertListOption($listId, $optionId, $title)
    {
        // find the proper sequence based on alphabetic sorting
        // Get the list options from Z - A
        $result = sqlStatement("SELECT * FROM list_options WHERE list_id = ? ORDER BY option_id DESC", [
            $listId
        ]);
        $count = 0;
        $seq = 1000;
        $wasInserted = false;
        $inserts = [];
        while ($row = sqlFetchArray($result)) {
            if (strcmp($row['title'], $title) <= 0 && $wasInserted === false) {
                // Insert our new list in between the two adjacent list options by alpha
                $now = date('Y-m-d H:i:s');
                $inserts[]= $listId;
                $inserts[]= trim($optionId);
                $inserts[]= trim($title);
                $inserts[]= $seq;
                $inserts[]= $now;

                $wasInserted = true;

                $seq += 10;
                $count++;
            }

            $inserts[] = $row['list_id'];
            $inserts[] = $row['option_id'];
            $inserts[] = $row['title'];
            $inserts[] = $seq;
            $inserts[] = $row['timestamp'];

            $seq += 10;
            $count++;
        }

        if ($wasInserted === false) {
            // It happens to be the last one
            $now = date('Y-m-d H:i:s');
            $inserts[] = $listId;
            $inserts[] = $optionId;
            $inserts[] = $title;
            $inserts[] = $seq;
            $inserts[] = $now;
            $count++;
        }

        $valuesPart = rtrim(str_repeat("(?,?,?,?,?),", $count), ",");

        $insertResult = sqlInsert("
            INSERT INTO list_options (list_id, option_id, title, seq, `timestamp`)
            VALUES $valuesPart
            ON DUPLICATE KEY UPDATE seq = VALUES(seq)
        ", $inserts);

        return $insertResult;
    }

    public function getListItemOption($list, $title, $patient_name = "", $insertWhenNotFound = false): Response
    {
        $result = true;
        $messages = [];
        $row = sqlQuery("SELECT option_id FROM list_options WHERE " .
            "list_id = ? AND title = ? AND activity = 1", array($list, trim($title)));
        if (
            $row == false ||
            empty($row['option_id'])
        ) {
            if ($insertWhenNotFound) {
                $messages[] = "There was a value `$title` in the list `$list` for `$patient_name`. Option was not found, so we created it.";
                // Insert the new list item
                $success = $this->insertListOption($list, trim($title), trim($title));
                // Try the fetch again (recursive call one time)
                $row = sqlQuery("SELECT option_id FROM list_options WHERE " .
                    "list_id = ? AND title = ? AND activity = 1", array($list, trim($title)));
                if (
                    $row == false ||
                    empty($row['option_id'])
                ) {
                    $messages []= "ERROR: Failed to create list option";
                } else {
                    $data = $row['option_id'];
                }
            } else {
                if ($title != "") {
                    $messages[] = "There was a value `$title` in the list `$list` for `$patient_name` but the option was not found";
                }
                $data = "";
                $result = false;
            }
        } else {
            $data = $row['option_id'];
        }

        $response = new Response([
            'result' => $result,
            'data' => $data,
            'messages' => $messages,
        ]);
        return $response;
    }

    public function getListItemTitle($list, $option, $patient_name = ""): Response
    {
        $result = true;
        $messages = [];
        $row = sqlQuery("SELECT title FROM list_options WHERE " .
            "list_id = ? AND option_id = ? AND activity = 1", array($list, $option));
        if (
            $row == false ||
            empty($row['title'])
        ) {
            if ($option != "") {
                $messages[] = "There was a value `$option` in the list `$list` for `$patient_name` but the title was not found";
            }
            $result = false;
            $data = "";
        } else {
            $data = xl_list_label($row['title']);
        }

        $response = new Response([
            'result' => $result,
            'data' => $data,
            'messages' => $messages,
        ]);
        return $response;
    }
}
