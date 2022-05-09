<?php
namespace Stanford\DesignatedContact;
use REDCap;

/** @var \Stanford\DesignatedContact\DesignatedContact $module */

$pid = isset($_GET['pid']) && !empty($_GET['pid']) ? $_GET['pid'] : null;

$runUrl = $module->getUrl('pages/adminLogging.php', false, true);
$dc_email = $module->getDC($pid);

if (isset($_POST['add_logging'])) {

    if ($_POST['logmsg']) {
        $log_msg = $_POST['logmsg'];
        REDCap::logEvent('ADMIN log', $log_msg);
    }
    if ($_POST['emailmsg']) {

        $email_msg = $_POST['emailmsg'];

        if (!empty($dc_email) && !empty($email_msg)) {
            REDCap::logEvent('ADMIN email sent', "Emailing to $dc_email :  $email_msg");
            $email_subject = "Update notification about your project (pid: ".  $pid . ")";
            sendEmail($email_subject,$dc_email, $email_msg);
        }

    }
}

function sendEmail($email_subject, $email, $msg) {
    global $module, $pid;
    $from_email = "redcap-help@lists.stanford.edu";
    $status = REDCap::email($email,$from_email, $email_subject,$msg);
    if (!$status) {
        $module->emError("Email did not get sent to $email for project $pid");
    }
}

?>
<form enctype="multipart/form-data" action="<?php echo $runUrl ?>" method="post">
    <h2>SUPERUSER ONLY: Add entry to logging: </h2>

    <label for="logmsg" id="logmsg-label">Entry for List of Data Changes field: For example: Removed DET triggers to summarize plugin. </label><br>
    <textarea name="logmsg" id="logmsg" cols="80" rows="4" placeholder="Enter log message" required="required" /></textarea>
    <br><br>
    <label for="emailmsg" id="emailmsg-label">OPTIONAL: Email to Designated Contact: <?php echo $dc_email?></label><br>
    <textarea name="emailmsg" id="emailmsg" cols="80" rows="4" placeholder="IF POPULATED, an email will be sent to the designated contact" /></textarea>
    <br>
    <input type="submit" id="add_logging" name="add_logging" value="Add to logging">

</form>


