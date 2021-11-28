<?php
namespace Stanford\DesignatedContact;
/** @var \Stanford\DesignatedContact\DesignatedContact $module **/

use REDCap;

/**
 * This function retrieves the list of users who have User Rights privileges who are not suspended for this project.
 *
 * @return array - users who have User Rights for this project
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
 * Given an array of userids, this function will return the user name and contact info
 * for each user.
 *
 * @param $users - array of userIds
 * @return array - user information associated with the userId
 */
function retrieveUserInformation($users) {

    $contact = array();

    // Retrieve the rest of the data for this contact
    $sql = "select user_email, user_phone, user_firstname, user_lastname, username " .
        "    from redcap_user_information " .
        "    where username in ('" . implode("','", $users) . "')";
    $q = db_query($sql);
    while ($current_db_row = db_fetch_assoc($q)) {
        $user = $current_db_row['username'];
        $contact[$user]['contact_id'] = $user;
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

function noContactSelectedList($user, $pmon_pid) {

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
 * Update the designated_contact_selected database table in redcap so it always up-to-date and we can query
 * against it instead of having to sift through the REDCap projects table.
 *
 * @param $new_contact
 */
function update_dc_table($new_contact) {

    // Update the designated contact so it is always up-to-date
    $db_query = "replace into designated_contact_selected
    (project_id, contact_first_name, contact_last_name, contact_email, contact_userid, last_update_date)
    values
    ('" . $new_contact['project_id'] . "', '" . $new_contact['contact_firstname'] . "', '" . $new_contact['contact_lastname'] .
        "', '" . $new_contact['contact_email'] . "', '" . $new_contact['contact_id'] . "', now());";

    $q = db_query($db_query);

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
 * This function will join the table of projects with no non-suspended users with the log tables to see
 * which of these projects haven't had activity in the log event table for over 18 months.
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
 * If the update is successful, we put an entry in the log table and update the redcap project which tracks
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
        $status = $response;
    } else {
        $status = false;
    }

    return $status;
}


/**
 * When new projects are created and do not select a Designated Contact, set the DC to the person who created the
 * project. To set a DC, update the designated_contact_selected table in the DB, enter the DC in the REDCap
 * project and put a log message in the Log to say this was done by the automated cron. This cron runs nightly.
 */
function newProjectSetDC($dc_pid) {

    $now = date('Y-m-d H:i:s');
    $proj_ids = array();

    // Retrieve the user id of the person who created the project and set them as the designated contact
    $sql =
        'select rp.project_id, rui.user_firstname, rui.user_lastname, rui.user_email, rui.username, rui.ui_id
        from redcap_projects rp
            join redcap_user_information rui on rp.created_by = rui.ui_id
        where rp.creation_time between DATE_SUB(now(), INTERVAL 3 DAY) and now()
        and rp.project_id not in (select project_id from designated_contact_selected)';
    $q = db_query($sql);
    while ($proj_and_creators = db_fetch_assoc($q)) {

        // Retrieve the id of the creator of the project
        $list_of_projects['project_id']             = $proj_and_creators['project_id'];
        $list_of_projects['contact_firstname']      = $proj_and_creators['user_firstname'];
        $list_of_projects['contact_lastname']       = $proj_and_creators['user_lastname'];
        $list_of_projects['contact_email']          = $proj_and_creators['user_email'];
        $list_of_projects['contact_id']             = $proj_and_creators['username'];
        $ui_id                                      = $proj_and_creators['ui_id'];
        $list_of_projects['contact_timestamp']      = $now;
        $list_of_projects['designated_contact_complete'] = 2;
        $list_of_projects['cron_status']            = 'Auto-selected';
        $list_of_projects['cron_date_selected_dc']  = $now;
        $list_of_projects['cron_updates_complete']  = 2;

        // Save the id into the designated_contact_selected DB table
        $sql = 'replace into designated_contact_selected
            (project_id, contact_first_name, contact_last_name, contact_email, contact_userid, last_update_date, contact_ui_id)
                select ' . $list_of_projects['project_id'] . ', "' . $list_of_projects['contact_firstname'] . '", "' .
            $list_of_projects['contact_lastname'] . '", "' . $list_of_projects['contact_email'] . '", "' .
            $list_of_projects['contact_id'] . '", "' . $list_of_projects['contact_timestamp'] . '", ' .
            $ui_id;
        $q2 = db_query($sql);
        if ($q2) {

            // The database table was updated, now update the REDCap project
            $response = REDCap::saveData($dc_pid, 'json', json_encode(array($list_of_projects)));

            // Make an entry in the REDCap log file that the designated contact was selected
            REDCap::logEvent('Automated Cron', "Automatically set Designated Contact to " . $list_of_projects['contact_id'], null, null, null, $list_of_projects['project_id']);
        }

        // Create a list of projects that have the DC set by the cron job
        $proj_ids[] = $list_of_projects['project_id'];
    }

    return $proj_ids;
}

/**
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
 * This function will loop over projects whose designated contact is suspended.  If another person with User Rights is
 * found, that person will be assigned the DC.  If there is more than one person with User Rights, the person who
 * has the latest log event will become the new DC.
 *
 * If no other users have User Rights on the project, set the project status as Orphaned in the DC REDCap project.
 *
 * @param $dc_pid
 * @param $pids
 * @return bool
 */
/*
function updateDesignatedContact($dc_pid, $pids) {

    $updated = array();
    $status = true;
    $now = date("Y-m-d H:i:s");

    // Retrieve list of orphaned projects so we don't keep updating with a new date
    $filter = "[cron_date_orphaned_project] = ''";
    $orphaned_projects = getProjectData($dc_pid, $filter, array('project_id'));

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
            }

        } else{

            // Re-assign DC to a different user
            $latest_user = findUserWithLastLoggedEvent($pid, $users);
            return $latest_user;

            // Save the new status
            $updated[$pid]['project_id']                    = $pid;
            $updated[$pid]['cron_status']                   = 'Re-selected';
            $updated[$pid]['cron_date_reselected_dc']       = $now;
            $updated[$pid]['cron_updates_complete']         = 2;

            // Make a log entry so users can tell what happened
            REDCap::logEvent('Automated Cron', 'Automatically set Designated Contact to ' . 'me', null, null, null, $pid);

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
*/

/*
function findUserWithLastLoggedEvent($pid, $users) {

    $user_list = implode(',', $users);
    $sql = 'select log_event_table from redcap_projects where project_id = ' . $pid;
    $q = db_query($sql);
    $log_table = db_fetch_row($q);

    return $log_table;
}
*/

/*
function getProjectData($pid, $filter, $fields) {

    $data = REDCap::getData($pid, 'array', null, $fields, null, null, null, null, null, $filter);
    return $data;

}
*/
