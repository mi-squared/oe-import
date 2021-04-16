<?php

use OpenEMR\Core\Header;
use OpenEMR\Common\Csrf\CsrfUtils;

?>
<html>
<head>
    <?php Header::setupHeader(['datatables', 'datatables-colreorder', 'datatables-dt', 'datatables-bs']); ?>

    <title><?php echo xlt('Import'); ?></title>

    <script type="text/javascript" src="../oe-import/assets/js/datatables/Buttons-1.6.5/js/dataTables.buttons.js"></script>
    <script type="text/javascript" src="../oe-import/assets/js/datatables/Buttons-1.6.5/js/buttons.dataTables.js"></script>
    <script type="text/javascript" src="../oe-import/assets/js/datatables/Buttons-1.6.5/js/buttons.colVis.js"></script>
    <script type="text/javascript" src="../oe-import/assets/js/datatables/Buttons-1.6.5/js/buttons.print.js"></script>
    <script type="text/javascript" src="../oe-import/assets/js/datatables/Buttons-1.6.5/js/buttons.html5.js"></script>
    <script type="text/javascript" src="../oe-import/assets/js/datatables/Buttons-1.6.5/js/buttons.bootstrap4.js"></script>

    <link rel="stylesheet" href="../oe-import/assets/js/datatables/Buttons-1.6.5/css/buttons.bootstrap4.css" type="text/css" />
    <style type="text/css">
        .dataTables_wrapper .dataTables_scroll div.dataTables_scrollBody > table > thead > tr > th, .dataTables_wrapper .dataTables_scroll div.dataTables_scrollBody > table > thead > tr > td, .dataTables_wrapper .dataTables_scroll div.dataTables_scrollBody > table > tbody > tr > th, .dataTables_wrapper .dataTables_scroll div.dataTables_scrollBody > table > tbody > tr > td {
            vertical-align: top;
        }

        .badge.complete {
            background-color: #B4CE99;
        }

        .badge.info {
            background-color: #17a2b8;
        }

        .badge.warning {
            background-color: #ffc107;
        }
    </style>
</head>
<body class="body_top" style="padding: 10px;">

<?php if (count($this->importManager->getLogger()->getMessages()) > 0) { ?>
    <div class="alert-danger">
        <ul>
            <?php foreach($this->importManager->getLogger()->getMessages() as $message) { ?>
                <li><?php echo $message; ?></li>
            <?php } ?>
        </ul>
    </div>
<?php } ?>

<form class="form-inline" enctype="multipart/form-data" method='post' name='do_import_form' id='do_import_form' action='<?php echo $this->action_url; ?>' onsubmit='return top.restoreSession()'>
    <input type="hidden" id="csrf_token_form" name="csrf_token_form" value="<?php echo attr(CsrfUtils::collectCsrfToken()); ?>" />
    <input type="hidden" name="do_import" value="1" />
    <div class="form-group mb-2">
        <label for="input_files">Input Files</label>
        <input type="file" class="form-control-file" id="input_files" name="input_files[]" multiple>
    </div>
    <input type="submit" class="btn btn-primary css_button_small" value="Do Import" \>
</form>

<hr>

<div id="report_results">
    <table class='table table-striped table-bordered' style="width: 100%" id='import-table'>
        <thead class='thead-light'>
        <th>&nbsp;</th>
        <?php foreach ($this->columns as $title => $key) { ?>
            <th><?php echo xlt($title); ?></th>
        <?php } ?>
        </thead>
        <tbody>
        </tbody>
    </table>
</div>
</body>
<script type="text/javascript">

    var batch_table = $("#import-table").DataTable({
        "scrollX": true,
        dom: 'frtip',
        "processing": true,
        // next 2 lines invoke server side processing
        "ajax": {
            "type" : "GET",
            "url" : '<?php echo $this->ajax_source_url; ?>&skip_timeout_reset=1&csrf_token_form=' + $('#csrf_token_form').val(),
            "dataSrc": function (json) {
                if (typeof json.data === 'undefined') {
                    window.location = '<?php $GLOBALS['webroot']; ?>';
                }
                return json.data;
            }
        },
        "columns": [
            {
                data: null,
                render: function (data, type, row, meta) {
                    const id = data.id;
                    const button_group = '<div class="btn-group">' +
                        '<a href="#" data-id="' + id + '" class="btn btn-sm batch-rerun"><i class="fa fa-recycle"></i></a>' +
                        '<a href="#" data-id="' + id + '"class="btn btn-sm batch-delete"><i class="fa fa-trash"></i></a>' +
                        '</div>';
                    return button_group;
                }
            },
            { "data": "id" },
            {
                "data": "status",
                "render": function(data, type, row, meta) {
                    // Format the status with a nice looking badge
                    if (type === 'display') {
                        if (data == 'complete') {
                            data = '<span class="badge complete">' + data + '</span>';
                        } else if (data == 'waiting') {
                            data = '<span class="badge info">' + data + '</span>';
                        } else {
                            data = '<span class="badge warning">' + data + '</span>';
                        }
                    }

                    return data;
                }
            },
            { "data": "user_filename" },
            { "data": "created_datetime" },
            { "data": "start_datetime" },
            { "data": "end_datetime" },
            {
                "data": "messages",
                "render": function(json, type, row, meta) {
                    // Build the HTML
                    // let json = JSON.parse(data);
                    let messagesHTML = '';
                    if (Array.isArray(json)) {
                        if (type === 'display') {
                            messagesHTML = '<table>';
                            let count = 1;
                            json.forEach(message => {
                                let messageRow = '<tr><td>' + count + '</td><td>' + message + '</td></tr>';
                                messagesHTML = messagesHTML + messageRow;
                                count++;
                            })

                            messagesHTML = messagesHTML + '</table>';
                        }
                    }

                    return messagesHTML;
                }
            }
        ],
        "order": [[1, 'desc']]
    });

    setInterval(function() {
        top.restoreSession();
        batch_table.ajax.reload( null, false );
    }, 3000);

    $("#import-table tbody").on('click', 'a.batch-delete', function(e) {
        e.preventDefault();
        const id = $(this).attr('data-id');
        var that = $(this);
        const data = {
          id: id,
          what: 'delete'
        };
        $.ajax({
            type: 'POST',
            dataType: "json",
            url: '<?php echo $this->baseUrl() ?>/index.php?action=import!do_row_action',
            data: data,
            success: function (data) {
                const row = batch_table.row(that.parents('tr'));
                row.remove().draw();
            }
        });
    });

    $("#import-table tbody").on('click', 'a.batch-rerun', function(e) {
        e.preventDefault();
        const id = $(this).attr('data-id');
        const data = {
            id: id,
            what: 'rerun'
        };
        $.ajax({
            type: 'POST',
            dataType: "json",
            url: '<?php echo $this->baseUrl() ?>/index.php?action=import!do_row_action',
            data: data,
            success: function (data) {
                batch_table.ajax.reload( null, false );
            }
        });
    });
</script>
</html>
