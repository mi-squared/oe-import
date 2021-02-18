<?php

namespace Mi2\Import\Traits;

use Mi2\Import\Models\Response;

trait InteractsWithLists
{
    public function getListItemOption($list, $title, $patient_name = ""): Response
    {
        $result = true;
        $messages = [];
        $row = sqlQuery("SELECT option_id FROM list_options WHERE " .
            "list_id = ? AND title = ? AND activity = 1", array($list, $title));
        if (
            $row == false ||
            empty($row['option_id'])
        ) {
            if ($title != "") {
                $messages[] = "There was a value `$title` in the list `$list` for `$patient_name` but the option was not found";
            }
            $data = "";
            $result = false;
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
