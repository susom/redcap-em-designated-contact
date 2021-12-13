<?php
namespace Stanford\DesignatedContact;
/** @var \Stanford\DesignatedContact\DesignatedContact $module **/

use REDCap;

/**
 * This function creates the model with the red or green box which displays who is the selected DC.
 *
 * @param $dc_description
 * @return string
 */
function getInfoModal($dc_description) {

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


/**
 * This function creates the model when someone wants to change the DC.  A dropdown list of available users is displayed
 * and can be selected.  Only users who have User Right permissions for the project are included in this list.
 *
 * @param $availableContacts
 * @param $current_contact
 * @param $user
 * @param $current_person
 * @param $contact_timestamp
 * @param $button_text
 * @param $url
 * @return string
 */
function getDCModal($availableContacts, $current_contact, $user, $current_person, $contact_timestamp, $button_text, $url) {

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
 * This function retrieves the list of users who have User Rights privileges who are not suspended for this project.
 *
 * @param $project_id
 * @return array
 */

function getUsersWithUserRights($project_id) {

    $db_query = "select rur.username
                    from redcap_user_rights rur
                        left join redcap_user_roles ruro on rur.role_id = ruro.role_id
                        join redcap_projects rp on rur.project_id = rp.project_id
                        join redcap_user_information rui on rui.username = rur.username
                    where  rp.completed_time is null
                    and rp.date_deleted is null
                    and ifnull(ruro.user_rights, rur.user_rights) = 1
                    and rur.expiration is null
                    and rui.user_suspended_time is null
                    and rur.project_id = '" . $project_id . "'";

    $users = array();
    $q = db_query($db_query);
    while($proj_id = db_fetch_assoc($q)) {
        $users[] = $proj_id['username'];
    }

    return $users;

}


/**
 * Given an array of userids, this function will return the user name and contact info for each user.
 *
 * @param $users - array of userIds
 * @return array - user information associated with the userId
 */
function retrieveUserInformation($users) {

    $contact = array();

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
 * This function will retrieve a list of projects that this person is the Designated Contact for.  This list
 * is used to place on icon next to the project name on the 'My Projects' page.
 *
 * @param $user - user ID of current user
 * @param $pmon_pid - project id of Designated Contact project where data is stored
 * @param $pmon_event_id - event id of Designated Contact project where data is stored
 * @return array - Projects that this user is the Designated Contact
 */

function contactProjectList($user, $pmon_pid, $pmon_event_id) {

    $filter = '[contact_id] = "' . $user . '"';
    $data = REDCap::getData($pmon_pid, 'array', null, array('project_id'), $pmon_event_id, null, null, null, null, $filter);
    $records = array();
    foreach ($data as $record_id => $record_info) {
        $records[$record_id] = $record_id;
    }

    return $records;
}


/**
 * This function will retrieve a list of projects that this person is a Project Admin or has User Rights and that does
 * not have a Designated Contact selected. Since the Designated Contact table is now being updated in real-time, we can
 * query against it rather than querying against the redcap_data table for currently selected DC projects.
 *
 * @param $user - user ID of current user
 * @return array - Projects that this user is the Designated Contact
 */

function noContactSelectedList($user) {

    $db_query = "select rur.project_id
                    from redcap_user_rights rur
                        left join redcap_user_roles ruro on rur.role_id = ruro.role_id
                        join redcap_projects rp on rur.project_id = rp.project_id
                    where  rp.completed_time is null
                    and ifnull(ruro.user_rights, rur.user_rights) = 1
                    and rur.expiration is null
                    and rur.project_id not in (select project_id from designated_contact_selected)
                    and rur.username='" . $user . "'";

    $records = array();
    $q = db_query($db_query);
    while($proj_id = db_fetch_assoc($q)) {
        $records[] = $proj_id['project_id'];
    }

    return $records;
}


/**
 * This function will create a temporary table of projects with all suspended users
 *
 * @return array
 */
function updateSuspendedUserTable() {

    // Drop the temp table if it exists
    $db_query = 'drop table if exists ly_dc_suspended_users';
    $q = db_query($db_query);

    // Create a new temporary table with a list of projects with all suspended users
    $db_query = 'create table ly_dc_suspended_users as
            select *
            from (
                select rur.project_id,
                       sum(case when rui.user_suspended_time is null then 1 else 0 end) as not_suspended,
                       sum(case when rui.user_suspended_time is not null then 1 else 0 end) as suspended,
                       rp.log_event_table,
                       rp.status
                    from redcap_user_rights rur
                        left join redcap_user_roles ruro on rur.role_id = ruro.role_id
                        join redcap_projects rp on rur.project_id = rp.project_id
                        join redcap_user_information rui on rui.username = rur.username
                    where rp.completed_time is null
                    and rp.date_deleted is null
                    and rp.status in (0,1)
                    and ifnull(ruro.user_rights, rur.user_rights) = 1
                    and rur.expiration is null
                    and rur.project_id not in (
                                select project_id from designated_contact_selected
                    )
                group by rur.project_id
            ) as users
            where not_suspended = 0';
    $q = db_query($db_query);

    //Find the log file names that we need to search for the last log event timestamp
    $db_query = 'select log_event_table
                            from ly_dc_suspended_users
                            group by log_event_table';
    $q1 = db_query($db_query);
    $log_table_names = array();
    while ($log_table = db_fetch_assoc($q1)) {
        $log_table_names[] = $log_table;
    }

    return $log_table_names;

}

/**
 * This function will join the table of projects with all suspended users, with the log tables, to see
 * which of these projects haven't had activity in the log event table for over 12 months.
 *
 * @param $log_table_name
 * @return array
 */
function lastLogDate($log_table_name) {

    // Query to see which of these project has their last log event more than 12 months ago
    $proj_last_log_date =
        "select project_id, status, last_log_time, now() as completed_date
                    from (
                        select ur.project_id, ur.status, max(str_to_date(le.ts, '%Y%m%d%H%i%s')) as last_log_time
                                from ly_dc_suspended_users ur
                                    join " . $log_table_name . " le on ur.project_id = le.project_id
                                where ur.not_suspended = 0
                                and ur.log_event_table = '" . $log_table_name. "'
                                and ur.status in (0,1)
                                group by ur.project_id, status
                         ) as log_date
                    where last_log_time < DATE_SUB(now(), INTERVAL 12 MONTH)";
    $q = db_query($proj_last_log_date);

    $list_of_projects = array();
    while ($projects = db_fetch_assoc($q)) {
        $list_of_projects[] = $projects['project_id'];
    }

    return $list_of_projects;

}

/**
 * This function will update the database with a completion timestamp and user which makes the project Complete.
 * If the update is successful, we put an entry in the log table and update the redcap master designated contact project which tracks
 * designated contacts and project status from cron jobs. The project is defined in the System Settings of the
 * EM Config file.
 *
 * @param $dc_pid
 * @param $complete_pid
 * @return bool
 */
function moveToComplete($dc_pid, $complete_pid)
{

    // To move a project to Completion, update the database with a completed time and user
    $sql = "update redcap_projects
                    set completed_time = '" . date("Y-m-d H:i:s") . "',
                        completed_by = 'site_admin'
                    where project_id = " . $complete_pid;
    $q = db_query($sql);
    if ($q) {

        // Put a message in the log of the project
        REDCap::logEvent("Automated Cron", "Automatically moved to Completed status", null, null, null, $complete_pid);

        // Update the Designated Project project so we know this happened
        $save_to_dc_project = array();
        $save_to_dc_project['project_id'] = $complete_pid;
        $save_to_dc_project['cron_status'] = 'Completed';
        $save_to_dc_project['cron_date_moved_to_completed'] = date("Y-m-d H:i:s");
        $save_to_dc_project['cron_updates_complete'] = 2;
        $response = REDCap::saveData($dc_pid, 'json', json_encode(array($save_to_dc_project)));
        $status = (empty($response['errors']) ? true : false);
    } else {
        $status = false;
    }

    return $status;
}


/**
 * When new projects are created and do not select a Designated Contact, set the DC to the person who created the
 * project. To set a DC, update the designated_contact_selected table in the DB, enter the DC in the REDCap
 * project and put a log message in the Log to say this was done by the automated cron. This cron runs nightly.
 *
 * @param $dc_pid
 * @return array
 */
function newProjectSetDC($dc_pid) {

    $now = date('Y-m-d H:i:s');
    $proj_ids = array();

    // Retrieve the user id of the person who created the project and set them as the designated contact
    $sql =
        'select rp.project_id, rui.user_firstname as contact_firstname, rui.user_lastname as contact_lastname,
                rui.user_email as contact_email, rui.username as contact_id, rui.ui_id as contact_ui_id
        from redcap_projects rp
            join redcap_user_information rui on rp.created_by = rui.ui_id
        where rp.creation_time between DATE_SUB(now(), INTERVAL 3 DAY) and now()
        and rp.project_id not in (select project_id from designated_contact_selected)';
    $q = db_query($sql);
    while ($proj_and_creators = db_fetch_assoc($q)) {

        // Retrieve the id of the creator of the project
        $project                                = $proj_and_creators;
        $project['contact_timestamp']           = $now;
        $project['designated_contact_complete'] = 2;
        $project['cron_status']                 = ASSIGN_DC;
        $project['cron_date_selected_dc']       = $now;
        $project['cron_updates_complete']       = 2;

        // Save this new DC
        $status = saveNewDC($dc_pid, $project, 'Automated Cron', "Automatically set Designated Contact to " . $project['contact_id']);
        if ($status) {
            // Create a list of projects that have the DC set by the cron job
            $proj_ids[] = $project['project_id'];
        }
    }

    return $proj_ids;
}

/**
 * This function will retrieve projects whose DC is suspended.
 *
 * @return array
 */
function projectsWithSuspendedDC() {

    $list_of_projects = array();

    // Find the project IDs who have Designated Contacts who are suspended
    $sql = 'select dcs.project_id
                    from designated_contact_selected dcs
                        join redcap_user_information rui on rui.username = dcs.contact_userid
                    and rui.user_suspended_time is not null';
    $q = db_query($sql);
    while ($suspended_dc = db_fetch_assoc($q)) {
        $list_of_projects[] = $suspended_dc['project_id'];
    }

    return $list_of_projects;
}


/**
 * This function will retrieve projects with no DC.
 *
 * @return array
 */

function projectsWithNoDC() {

    $list_of_projects = array();

    // Find the project IDs who have no designated contacts
    $sql = 'select project_id
                    from redcap_projects
                    where date_deleted is null
                    and completed_time is null
                    and project_id not in (
                        select project_id from  designated_contact_selected
                    )';
    $q = db_query($sql);
    while ($no_dc = db_fetch_assoc($q)) {
        $list_of_projects[] = $no_dc['project_id'];
    }

    return $list_of_projects;
}


/**
 * This function will loop over projects whose designated contact is suspended.  If another person with User Rights is
 * found, that person will be assigned the DC.  If there is more than one person with User Rights, the person who
 * has the latest log event will become the new DC.
 *
 * If no other users have User Rights on the project, set the project status as Orphaned in the DC REDCap project.
 *
 * @param $dc_pid
 * @param $dc_event_id
 * @param $pids
 * @param $action
 * @param $base_url
 * @param $email_subject
 * @param $email_body
 * @param $from_addr
 * @return bool
 */
function findNewDesignatedContact($dc_pid, $dc_event_id, $pids, $action, $base_url, $email_subject, $email_body, $from_addr) {

    $updated_statuses = array();
    $orphaned = array();
    $now = date("Y-m-d H:i:s");

    // Retrieve list of projects so we don't keep updating with a new date
    $data = REDCap::getData($dc_pid, 'array', null, array('cron_status', 'contact_email', 'contact_firstname', 'contact_lastname'));

    // Loop over each project and see if there is another person we can set as the designated contact
    $updated = array();
    foreach ($pids as $pid) {

        // Retrieve all users with user rights that are not suspended
        $users = getUsersWithUserRights($pid);
        if (count($users) > 1) {
            // If there are more than 1 user that has User Rights, see which has the last log entry
            $latest_user = findUserWithLastLoggedEvent($pid, $users);
            $new_user = $latest_user[0];
        } else if (count($users) == 1) {
            // If there is only one user with User Rights, they are automatically DC.
            $new_user = $users[0];
        } else if (count($users) == 0) {
            // No one is available to be DC
            $new_user = '';
        }

        // If there is not a user to set as Designated Contact, the status of the project is Orphaned.
        if (empty($new_user)) {

            // Only update the date if it is empty since we want to know when the project first became orphaned.
            if ($data[$pid][$dc_event_id]['cron_status'] != ORPHANED_DC) {

                // No other users can be made a designated contact.  We consider this orphaned
                $updated[$pid]['project_id'] = $pid;
                $updated[$pid]['cron_status'] = ORPHANED_DC;
                $updated[$pid]['cron_date_orphaned_project'] = $now;
                $updated[$pid]['cron_updates_complete'] = 2;
                $response = REDCap::saveData($dc_pid, 'json', json_encode($updated));
                if (empty($response['errors'])) {
                    $orphaned[] = $pid;
                }
            }

        } else{

            // Retrieve info on this latest user
            $user_info = retrieveUserInformation(array($new_user));

            // Add on the additional status info for the REDCap project
            $new_user_info = $user_info[$new_user];
            $new_user_info['contact_timestamp']             = $now;
            $new_user_info['designated_contact_complete']   = 2;
            $new_user_info['project_id']                    = $pid;
            $new_user_info['cron_updates_complete']         = 2;

            if ($action === REASSIGN_DC) {
                $new_user_info['cron_date_reselected_dc']   = $now;
                $new_user_info['cron_status']               = REASSIGN_DC;
            } else if ($action === ASSIGN_DC) {
                $new_user_info['cron_date_selected_dc']     = $now;
                $new_user_info['cron_status']               = ASSIGN_DC;
            }

            // Save the new user
            $status = saveNewDC($dc_pid, $new_user_info, 'Automated Cron', 'Automatically set Designated Contact to ' . $new_user);
            if ($status) {
                // Notify the new DC
                $updated_statuses[] = $pid;
                $status = sendEmailNotifications($pid, $user_info[$new_user], $data[$pid][$dc_event_id], $action, $base_url,
                                $email_subject, $email_body, $from_addr);
            }
        }
    }

    return [$orphaned, $updated_statuses];

}

/**
 * This function will send to alert users that they were assigned as a DC on a REDCap project.  If a person was assigned but
 * have been suspended from REDCap, they will also receive the email.
 *
 * @param $pid
 * @param $new_user
 * @param $old_user
 * @param $action
 * @param $base_url
 * @param $subject
 * @param $body
 * @param $from_addr
 * @return bool
 */
function sendEmailNotifications($pid, $new_user, $old_user, $action, $base_url, $subject, $body, $from_addr) {


    // Find out who we are sending email to
    $new_dc_name    = $new_user['contact_firstname'] . ' ' . $new_user['contact_lastname'];
    $new_dc_email   = $new_user['contact_email'];
    $old_dc_name    = (empty($old_user['contact_firstname']) ? "" : $old_user['contact_firstname'] . ' ' . $old_user['contact_lastname']);
    $old_dc_email   = (empty($old_user['contact_email']) ? "" : $old_user['contact_email']);
    $email_address  = $new_dc_email . (empty($old_dc_email) ? '' : '; ' . $old_dc_email);
    $salutation     = 'Hello ' .  $new_dc_name . (empty($old_dc_name) ? ',' : ' and ' . $old_dc_name . ',');

    // Find out project details
    $sql = 'select app_title from redcap_projects where project_id = ' . $pid;
    $q = db_query($sql);
    $title = db_fetch_row($q);
    $proj_title = $title[0];

    // Put together the URL to the User Rights page for this project
    $url = $base_url . "?pid=" . $pid;

    // Add project details to the end of the email
    $email_body = $salutation . "<br><br>" . $body . "<br><br>" .
                "<b>Project Details</b>:<br>" .
                "Project ID: " . $pid . "<br>" .
                "Project Title: " . $proj_title . "<br>" .
                "New Designated Contact: " . $new_dc_name . "<br>";
    if (!empty($old_dc_name)) {
        $email_body .= "Old Designated Contact: " . $old_dc_name . "<br>";
    }
    $email_body .= "Project Link: <a href='" . $url . "'>$url</a><br>";

    // Send the email
    $status = REDCap::email($email_address, $from_addr, $subject, $email_body);

    return $status;

}

/**
 * This function determines which of the users (in array $users) have the last log entry in the project.  This person will
 * become the new DC.
 *
 * @param $pid
 * @param $users
 * @return array|false|mixed|null
 */

function findUserWithLastLoggedEvent($pid, $users) {

    // Check to see if there are more than one user who has User Rights
    if (count($users) > 1) {

        // Find the log table where this project's data is stored
        $sql = 'select log_event_table from redcap_projects where project_id = ' . $pid;
        $q = db_query($sql);
        $log_table = db_fetch_row($q);

        // Retrieve the log entries for the users with
        $user_list = implode("','", $users);
        $sql = "select (select user from " . $log_table[0] . " where log_event_id = log.log_event_id) as last_log_user
                        from (
                            select max(rl.log_event_id) as log_event_id
                                from " . $log_table[0] . " rl
                                    join redcap_user_information rui on rui.username = rl.user
                                where rl.project_id = " . $pid . "
                                and rl.user in ('" . $user_list . "')
                                and rl.ts is not null
                        ) as log";
        $q = db_query($sql);
        $new_user = db_fetch_row($q);

    } else {
        $new_user = $users[0];
    }

    return $new_user;
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
function saveNewDC($dc_pid, $project, $log_action, $log_description) {

    $status = true;

    // Save the new user into the designated_contact_selected DB table
    $sql = 'replace into designated_contact_selected
            (project_id, contact_first_name, contact_last_name, contact_email, contact_userid, last_update_date, contact_ui_id)
                select ' . $project['project_id'] . ', "' . $project['contact_firstname'] . '", "' .
        $project['contact_lastname'] . '", "' . $project['contact_email'] . '", "' .
        $project['contact_id'] . '", "' . $project['contact_timestamp'] . '", ' .
        (empty($project['contact_ui_id']) ? '""': $project['contact_ui_id']);
    $q2 = db_query($sql);
    if ($q2) {

        // The database table was updated, now update the REDCap project
        $response = REDCap::saveData($dc_pid, 'json', json_encode(array($project)));
        if (!empty($response['errors'])) {
            $status = false;
        }

        // Make an entry in the REDCap log file that the designated contact was selected
        REDCap::logEvent($log_action, $log_description, null, null, null, $project['project_id']);

    } else {

        // If the database update did not work, send back an error status
        $status = false;
    }

    return $status;
}

