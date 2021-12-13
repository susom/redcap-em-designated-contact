<?php
namespace Stanford\DesignatedContact;
/** @var \Stanford\DesignatedContact\DesignatedContact $module **/

require_once($module->getModulePath() . "util/DesignatedContactUtil.php");
use REDCap;

/*
 * This page is called when users are on the User Rights page (or Project Setup when a designated contact
 * is not yet selected) and a user selects the Change Designated Contact link. Users will be presented with
 * a list of project users who have User Rights privileges.  When a new contact is saved, the new and old
 * Designated Contact will be sent email to let them know the contact has been changed.
 *
 * The Designated Contact will be changed in the Project Monitoring project and a log entry will be created om
 * project where a new contact was selected..
 */

$current_user = USERID;
$des_contact = array();

$pid = isset($_GET['pid']) && !empty($_GET['pid']) ? $_GET['pid'] : null;
$contact = isset($_POST['selected_contact']) && !empty($_POST['selected_contact']) ? $_POST['selected_contact'] : null;

// Retrieve the user information from their username
$users = retrieveUserInformation(array($contact));
foreach($users as $userid => $userInfo) {
    $des_contact[$pid]["project_id"]                    = $pid;
    $des_contact[$pid]['contact_id']                    = $userid;
    $des_contact[$pid]["contact_firstname"]             = $userInfo["contact_firstname"];
    $des_contact[$pid]["contact_lastname"]              = $userInfo["contact_lastname"];
    $des_contact[$pid]["contact_email"]                 = $userInfo["contact_email"];
    $des_contact[$pid]["contact_phone"]                 = $userInfo["contact_phone"];
    $des_contact[$pid]['contact_timestamp']             = date("Y-m-d H:i:s");
    $des_contact[$pid]['designated_contact_complete']   = '2';
    $des_contact[$pid]['force_update___1']              = '0';
    $des_contact[$pid]['contact_notified___1']          = '1';
}

if (!empty($des_contact)) {

    // Find the designated contact project where the data is stored
    $pmon_pid = $module->getSystemSetting('designated-contact-pid');
    $pmon_event_id = $module->getSystemSetting('designated-contact-event-id');
    $project_title = REDCap::getProjectTitle();

    // New contact
    $new_email = $des_contact[$pid]["contact_email"];
    $new_name = $des_contact[$pid]["contact_firstname"] . ' ' . $des_contact[$pid]["contact_lastname"];

    // Old contact
    $fields = array('contact_id', 'contact_email', 'contact_firstname', 'contact_lastname');
    $old_contact = REDCap::getData($pmon_pid, 'array', $pid, $fields, $pmon_event_id);
    $old_user = $old_contact[$pid][$pmon_event_id]['contact_id'];
    $old_email = $old_contact[$pid][$pmon_event_id]['contact_email'];
    $old_name = $old_contact[$pid][$pmon_event_id]['contact_firstname'] . ' ' . $old_contact[$pid][$pmon_event_id]['contact_lastname'];

    // Save the new Designated Contact
    $status = saveNewDC($pmon_pid, $des_contact[$pid], NEW_CONTACT, "Designated Contact changed to $contact by $current_user");
    if ($status) {

        // Person who made the change
        $change = retrieveUserInformation(array($current_user));
        $changer = $change[$current_user]["contact_firstname"] . ' ' . $change[$current_user]["contact_lastname"];

        // Retrieve the verbiage, from the system settings, for the emails that will be sent to the new DC
        $from_email     = $module->getSystemSetting('from-address');
        $old_subject    = $module->getSystemSetting('old-dc-email-subject');
        $old_body       = $module->getSystemSetting('old-dc-email-body');
        $new_subject    = $module->getSystemSetting('new-dc-email-subject');
        $new_body       = $module->getSystemSetting('new-dc-email-body');

        // Check to see if we need to send email to the one being removed
        if (!empty($old_user)) {
            if (($old_user != $current_user) and ($old_email != $new_email)) {

                // Put together the email body with pid and person who made the change
                $emailBody = $old_body;
                $emailBody .= "<br>Project ID:                 " . $pid;
                $emailBody .= "<br>Project Title:              " . $project_title;
                $emailBody .= "<br>Person who made the change: " . $changer;
                if (!empty($old_user)) {
                    $emailBody .= "<br>Designated Contact Removed: " . $old_name;
                }
                $emailBody .= "<br>Designated Contact Added:   " . $new_name;

                // Send email to the old contact
                if (!empty($old_email)) {
                    $status = REDCap::email($old_email, $from_email, $old_subject, $emailBody);
                } else {
                    $module->emError("Cannot send email to $old_user that $current_user has removed them from Designated Contact for project $pid");
                }
            }
        }

        // Check to see if we need to send email to the one being added
        if ($contact != $current_user) {
            // Send email to the new contact
            if (!empty($new_email) and ($new_email != $old_email)) {

                // Put together the email body with pid and person making the change
                $emailBody = $new_body;
                $emailBody .= "<br><br><b>Project Details</b>:";
                $emailBody .= "<br>Project ID:                 " . $pid;
                $emailBody .= "<br>Project Title:              " . $project_title;
                $emailBody .= "<br>Person who made the change: " . $changer;
                if (!empty($old_user)) {
                    $emailBody .= "<br>Designated Contact Removed: " . $old_name;
                }
                $emailBody .= "<br>Designated Contact Added:   " . $new_name;

                $status = REDCap::email($new_email, $from_email, $new_subject, $emailBody);
                if (!$status) {
                    $module->emError("Email did not get sent to $new_email for project $pid");
                } else {
                    $module->emLog("Successfully sent email to $new_email for project $pid");
                }
            } else {
                $module->emError("Cannot send email to $new_email that $current_user has removed them from Designated Contact for project $pid");
            }
        }

        print 1;
    } else {
        print 0;
        $module->emError("Cannot update Designated Contact $contact for project $pid by $current_user");
    }
} else {
    $module->emError("Could not find new Designated Contact $contact in DB for project $pid by $current_user");
    print 0;
}

return;
