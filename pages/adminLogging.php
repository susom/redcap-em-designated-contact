<?php
namespace Stanford\DesignatedContact;
use \REDCap;
use Stanford\DesignatedContact\emLoggerTrait;

/** @var \Stanford\DesignatedContact\DesignatedContact $module */

$pid = isset($_GET['pid']) && !empty($_GET['pid']) ? $_GET['pid'] : null;

$runUrl = $module->getUrl('pages/adminLogging.php', false, true);
$users = REDCap::getUsers();
$dc_email = 'test@stanford.edu';
$projectURL = 'https://redcap.stanford.edu/redcap_v12.3.2/index.php?pid=' . $pid;

if (isset($_POST['add_logging'])) {

    if ($_POST['logmsg']) {
        $log_msg = $_POST['logmsg'];
        REDCap::logEvent('ADMIN log', $log_msg);
    }

    // if (empty($dc_email)) {
    //     if (!empty($_POST['contact'])){
    //         $dc_email = $_POST['contact'];
    //     }
    // }

    if ($_POST['emailmsg']) {

        $email_msg = $_POST['emailmsg'];
        $email_subject = "Update notification about your project (pid: ".  $pid . ")";

        // if (!empty($dc_email) && !empty($email_msg)) {
        if (!empty($email_msg)) {

            if (!empty($_POST['contact'])) {
                
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

    // if (!empty($_POST['contact'])){
    //     $dc_email = '';
    // }
}

function sendEmail($email_subject, $email, $msg) {
    global $module, $pid;
    $from_email = "redcap-help@lists.stanford.edu";
    $status = REDCap::email($email, $from_email, $email_subject, $msg);
    if (!$status) {
        $module->emError("Email did not get sent to $email for project $pid");
    }
}

// function getEmailTemplate($et_name, $et_body, $et_template) {
//     $et_name         =         getSubSettings('email-template-title');
//     $et_body         =         getSubSettings('email-template-body');
//     $et_template     =         getSubSettings('email-template');
// }

$emailTemplate = 'Dear Jae,' . '\n\n' . "(Your Message Here)" . '\n\n' . "Project Name: " . REDCap::getProjectTitle();

?>
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap.min.css">
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap-theme.min.css">
<script src="https://ajax.googleapis.com/ajax/libs/jquery/2.1.3/jquery.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/js/bootstrap.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-multiselect/0.9.13/js/bootstrap-multiselect.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-multiselect/0.9.13/css/bootstrap-multiselect.css">
</script>

<form enctype="multipart/form-data" action="<?php echo $runUrl ?>" method="post">
    <h2>SUPERUSER ONLY: Add entry to logging: </h2>

    <label for="logmsg" id="logmsg-label">Entry for List of Data Changes field: For example: Removed DET triggers to summarize plugin. </label><br>
    <textarea name="logmsg" id="logmsg" cols="80" rows="4" placeholder="Enter log message" ></textarea>
    <br><br>
    <label for="emailmsg" id="emailmsg-label">OPTIONAL: Email to Designated Contact:</label>
    <br>
    <select name="contact[]" multiple="multiple" id="contact">
        <option value="<?php echo $dc_email?>">Designated Contact</option>
        <?php
            foreach($users as $user) {
        ?>
        <option value="<?php echo $user . '@stanford.edu'; ?>"><?php echo $user; ?></option>
        <?php } ?>
        <!-- <option value="test@stanford.edu">test@stanford.edu</option>
        <option value="test2@stanford.edu">test2@stanford.edu</option>
        <option value="all">Send to All</option> -->
    </select>
    </br><br>
    <textarea name="emailmsg" id="emailmsg" cols="80" rows="4" placeholder="IF POPULATED, an email will be sent to the designated contact" ></textarea>
    <br>
    <input type="submit" id="add_logging" name="add_logging" value="Add to logging">
    <input type="button" id="email_template" name="email_template" value="Email template" onclick="document.getElementById('emailmsg').innerHTML = '<?php echo $emailTemplate?>'">
    <!-- <input type="button" id="email_template" name="email_template" value="Test" onclick="document.getElementById('emailmsg').innerHTML = '<?php echo $et_template?>'"> -->
    <input type="checkbox" id="link" name="link">
    <label for="link">Add Project URL Link to End of Email</label>
</form>

<script>
$(document).ready(function() {       
	$('#contact').multiselect({		
		nonSelectedText: 'Select a contact'				
	});
});

$(function () {
    $('#contact').multiselect({ 
        buttonText: function(options, select) {
        var labels = [];
        console.log(options);
        options.each(function() {
            labels.push($(this).val());
            });
            
        $("#add_logging").val(labels.join(',') + '');
        return labels.join(', ') + '';
        }
    });
});
</script>