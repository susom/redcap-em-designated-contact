<?php
namespace Stanford\DesignatedContact;
use \REDCap;

/** @var DesignatedContact $module */


$pid                =           $module->getProjectId();

$runUrl             =           $module->getUrl('pages/adminLogging.php', false, true);
$dc_email           =           $module->getDC($pid);
$users              =           REDCap::getUsers();
$user_email         =           $module->getUser($user)->getEmail();
$user_rights        =           REDCap::getUserRights($user);

$projectURL         =           "https://redcap.stanford.edu" . APP_PATH_WEBROOT . "index.php?pid=$pid";

$et_template        =           $module->getSystemSetting('email-template');
$et_name            =           $module->getSystemSetting('email-template-title');
$et_body            =           $module->getSystemSetting('email-template-body');

$all_contacts       =           array($dc_email);
foreach($users as $user) {
    if ($user_rights[$user]['user_rights'] == '1') {
        $all_contacts[] = $module->getUser($user)->getEmail();
    }
}

if (isset($_POST['add_logging'])) {

    if ($_POST['logmsg']) {
        $log_msg = $_POST['logmsg'];
        REDCap::logEvent('ADMIN log', $log_msg);
    }

    if ($_POST['emailmsg']) {

        $email_msg = $_POST['emailmsg'];
        $email_subject = "Update notification about your project (pid: ".  $pid . ")";

        if (!empty($email_msg)) {

            if (!empty($_POST['contact'])) {

                $module->emDebug(implode($_POST['contact']));
                
                if (implode($_POST['contact']) === 'all') {

                    foreach ($all_contacts as $contact) {
                        if (!empty($_POST['link'])) {
                            REDCap::logEvent('ADMIN email sent', "Emailing to $contact :  $email_msg");
                            sendEmail($email_subject, $contact, $email_msg . '<br>' . $projectURL);
                        }
    
                        else {
                            REDCap::logEvent('ADMIN email sent', "Emailing to $contact :  $email_msg");
                            sendEmail($email_subject, $contact, $email_msg);
                        }
                    }

                }

                else {

                    foreach ($_POST['contact'] as $contact) {

                        if (!empty($_POST['link'])) {
                            REDCap::logEvent('ADMIN email sent', "Emailing to $contact :  $email_msg");
                            sendEmail($email_subject, $contact, $email_msg . '<br>' . $projectURL);
                        }

                        else {
                            REDCap::logEvent('ADMIN email sent', "Emailing to $contact :  $email_msg");
                            sendEmail($email_subject, $contact, $email_msg);
                        }

                    }

                }


            }

        }

    }

}

function sendEmail($email_subject, $email, $msg) {
    global $module, $pid;
    $from_email = "redcap-help@lists.stanford.edu";
    $status = REDCap::email($email, $from_email, $email_subject, $msg);
    if (!$status) {
        $module->emError("Email did not get sent to $email for project $pid");
    }
}

?>

<!-- CSS -->
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap.min.css">
<link rel="stylesheet" href="<?php echo $module->getUrl("./css/adminLogging.css"); ?>" />

<!-- Admin Logging Page -->
<form enctype="multipart/form-data" action="<?php echo $runUrl ?>" method="post">

    <h2>SUPERUSER ONLY: Add entry to logging: </h2>

    <label for="logmsg" id="logmsg-label">Entry for List of Data Changes field: For example: Removed DET triggers to summarize plugin. </label><br>
    <textarea name="logmsg" id="logmsg" cols="80" rows="4" placeholder="Enter log message" ></textarea>
    <br><br>
    <label for="emailmsg" id="emailmsg-label">OPTIONAL: Email to Designated Contact or Other Users:</label>
    <br>


    <select class="contact" name="contact[]" multiple="multiple" id="contact">
        <option value="all">Select All</option>
        <option value="<?php echo $dc_email?>">Designated Contact (<?php echo $dc_email; ?>)</option>
        <?php
            foreach($users as $user) {
                if ($user_rights[$user]['user_rights'] == '1') {
                
        ?>
        <option value="<?php echo $user_email; ?>"><?php echo $user . ' (' . $user_email . ')' ; ?></option>
        <?php }} ?>
    </select>
    
    </br><br>
    <label for="templates" id="templates">Your Email Templates</label>
    <br>
    <?php
        $count_template     =           count($et_template);
        $counter            =           0;
        while ($counter < $count_template) {
            $encoded_msg    =           explode("\n", str_replace(array("\r\n", "\r"), "\n", $et_body[$counter]));
            $decoded_msg    =           implode($encoded_msg);
    ?>
        <input type="button" id="email_template" name="email_template" value="<?php echo $et_name[$counter];?>" onclick="document.getElementById('emailmsg').innerHTML = '<?php echo $decoded_msg;?>'">
    <?php 
            $counter = $counter + 1;
        } 
    ?>
    </br><br>

    <textarea name="emailmsg" id="emailmsg" cols="80" rows="4" placeholder="IF POPULATED, an email will be sent to the designated contact" ></textarea>
    <br>
    <input type="submit" id="add_logging" name="add_logging" value="Add to logging">
    <input type="checkbox" id="link" name="link">
    <label for="link">Add Project URL Link to End of Email</label>
    </br><br>

</form>

<!-- JavaScript -->
<script type="text/javascript" src="<?php echo $module->getUrl("./js/adminLogging.js"); ?>"></script>