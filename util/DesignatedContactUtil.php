<?php
namespace Stanford\DesignatedContact;
/** @var \Stanford\DesignatedContact\DesignatedContact $module **/

use REDCap;

/**
 * NOTE: This must be called in Project Context since REDCap functions need to know what project it is in.
 * This function retrieves the list of users who have User Rights privileges for this project.
 *
 * @return array - users who have User Rights for this project
 */
function getUsersWithUserRights() {

    $userRightsUsers = array();
    $allUsers = REDCap::getUsers();
    foreach($allUsers as $cnt => $user) {
        $rights = REDCap::getUserRights($user);
        if ($rights[$user]["user_rights"] == 1) {
            $userRightsUsers[] = $user;
        }
    }

    return $userRightsUsers;

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
        "    where username in ('" . implode("','",$users) . "')";
    $q = db_query($sql);
    while ($current_db_row = db_fetch_assoc($q)) {
        $user = $current_db_row['username'];
        $contact[$user]['contact_id']   = $user;
        $contact[$user]["contact_firstname"] = $current_db_row["user_firstname"];
        $contact[$user]["contact_lastname"]  = $current_db_row["user_lastname"];
        $contact[$user]["contact_email"]     = $current_db_row["user_email"];
        $contact[$user]["contact_phone"]     = $current_db_row["user_phone"];
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
 * not have a Designated Contact selected.
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
                    and rur.project_id not in (select rd.record
                                                    from redcap_data rd
                                                        join redcap_projects rp on rd.project_id = rp.project_id
                                                    where rd.field_name = 'contact_id'
                                                    and rd.project_id = 22052
                                                )
                    and rur.username='" . $user . "'";


    $records = array();
    $q = db_query($db_query);
    while($proj_id = db_fetch_assoc($q)) {
        $records[] = $proj_id['project_id'];
    }

    return $records;
}
