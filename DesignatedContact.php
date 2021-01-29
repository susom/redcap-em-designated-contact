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
            $users = getUsersWithUserRights();
            if (in_array($user, $users)) {

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
            $userList .= '<option value="' . $userInfo['contact_id'] . '">' . $userInfo['contact_firstname'] . ' ' . $userInfo['contact_lastname'] . ' [' . $userInfo['contact_id'] . ']' . '</option>';
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

}
