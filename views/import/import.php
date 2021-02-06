<?php

use OpenEMR\Billing\BillingUtilities;
use OpenEMR\Common\Csrf\CsrfUtils;
use OpenEMR\Core\Header;
if (!empty($_POST)) {
    if (!CsrfUtils::verifyCsrfToken($_POST["csrf_token_form"])) {
        CsrfUtils::csrfNotVerified();
    }
}

?>
<html>
<head>
    <title><?php echo xlt('Import'); ?></title>

    <?php Header::setupHeader(['datetime-picker', 'report-helper', 'datatables', 'datatables-bs', 'datatables-scroller']); ?>

    <script type="text/javascript" src="../oe-dashboard/assets/js/datatables/Buttons-1.6.5/js/dataTables.buttons.js"></script>
    <script type="text/javascript" src="../oe-dashboard/assets/js/datatables/Buttons-1.6.5/js/buttons.dataTables.js"></script>
    <script type="text/javascript" src="../oe-dashboard/assets/js/datatables/Buttons-1.6.5/js/buttons.colVis.js"></script>
    <script type="text/javascript" src="../oe-dashboard/assets/js/datatables/Buttons-1.6.5/js/buttons.print.js"></script>
    <script type="text/javascript" src="../oe-dashboard/assets/js/datatables/Buttons-1.6.5/js/buttons.html5.js"></script>
    <script type="text/javascript" src="../oe-dashboard/assets/js/datatables/Buttons-1.6.5/js/buttons.bootstrap4.js"></script>

    <link rel="stylesheet" href="<?php echo $GLOBALS['assets_static_relative'] . '/datatables.net-bs4/css/dataTables.bootstrap4.min.css'; ?>" type="text/css" />
    <link rel="stylesheet" href="../oe-dashboard/assets/js/datatables/Buttons-1.6.5/css/buttons.bootstrap4.css" type="text/css" />
</head>
<body class="body_top">

<span class='title'><?php echo xlt('Import'); ?></span>

<?php if (count($this->importService->getValidationMessages()) > 0) { ?>
    <div class="alert-danger">
        <ul>
            <?php foreach($this->importService->getValidationMessages() as $message) { ?>
                <li><?php echo $message; ?></li>
            <?php } ?>
        </ul>
    </div>
<?php } ?>

<form class="form-inline" enctype="multipart/form-data" method='post' name='do_import_form' id='do_import_form' action='<?php echo $this->action_url; ?>' onsubmit='return top.restoreSession()'>
    <input type="hidden" name="csrf_token_form" value="<?php echo attr(CsrfUtils::collectCsrfToken()); ?>" />
    <input type="hidden" name="do_import" value="1" />
    <div class="form-group mb-2">
        <label for="input_file">Input File</label>
        <input type="file" class="form-control-file" id="input_file" name="input_file">
    </div>
    <input type="submit" class="btn btn-primary mb-2" value="Do Import" \>
</form>

<hr>

<div id="report_results">
    <table class='table table-striped table-bordered' style="width: 100%" id='mymaintable'>
        <thead class='thead-light'>
        <?php foreach ($this->columns as $title => $key) { ?>
            <th><?php echo xlt($title); ?></th>
        <?php } ?>
        </thead>
        <tbody>
        <?php foreach ($this->batches as $batch) { ?>
            <tr>
                <?php foreach ($this->columns as $title => $key) { ?>
                    <td><?php echo xlt($batch[$key]); ?></td>
                <?php } ?>
            </tr>
        <?php } ?>
        </tbody>
    </table>
</div>
</body>
<script type="text/javascript">
    $("#mymaintable").DataTable({
        "scrollX": true,
        dom: 'Bfrtip',
        buttons: [
            'colvis',
            'csv',
            'print'
        ],
        "order": [[ 0, "desc"]]
    });
</script>
</html>
