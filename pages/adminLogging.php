<?php
namespace Stanford\DesignatedContact;
use REDCap;

/** @var \Stanford\DesignatedContact\DesignatedContact $module */

$runUrl = $module->getUrl('pages/adminLogging.php', false, true);

$module->emDebug($_POST);

if (isset($_POST['add_logging'])) {
    if (!$_POST['logmsg']) {
        die("No logmsg set.");
    }
    $log_msg = $_POST['logmsg'];
    REDCap::logEvent(USERID . ":" . $log_msg);

    exit;
}

?>
<form enctype="multipart/form-data" action="<?php echo $runUrl ?>" method="post">
    <h2>SUPERUSER ONLY: Add entry to logging: </h2>
    <p>For example: Removed DET triggers to summarize plugin.</p>
    <textarea name="logmsg" id="logmsg" cols="40" rows="4" placeholder="Enter log message" required="required" /></textarea>
    <br>
    <input type="submit" id="add_logging" name="add_logging" value="Add to logging">

</form>
