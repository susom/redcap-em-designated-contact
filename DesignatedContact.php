<?php

namespace Stanford\DesignatedContact;

require_once "emLoggerTrait.php";
require_once "./classes/getModal.php";
require_once "./classes/newContact.php";

use REDCap;
use Exception;

class DesignatedContact extends \ExternalModules\AbstractExternalModule
{

    use emLoggerTrait;

    public function __construct()
    {
        define('REASSIGN_DC',       'Re-selected');
        define('ASSIGN_DC',         'Auto-selected');
        define('ORPHANED_DC',       'Orphaned');
        define('NEW_CONTACT',       'New Contact');
        define('DB_TABLE',          'designated_contact_selected');
        define('SUSPEND_TABLE',     'designated_contact_suspended_users');
        parent::__construct();
    }

    /**
     * This function is called after each page is loaded and before it is rendered. There are 2 main reasons
     * we have it enabled.
     *
     *  1) On the User Rights page (and the Project Setup if a Designated Contact is not selected), users
     *     who have User Rights privaleges have the option to change the Designated Contact for their project.
     *  2) On the My Projects page, an icon will be placed to the left of the project title when this user
     *     is the designated contact for that project.
     *
     * @param null $project_id
     */
    function redcap_every_page_top($project_id=null) {

        /*
         * This section will place the Designated Contact icon next to the project title on the My Projects page.
         */
        if (((PAGE === 'Home/index.php') || (PAGE === 'index.php'))
                && ($_GET["action"] == "myprojects")) {

            // Find the user
            $user = '';
            try {
                //if (defined(USERID)) {
                    $user = $this->getUser()->getUsername();
                //}
            } catch (Exception $ex) {
                $this->emError("Trying to retrieve username on MyProjects page but no user defined: " . PAGE);
            }
            if (empty($user)) {
                return;
            }

            // Find the designated contact project where the data is stored
            $pmon_pid = $this->getSystemSetting('designated-contact-pid');
            $pmon_event_id = $this->getSystemSetting('designated-contact-event-id');
            if (empty($pmon_pid) || empty($pmon_event_id)) {
                return;
            }

            // Find the projects that this user is the designated contact and put the icon next to the name
            try {
                $contacts = new newContact($this);
                $projects = $contacts->contactProjectList($user, $pmon_pid, $pmon_event_id);
                //$this->emDebug("Projects for this user: " . json_encode($projects));

                // Find the projects that this user has User Rights but no designated contact was selected
                $no_dc_projects = $contacts->noContactSelectedList($user);
                //$this->emDebug("No DC projects: " . json_encode($no_dc_projects));
            } catch (Exception $ex) {
                $this->emError("Exception occurred while trying to place icons on Project page", $ex->getMessage());
            }

            // Add the javascript which will inject the html into the page to display the DC icons
            require_once $this->getModulePath() . "src/designated_contact_icon.php";

        }

        /*
         * If this is the User Rights page (or Project Setup page in some instances),
         * add the Designated Contact block which allows users to change the contact.
         * This Designated Contact box will be displayed on the Project Setup page when
         * a contact has not yet been selected for a project or when the Force Verification
         * is selected.
         *
         */
        if (PAGE === 'UserRights/index.php' || PAGE === 'ProjectSetup/index.php') {

            // Find the current user
            $user = '';
            try {
                //if (defined(USERID)) {
                    $user = $this->getUser()->getUsername();
               //}
            } catch (Exception $ex) {
                $this->emError("Trying to retrieve username for projects page but cannot: " . PAGE);
            }
            if (empty($user)) {
                return;
            }

            // See if this user has User Rights. If not, just exit
            try {
                $contacts = new newContact($this);
                $users = $contacts->getUsersWithUserRights($project_id);
                $this->emDebug("These are users with User Rights for project $project_id: " . json_encode($users));
            } catch (Exception $ex) {
                $this->emError("Exception occurred when retrieving users with user rights", $ex->getMessage());
            }
            if (in_array($user, $users) || (defined(SUPER_USER) && SUPER_USER)) {

                // Find the designated contact project where the data is stored. If it is not setup yet, exit
                $pmon_pid = $this->getSystemSetting('designated-contact-pid');
                $pmon_event_id = $this->getSystemSetting('designated-contact-event-id');
                if (empty($pmon_pid) || empty($pmon_event_id)) {
                    return;
                }

                // Retrieve the designated contact in the Project monitoring project
                $contact_fields = array('contact_id', 'contact_firstname', 'contact_lastname', 'contact_timestamp', 'force_update');
                $data = \REDCap::getData($pmon_pid, 'array', $project_id, $contact_fields);
                $current_contact = $data[$project_id][$pmon_event_id]['contact_id'];
                $force_update = $data[$project_id][$pmon_event_id]['force_update']['1'];

                if (empty($current_contact)) {
                    $bg_color = "#ffcccc";
                    $current_person = "<b>No Designated Contact:</b>  Please setup a Designated Contact ";
                    $contact_timestamp = "";
                    $button_text = " Select Here! ";
                } else {
                    if ($force_update) {
                        $bg_color = "#cce9ff";
                        $current_person = "<b>Please verify:</b>  " . str_replace("'", "&#39;", $data[$project_id][$pmon_event_id]['contact_firstname']) . ' ' . str_replace("'", "&#39;", $data[$project_id][$pmon_event_id]['contact_lastname']) . ' as the project Designated Contact!';
                        $contact_timestamp = "";
                        $button_text = " Verify! ";
                    } else {
                        $bg_color = "#ccffcc";
                        $current_person = "<b>Designated Contact:</b>  " . str_replace("'", "&#39;", $data[$project_id][$pmon_event_id]['contact_firstname']) . ' ' . str_replace("'", "&#39;", $data[$project_id][$pmon_event_id]['contact_lastname']);
                        $contact_timestamp = "(Last updated: " . $data[$project_id][$pmon_event_id]['contact_timestamp'] . ")";
                        $button_text = " Change! ";
                    }
                }

                // Set the max width based on which page we are on
                if (PAGE === 'ProjectSetup/index.php') {
                    $max_width = 800;
                } else {
                    $max_width = 630;
                }

                // Retrieve the list of other selectable contacts
                $availableContacts = $this->retrieveUserInformation($users);

                // This is the Designated Contact modal which is used to change the person designated for that role.
                if ((PAGE === 'ProjectSetup/index.php') && (!empty($current_contact)) && (empty($force_update))) {
                    // If there is already a designated contact, don't display anything on the Project Setup page
                } else {

                    $url = $this->getUrl("src/saveNewContact.php");
                    $dc_description = $this->getSystemSetting('dc_description');
                    $token = $this->getCSRFToken();

                    // Create a new element to hold the information and designated contact modals
                    $userList = '<div id="contactDiv" style="margin:20px 0;font-weight:normal;padding:10px; border:1px solid #ccc; max-width:' . $max_width . 'px; background-color:' . $bg_color . ';" >';

                    try {
                        $modal = new GetModal();
                        $userList .= $modal->getInfoModal($dc_description);
                        $userList .= $modal->getDCModal($availableContacts, $current_contact, $user,
                            $current_person, $contact_timestamp, $button_text, $url, $token);
                    } catch (Exception $ex) {
                        $this->emError("Error retrieving modal information: " . $ex->getMessage());
                    }
                    // Close off the new div
                    $userList .= '</div>';

                    require_once $this->getModulePath() . "src/modals.php";

                }
            }
        }
    }

    /**
     * CRON JOBS
     */

    /**
     * This cron job will move projects to completed status when the following list of criteria is met:
     *
     *      1. All users on the project are suspended (which mean they haven't logged into REDCap for 12 months)
     *      2. The last logged entry is > 12 months ago
     *      3. The project is in development or production mode
     *      4. No designated contact selected
     *
     * This cron will run weekly.
     */
    public function moveProjectsToComplete() {

        $this->emDebug("In CRON: moveProjectsToComplete");

        // Retrieve the project id where the designated contact data is stored
        $dc_pid = $this->getSystemSetting('designated-contact-pid');

        // Update the temporary table which looks at all projects with no current users and retrieve all
        // the log tables we need to look at to see when the last log event was
        try {
            $contacts = new newContact($this);
            $log_tables = $contacts->updateSuspendedUserTable();
            $this->emDebug("Log tables to query against: " . json_encode($log_tables));

            foreach ($log_tables as $log_table => $log_table_name) {

                // Cross reference the suspended user table with the log event table to see which projects'
                // last log entry was > 12 months ago
                $project_ids = $contacts->lastLogDate($log_table_name['log_event_table']);
                $this->emDebug("project ids: " . json_encode($project_ids));

                foreach ($project_ids as $pid) {
                    $this->emDebug("This project will be moved to Complete: " . $pid);

                    //These are the project to move to Complete status
                    $status = $contacts->moveToComplete($dc_pid, $pid);
                    if ($status) {
                        $this->emDebug("Project $pid was automatically moved to Completed status");
                    } else {
                        $this->emError("Project $pid could not be automatically moved to Completed status");
                    }
                }
            }
        } catch (Exception $ex) {
            $this->emError("Exception thrown in moveProjectsToComplete", $ex->getMessage());
        }

        $this->emDebug("Exiting CRON: moveProjectsToComplete");
    }

    /**
     * This cron will set the Designated Contact to the creator of the project for new projects when
     * one wasn't already selected
     *
     * This cron will run nightly.
     */
    public function newProjectsNoDC() {

        $this->emDebug("In CRON: newProjectsNoDC");

        // Retrieve the project id where the designated contact data is stored
        $dc_pid = $this->getSystemSetting('designated-contact-pid');

        // Find the new projects created in the last 2 days that do not have DC selected yet
        try {
            $contacts = new newContact($this);
            $new_pids = $contacts->newProjectSetDC($dc_pid);
            $this->emDebug("These are the projects where the automated cron set the designated contact: " . json_encode($new_pids));
        } catch (Exception $ex) {
            $this->emError("Exception throw in newProjectsNoDC", $ex->getMessage());
        }

        $this->emDebug("Exiting CRON: newProjectsNoDC");
    }

    /**
     * This cron will look for DC who are suspended and set the new DC to the user who has User Rights and was the last
     * user to have made an entry in the log file. If no active users have User Rights, set the status to Orphaned in
     * the REDCap DC project.
     */
    public function reassignDesignatedContacts() {

        $this->emDebug("In CRON: reassignDesignatedContacts");

        // Retrieve the project id where the designated contact data is stored
        $dc_pid = $this->getSystemSetting('designated-contact-pid');
        $dc_event_id = $this->getSystemSetting('designated-contact-event-id');
        $subject = $this->getSystemSetting('auto-reassign-subject-email');
        $body = $this->getSystemSetting('auto-reassign-body-email');
        $from_addr = $this->getSystemSetting('from-address');

        // Retrieve the URLs of the DC and Suspended User wiki pages to insert into the email.
        $dc_wiki = $this->getSystemSetting('dc-wiki-url');
        $su_wiki = $this->getSystemSetting('susp-user-wiki-url');

        // Look for users who are the designated contacts for a project but are suspended
        try {
            $contacts = new newContact($this);
            $pids = $contacts->projectsWithSuspendedDC();
            $this->emDebug("Projects with suspended DC: " . json_encode($pids));

            if (!empty($pids)) {
                $base_url = APP_PATH_WEBROOT_FULL . 'redcap_v' . REDCAP_VERSION . '/UserRights/index.php';
                [$orphaned, $reassigned] = $contacts->findNewDesignatedContact($dc_pid, $dc_event_id, $pids, REASSIGN_DC,
                    $base_url, $subject, $body, $from_addr, $dc_wiki, $su_wiki);
                if (count($reassigned) > 0) {
                    $this->emDebug("These are projects that are re-assigned because the selected DC is suspended: " . json_encode($reassigned));
                }
                if (count($orphaned) > 0) {
                    $this->emDebug("These are projects that are orphaned because there are no users available to set as DC: " . json_encode($orphaned));
                }
            }
        } catch (Exception $ex) {
            $this->emError("Exception thrown in reassignDesignatedContacts", $ex->getMessage());
        }

        $this->emDebug("Exiting CRON: reassignDesignatedContacts");
    }

    /**
     * This cron will look for projects who do not have a designated contact assigned and assign a user with User Rights.
     * This follows the same logic when projects have Designated Contacts who have been suspended.
     */
    public function selectDesignatedContacts() {

        $this->emDebug("In CRON: selectDesignatedContact");

        // Retrieve the project id where the designated contact data is stored
        $dc_pid = $this->getSystemSetting('designated-contact-pid');
        $dc_event_id = $this->getSystemSetting('designated-contact-event-id');
        $subject = $this->getSystemSetting('auto-assign-subject-email');
        $body = $this->getSystemSetting('auto-assign-body-email');
        $from_addr = $this->getSystemSetting('from-address');

        // Retrieve the URL of the DC wiki page to insert into the email.
        $dc_wiki = $this->getSystemSetting('dc-wiki-url');

        // Look for projects who are not deleted, not completed and have no designated contacts
        try {
            $contacts = new newContact($this);
            $pids = $contacts->projectsWithNoDC();
            $this->emDebug("Projects with no Designated Contacts: " . json_encode($pids));

            if (!empty($pids)) {
                $base_url = APP_PATH_WEBROOT_FULL . 'redcap_v' . REDCAP_VERSION . '/UserRights/index.php';
                [$orphaned, $reassigned] = $contacts->findNewDesignatedContact($dc_pid, $dc_event_id, $pids, ASSIGN_DC,
                    $base_url, $subject, $body, $from_addr, $dc_wiki, null);
                if (count($reassigned) > 0) {
                    $this->emDebug("These are projects that are assigned a DC because none was selected: " . json_encode($reassigned));
                }
                if (count($orphaned) > 0) {
                    $this->emDebug("These are projects that are orphaned because there are no users available to set as DC: " . json_encode($orphaned));
                }
            }
        } catch (Exception $ex) {
            $this->emError("Exception throw in selectDesignatedContacts", $ex->getMessage());
        }

        $this->emDebug("Exiting CRON: selectDesignatedContact");
    }


    /**
     *
     * Need to override the EM method to limit display of EM link
     *
     * @param $project_id
     * @param $link
     * @return mixed
     */
    public function redcap_module_link_check_display($project_id, $link)
    {
        //limit the logging link to Super Users
        if ($link['key'] = 'adminLogging') {
            if (SUPER_USER) {
                return $link;
            } else {
                return null;
            }
        }
        return $link;
    }

    /**
     * Get Designated Contact for this project
     *
     * @param $project_id
     * @return mixed
     */
    public function getDC($project_id) {
        $sql = sprintf("select contact_email from designated_contact_selected where project_id = '%s'",
                       prep($project_id));
        $q = db_query($sql);
        $fetch_row = db_fetch_row($q);
        $dc_email = $fetch_row[0];
        return $dc_email;

    }

    /**
     * Given an array of userids, this function will return the user name and contact info for each user.
     *
     * @param $users - array of userIds
     * @return array - user information associated with the userId
     */
    public function retrieveUserInformation($users) {

        $contact = array();
        $this->emDebug("User: " . json_encode($users));

        // Retrieve the rest of the data for this contact
        $sql = "select user_email, user_phone, user_firstname, user_lastname, username, ui_id " .
            "    from redcap_user_information " .
            "    where username in ('" . implode("','", $users) . "')";
        $q = db_query($sql);
        while ($current_db_row = db_fetch_assoc($q)) {
            $user = $current_db_row['username'];
            $contact[$user]['contact_id'] = $user;
            $contact[$user]['contact_ui_id'] = $current_db_row['ui_id'];
            $contact[$user]["contact_firstname"] = $current_db_row["user_firstname"];
            $contact[$user]["contact_lastname"] = $current_db_row["user_lastname"];
            $contact[$user]["contact_email"] = $current_db_row["user_email"];
            $contact[$user]["contact_phone"] = $current_db_row["user_phone"];
        }

        return $contact;

    }

    /**
     * This is an utility function which will perform all the actions needed when a new designated contact is saved.  It will
     * update the database table designated_contact_selected, it will update the REDCap project 22052 with the new contact
     * and it will create a log entry in the project.
     *
     * @param $dc_pid
     * @param $project
     * @param $log_action
     * @param $log_description
     * @return bool
     */
    public function saveNewDC($dc_pid, $project, $log_action, $log_description) {

        $status = true;

        if (empty($project['contact_id'])) {
            $this->emError("Contact ID for new DC is empty for log action " . $log_action .
                            " and log description " . $log_description);
            return false;
        }

        // Save the new user into the designated_contact_selected DB table
        $sql = 'replace into ' . DB_TABLE . '
            (project_id, contact_first_name, contact_last_name, contact_email, contact_userid, last_update_date, contact_ui_id)
                select ' . $project['project_id'] . ', "' . $project['contact_firstname'] . '", "' .
            $project['contact_lastname'] . '", "' . $project['contact_email'] . '", "' .
            $project['contact_id'] . '", "' . $project['contact_timestamp'] . '", ' .
            (empty($project['contact_ui_id']) ? '""': $project['contact_ui_id']);
        $q2 = db_query($sql);
        if ($q2) {

            // The database table was updated, now update the REDCap project
            $response = \REDCap::saveData($dc_pid, 'json', json_encode(array($project)));
            if (!empty($response['errors'])) {
                $status = false;
            }

            // Make an entry in the REDCap log file that the designated contact was selected
            \REDCap::logEvent($log_action, $log_description, null, null, null, $project['project_id']);

        } else {

            // If the database update did not work, send back an error status
            $status = false;
        }

        return $status;
    }


}
