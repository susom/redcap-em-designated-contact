<?php

namespace Stanford\DesignatedContact;
/** @var \Stanford\DesignatedContact\DesignatedContact $module **/

require_once "emLoggerTrait.php";
require_once "./util/DesignatedContactUtil.php";

use REDCap;


class DesignatedContact extends \ExternalModules\AbstractExternalModule
{

    use emLoggerTrait;

    public function __construct()
    {
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

        $user = USERID;
        if (empty($user)) {
            return;
        }

        /*
         * This section will place the Designated Contact icon next to the project title on the My Projects page.
         */
        if (((PAGE === 'Home/index.php') || (PAGE === 'index.php'))
                && ($_GET["action"] == "myprojects")) {

            // Find the designated contact project where the data is stored
            $pmon_pid = $this->getSystemSetting('designated-contact-pid');
            $pmon_event_id = $this->getSystemSetting('designated-contact-event-id');
            if (empty($pmon_pid) || empty($pmon_event_id)) {
                return;
            }

            // Find the projects that this user is the designated contact and put the icon next to the name
            $projects = contactProjectList($user, $pmon_pid, $pmon_event_id);
            $this->emDebug("Projects for this user: " . json_encode($projects));

            // Find the projects that this user has User Rights but no designated contact was selected
            $no_dc_projects = noContactSelectedList($user, $pmon_pid);
            $this->emDebug("No DC projects: " . json_encode($no_dc_projects));

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

            // See if this user has User Rights. If not, just exit
            $users = getUsersWithUserRights($project_id);
            $this->emDebug("These are users with User Rights for project $project_id: " . json_encode($users));
            if (in_array($user, $users) || ((defined("SUPER_USER") && SUPER_USER))) {

                // Find the designated contact project where the data is stored. If it is not setup yet, exit
                $pmon_pid = $this->getSystemSetting('designated-contact-pid');
                $pmon_event_id = $this->getSystemSetting('designated-contact-event-id');
                if (empty($pmon_pid) || empty($pmon_event_id)) {
                    return;
                }

                // Retrieve the designated contact in the Project monitoring project
                $contact_fields = array('contact_id', 'contact_firstname', 'contact_lastname', 'contact_timestamp', 'force_update');
                $data = REDCap::getData($pmon_pid, 'array', $project_id, $contact_fields);
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
                        $current_person = "<b>Please verify:</b>  " . $data[$project_id][$pmon_event_id]['contact_firstname'] . ' ' . $data[$project_id][$pmon_event_id]['contact_lastname'] . ' as the project Designated Contact!';
                        $contact_timestamp = "";
                        $button_text = " Verify! ";
                    } else {
                        $bg_color = "#ccffcc";
                        $current_person = "<b>Designated Contact:</b>  " . $data[$project_id][$pmon_event_id]['contact_firstname'] . ' ' . $data[$project_id][$pmon_event_id]['contact_lastname'];
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
                $availableContacts = retrieveUserInformation($users);

                // This is the Designated Contact modal which is used to change the person designated for that role.
                if ((PAGE === 'ProjectSetup/index.php') && (!empty($current_contact)) && (empty($force_update))) {
                    // If there is already a designated contact, don't display anything on the Project Setup page
                } else {

                    // Create a new element to hold the information and designated contact modals
                    $userList = '<div id="contactDiv" style="margin:20px 0;font-weight:normal;padding:10px; border:1px solid #ccc; max-width:' . $max_width . 'px; background-color:' . $bg_color . ';" >';

                    $userList .= $this->getInfoModal();
                    $userList .= $this->getDCModal($availableContacts, $current_contact, $user,
                        $current_person, $contact_timestamp, $button_text);

                    // Close off the new div
                    $userList .= '</div>';

                    require_once $this->getModulePath() . "src/modals.php";

                }
            }
        }
    }

    private function getInfoModal() {

        $dc_description = $this->getSystemSetting('dc_description');

        // This is the information modal which describes what a designated contact is
        $userList  = '<div id="infomodal" class="modal" tabindex="-1" role="dialog">';
        $userList .= '    <div class="modal-dialog modal-sm" role="document">';
        $userList .= '      <div class="modal-content">';
        $userList .= '        <div class="modal-header" style="background-color:maroon;color:white">';
        $userList .= '          <h6 class="modal-title">What is a Designated Contact?</h6>';
        $userList .= '          <button type="button" class="close" data-dismiss="modal" aria-label="Close">';
        $userList .= '              <span style="color:white;" aria-hidden="true">&times;</span>';
        $userList .= '          </button>';
        $userList .= '        </div>';
        $userList .= '        <div class="modal-body"><span>' . $dc_description . '</span></div>';
        $userList .= '      </div>';
        $userList .= '    </div>';
        $userList .= '</div>';

        return $userList;

    }

    private function getDCModal($availableContacts, $current_contact, $user, $current_person, $contact_timestamp, $button_text) {

        $url = $this->getUrl("src/saveNewContact.php");
        $isMe = ($current_contact == $user);

        // This is the modal to update the designated contact
        $userList  = '    <div id="contactmodal" class="modal" tabindex="-1" role="dialog">';
        $userList .= '       <div class="modal-dialog" role="document">';
        $userList .= '          <div class="modal-content">';
        $userList .= '              <div class="modal-header" style="background-color:maroon;color:white">';
        $userList .= '                  <h5 class="modal-title">Choose a new Designated Contact</h5>';
        $userList .= '                  <button type="button" class="close" data-dismiss="modal" aria-label="Close">';
        $userList .= '                      <span style="color:white;" aria-hidden="true">&times;</span>';
        $userList .= '                  </button>';
        $userList .= '              </div>';

        $userList .= '              <div class="modal-body text-left">';
        $userList .= '                  <input id="url" type="text" value="' . $url . '" hidden>';
        $userList .= '                  <div style="margin: 10px 0; font-weight:bold;"><span style="font-weight:normal;">' . $current_person . '</span></div>';
        $userList .= '                  <div style="margin:20px 0 0 0;font-weight:bold;" > ';
        $userList .= '                      Select a new contact:';
        $userList .= '                        <select id="selected_contact" name="selected_contact">';

        // Add users that have User Rights to the selection list.
        foreach ($availableContacts as $username => $userInfo) {
            $userList .= '<option value="' . $userInfo['contact_id'] . '">' . str_replace("'", "&#39;", $userInfo['contact_firstname'])
                                            . ' ' . str_replace("'", "&#39;", $userInfo['contact_lastname']) . ' [' . $userInfo['contact_id'] . ']' . '</option>';
        }

        $userList .= '                        </select>';
        $userList .= '                        <div style="font-size:10px;color:red;">* Only users with User Right privileges can be Designated Contacts.</div>';

        // Display to the user that the new designated contact will receive email that they were put in this role.
        $userList .= '                        <div style="font-weight:normal;margin-top:20px;">';
        $userList .= '                            <b>Note:</b><br>';
        $userList .= '                            <ul>';
        $userList .= '                                <li style="margin-top:5px;">An email will be sent to the new Designated contact to let them know they were added to this role.</li>';

        // Only send the current Designated Contact an email if they are not the person making the change
        if (!$isMe && !empty($current_contact)) {
            $userList .= '                            <li style="margin-top:5px;">An email will be sent to the current Designated Contact to let them know they were removed from this role.</li>';
        }
        $userList .= '                            </ul>';
        $userList .= '                        </div>';
        $userList .= '                  </div>';
        $userList .= '                  <div style="margin-top:40px;text-align:right">';
        $userList .= '                        <input type="button" data-dismiss="modal" value="Close">';
        $userList .= '                        <input type="submit" onclick="saveNewContact()" value="Save">';
        $userList .= '                  </div>';
        $userList .= '              </div>';      // Modal body
        $userList .= '          </div>';         // Modal content
        $userList .= '       </div>';            // document
        $userList .= '    </div>';

        /*
         * This is the block display on the User Rights page or on the Project Setup page
         * (if a Designated Contact is not selected)
         */
        if (empty($current_contact)) {

            // There is no currently selected contact
            $userList .= '<div>';
            $userList .= '    <span style="margin-left:5px;">';
            $userList .= '        <button type="button" class="fas fa-question-circle" style="margin-right:10px" title="What is a designated contact?" data-toggle="modal" data-target="#infomodal"></button>';
            $userList .= '    </span>';
            $userList .= '    <span style="font-weight:bold;color:#000; ">';
            $userList .= '        <i class="fas fa-exclamation-triangle" style="margin-right:5px;"></i>';
            $userList .= '    </span>';
            $userList .= '    <span style="margin:5px;">' . $current_person . '</span>';
            $userList .= '    <button type="button" class="btn btn-sm btn-primary" style="font-size:12px" data-toggle="modal" data-target="#contactmodal">' . $button_text . '</button>';
            $userList .= '</div>';

        } else {

            // The contact is already selected
            $userList .= '<div>';
            $userList .= '    <span style="margin-left:5px;">';
            $userList .= '        <button type="button" class="fas fa-question-circle" style="margin-right:10px" title="What is a designated contact?" data-toggle="modal" data-target="#infomodal"></button>';
            $userList .= '    </span>';
            $userList .= '    <span style="font-weight:bold;color:#000;">';
            $userList .= '        <i class="fas fa-address-book" style="margin-right:5px;"></i>';
            $userList .= '    </span>';
            $userList .= '    <span style="margin-right:5px;">' . $current_person . '</span>';
            $userList .= '    <span style="font-size:10px;margin-right:5px;">' . $contact_timestamp . '</span>';
            $userList .= '    <button type="button" class="btn btn-sm btn-secondary" style="font-size:12px" data-toggle="modal" data-target="#contactmodal">' . $button_text . '</button>';
            $userList .= '</div>';
        }

        return $userList;
    }

    /**
     * CRON JOBS
     */

    /**
     * This cron job will move projects to completed status when the following list of criteria is met:
     *
     *      1. All users on the project are suspended (which mean they haven't logged into REDCap for 6 months?)
     *      2. The last logged entry is > 18 months ago
     *      3. The project is in development or production mode
     *      4. No designated contact selected
     *
     * This cron will run weekly.
     */
    public function moveProjectsToComplete() {

        // Retrieve the project id where the designated contact data is stored
        $dc_pid = $this->getSystemSetting('designated-contact-pid');
        $this->emDebug("DC project ID: " . $dc_pid);

        // Update the temporary table which looks at all projects with no current users and retrieve all
        // the log tables we need to look at to see when the last log event was
        $log_tables = updateSuspendedUserTable();
        $this->emDebug("Log tables to query against: " . json_encode($log_tables));

        foreach($log_tables as $log_table => $log_table_name) {

            // Cross reference the suspended user table with the log event table to see which projects'
            // last log entry was > 12 months ago
            $project_ids = lastLogDate($log_table_name['log_event_table']);
            $this->emDebug("project ids: " . json_encode($project_ids));

            foreach ($project_ids as $pid) {
                $this->emDebug("This project will be moved to Complete: " . $pid);

                //These are the project to move to Complete status
                $status = moveToComplete($dc_pid, $pid);
                $this->emDebug("This is the response from saveData: " . json_encode($status));
                if ($status) {
                    $this->emDebug("Project $pid was automatically moved to Completed status");
                } else {
                    $this->emError("Project $pid could not be automatically moved to Completed status");
                }
            }
        }
    }

    /**
     * This cron will set the Designated Contact to the creator of the project for new projects when
     * one wasn't already selected
     *
     * This cron will run nightly.
     */
    public function newProjectsNoDC() {

        // Retrieve the project id where the designated contact data is stored
        $dc_pid = $this->getSystemSetting('designated-contact-pid');
        $this->emDebug("Designated Contact project: " . $dc_pid);

        // Find the new projects created in the last 2 days that do not have DC selected yet
        $new_pids = newProjectSetDC($dc_pid);
        $this->emDebug("These are the list of projects that the automated cron set the designated contact: " . json_encode($new_pids));

    }

    /**
     * This cron will look for DC who are suspended and set the new DC to the user who has User Rights and was the last
     * user to have made an entry in the log file. If no active users have User Rights, set the status to Orphaned in
     * the REDCap DC project.
     */

    public function reassignDesignatedContact() {

        // Retrieve the project id where the designated contact data is stored
        $dc_pid = $this->getSystemSetting('designated-contact-pid');

        // Look for users who are the designated contacts for a project but are suspended
        $pids = projectsWithSuspendedDC();
        $this->emDebug("Projects with suspended DC: " . json_encode($pids));

        if (!empty($pids)) {
            $status = $this->updateDesignatedContact($dc_pid, $pids);
            $this->emDebug("Log table: " . $status);
        }
    }
/*
    private function updateDesignatedContact($dc_pid, $pids) {

        $updated = array();
        $status = true;
        $now = date("Y-m-d H:i:s");

        // Retrieve list of orphaned projects so we don't keep updating with a new date
        $filter = "[cron_date_orphaned_project] <> ''";
        $data = $this->getProjectData($dc_pid, $filter, array('project_id'));
        $orphaned_projects = array_keys($data);
        $this->emDebug("Projects that already have an orphan data: " . json_encode($orphaned_projects));

        // Loop over each project and see if there is another person we can set as the designated contact
        foreach ($pids as $pid) {

            // Retrieve all users with user rights that are not suspended
            $users = getUsersWithUserRights($pid);
            if (empty($users)) {

                // Only update the date if it is clear since we want to know when the project first became orphaned.
                if (!in_array($pid, $orphaned_projects)) {
                    // No other users can be made a designated contact.  We consider this orphaned
                    $updated[$pid]['project_id'] = $pid;
                    $updated[$pid]['cron_status'] = 'Orphaned';
                    $updated[$pid]['cron_date_orphaned_project'] = $now;
                    $updated[$pid]['cron_updates_complete'] = 2;
                } else {
                    $this->emDebug("Already in orphan array $pid");
                }

            } else{

                // Find who the new dc should be
                $latest_user = $this->findUserWithLastLoggedEvent($pid, $users);
                if (!empty($latest_user)) {

                    // Save the new user


                    // Save the new status
                    $updated[$pid]['project_id'] = $pid;
                    $updated[$pid]['cron_status'] = 'Re-selected';
                    $updated[$pid]['cron_date_reselected_dc'] = $now;
                    $updated[$pid]['cron_updates_complete'] = 2;

                    // Make a log entry so users can tell what happened
                    REDCap::logEvent('Automated Cron', 'Automatically set Designated Contact to ' . $latest_user, null, null, null, $pid);
                }
            }
        }

        // If there are some updated projects, save them to the REDCap tracking project
        if (!empty($updated)) {
            $response = REDCap::saveData($dc_pid, 'json', json_encode($updated));
            if (!empty($response['errors'])) {
                $status = false;
            }
        }

        return $status;

    }

    private function getProjectData($pid, $filter, $fields) {

        $data = REDCap::getData($pid, 'array', null, $fields, null, null, null, null, null, $filter);
        return $data;

    }

    private function findUserWithLastLoggedEvent($pid, $users) {

        // Check to see if there are more than one user who has User Rights
        $this->emDebug("Users: " . json_encode($users));
        if (count($users) > 1) {

            // Find the log table where this project's data is stored
            $sql = 'select log_event_table from redcap_projects where project_id = ' . $pid;
            $q = db_query($sql);
            $log_table = db_fetch_row($q);
            $this->emDebug("Log table: " . $log_table);

            // Retrieve the log entries for the users with
            $user_list = implode("','", $users);
            $sql = "select (select user from " . $log_table . " where log_event_id = log.log_event_id) as last_log_user
                        from (
                            select max(rl.log_event_id) as log_event_id
                                from " . $log_table . " rl
                                    join redcap_user_information rui on rui.username = rl.user
                                where rl.project_id = " . $pid . "
                                and rl.user in ('" . $user_list . "')
                                and rl.ts is not null
                        ) as log";
            $q = db_query($q);
            $new_user = db_fetch_row($q)[0];

        } else {
            $new_user = $users[0];
        }

        return $new_user;
    }
*/

}
