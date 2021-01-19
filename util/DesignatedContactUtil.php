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
 * Given an array of sunetids, this function will return the user name and contact info
 * for each sunetid.
 *
 * @param $sunetids - array of sunetIds
 * @return array - user information associated with the sunetIds
 */
function retrieveUserInformation($sunetids) {

    $contact = array();

    // Retrieve the rest of the data for this contact
    $sql = "select user_email, user_phone, user_firstname, user_lastname, username " .
        "    from redcap_user_information " .
        "    where username in ('" . implode("','",$sunetids) . "')";
    $q = db_query($sql);
    while ($current_db_row = db_fetch_assoc($q)) {
        $sunetid = $current_db_row['username'];
        $contact[$sunetid]['contact_sunetid']   = $sunetid;
        $contact[$sunetid]["contact_firstname"] = $current_db_row["user_firstname"];
        $contact[$sunetid]["contact_lastname"]  = $current_db_row["user_lastname"];
        $contact[$sunetid]["contact_email"]     = $current_db_row["user_email"];
        $contact[$sunetid]["contact_phone"]     = $current_db_row["user_phone"];
    }

    return $contact;
}

/**
 * This function will retrieve a list of projects that this person is the Designated Contact for.  This list
 * is used to place on icon next to the project name on the 'My Projects' page.
 *
 * @param $sunetid - sunet ID of current user
 * @param $pmon_pid - project id of Designated Contact project where data is stored
 * @param $pmon_event_id - event id of Designated Contact project where data is stored
 * @return array - Projects that this user is the Designated Contact
 */

function contactProjectList($sunetid, $pmon_pid, $pmon_event_id) {

    $filter = '[contact_sunetid] = "' . $sunetid . '"';
    $data = REDCap::getData($pmon_pid, 'array', null, array('project_id'), $pmon_event_id, null, null, null, null, $filter);
    $records = array();
    foreach ($data as $record_id => $record_info) {
        $records[$record_id] = $record_id;
    }

    return $records;
}

