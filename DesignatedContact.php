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
    function redcap_every_page_before_render($project_id=null) {

        $sunet_id = USERID;

        /*
         * This section will place the Designated Contact icon next to the project title on the My Projects page.
         */
        if (PAGE === 'index.php') {

            // Find the designated contact project where the data is stored
            $pmon_pid = $this->getSystemSetting('designated-contact-pid');
            $pmon_event_id = $this->getSystemSetting('designated-contact-event-id');

            // Find the projects that this user is the designated contact and put the icon next to the name
            $projects = contactProjectList($sunet_id, $pmon_pid, $pmon_event_id);
            ?>

            <script type="text/javascript">

                window.onload = function() {

                    // Retrieve the list of projects this user is designated contact and make an array
                    var jsonProjectList = '<?php echo json_encode($projects); ?>';
                    var projectObject = JSON.parse(jsonProjectList.valueOf());
                    var projectList = Object.keys(projectObject);

                    // Find each project and insert the Designated Contact image
                    var nodes = document.querySelectorAll("a.aGrid");
                    nodes.forEach(function(node) {

                        // Find the project ID from the URL
                        var url = node.getAttribute("href");
                        var index = url.indexOf("pid=");
                        var project_id = url.substring(index+4, url.length);

                        // See if this project ID is in our list of Designated Contact projects
                        if (projectList.includes(project_id)) {

                            // Add the icon before the project link
                            var newIcon = document.createElement("span");
                            newIcon.classList.add("fas");
                            newIcon.classList.add("fa-address-book");
                            newIcon.setAttribute("title", "You are the Designated Contact for this project");
                            newIcon.setAttribute("style", "margin-right:7px");
                            node.prepend(newIcon);

                            // Move up the DOM and remove the padding-left 10 px instead of 30px
                            var parent = node.parentNode;
                            if (parent != null) {
                                var grandparent = parent.parentNode;
                                grandparent.setAttribute("style", "padding-left: 10px;");
                            }
                        }

                    });
                };

            </script>

            <?php
        }

        /*
         * If this is the User Rights page (or Project Setup page in some instances),
         * add the Designated Contact block which allows users to change the contact.
         *
         */
        if (PAGE === 'UserRights/index.php' || PAGE === 'ProjectSetup/index.php') {

            // Find the designated contact project where the data is stored
            $pmon_pid = $this->getSystemSetting('designated-contact-pid');
            $pmon_event_id = $this->getSystemSetting('designated-contact-event-id');
            $dc_description = $this->getSystemSetting('dc_description');

            // See if this user has User Rights. If not, just exit
            $users = getUsersWithUserRights();

            if (in_array($sunet_id, $users)) {

                // This is the page that will be called to save the designated contact
                $url = $this->getUrl("src/saveNewContact.php");

                // Retrieve the designated contact in the Project monitoring project
                $contact_fields = array('contact_sunetid', 'contact_firstname', 'contact_lastname', 'contact_timestamp', 'force_update');
                $data = REDCap::getData($pmon_pid, 'array', $project_id, $contact_fields);
                $current_contact = $data[$project_id][$pmon_event_id]['contact_sunetid'];
                $force_update = $data[$project_id][$pmon_event_id]['force_update']['1'];
                $this->emDebug("Force update: " . $force_update);
                if (empty($current_contact)) {
                    $color = "#ffcccc";
                    $current_person = "<b>Designated Contact:</b>  Please setup a Designated Contact ";
                    $contact_timestamp = "";
                } else {
                    if ($force_update) {
                        $color = "cce9ff";
                        $current_person = "<b>Please verify:</b>  " . $data[$project_id][$pmon_event_id]['contact_firstname'] . ' ' . $data[$project_id][$pmon_event_id]['contact_lastname'] . ' as the project Designated Contact!';
                        $contact_timestamp = "";
                    } else {
                        $color = "#ccffcc";
                        $current_person = "<b>Designated Contact:</b>  " . $data[$project_id][$pmon_event_id]['contact_firstname'] . ' ' . $data[$project_id][$pmon_event_id]['contact_lastname'];
                        $contact_timestamp = "(Last updated: " . $data[$project_id][$pmon_event_id]['contact_timestamp'] . ")";
                    }
                }
                $isMe = ($current_contact === $sunet_id);

                // Set the max width based on which page we are on
                if (PAGE === 'ProjectSetup/index.php') {
                    $max_width = 800;
                } else {
                    $max_width = 630;
                }

                // Retrieve the list of other selectable contacts
                $availableContacts = retrieveUserInformation($users);
                $userList = null;

                // This is the Designated Contact modal which is used to change the person designated for that role.
                if ((PAGE === 'ProjectSetup/index.php') && (!empty($current_contact))) {
                    // If there is already a designated contact, don't display anything on the Project Setup page
                } else {

                    // Make a modal so users can change the Designated Contact
                    $userList .= '<div id="contactDiv" style="margin:20px 0;font-weight:normal;padding:10px; border:1px solid #ccc; max-width:' . $max_width . 'px; background-color:' . $color . ';" > ';

                    // This is the information modal which describes what a designated contact is
                    $userList .= '    <div id="infomodal" class="modal" tabindex="-1" role="dialog">';
                    $userList .= '       <div class="modal-dialog modal-sm" role="document">';
                    $userList .= '          <div class="modal-content">';
                    $userList .= '              <div class="modal-header" style="background-color:maroon;color:white">';
                    $userList .= '                  <h6 class="modal-title">What is a Designated Contact?</h6>';
                    $userList .= '                  <button type="button" class="close" data-dismiss="modal" aria-label="Close">';
                    $userList .= '                      <span style="color:white;" aria-hidden="true">&times;</span>';
                    $userList .= '                  </button>';
                    $userList .= '              </div>';
                    $userList .= '              <div class="modal-body"><span>' . $dc_description . '</span></div>';
                    $userList .= '          </div>';
                    $userList .= '       </div>';
                    $userList .= '    </div>';

                    // This is the modal to update the designated contact
                    $userList .= '    <div id="contactmodal" class="modal" tabindex="-1" role="dialog">';
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
                    $userList .= '                  <div style="margin: 10px 0; font-weight:bold;">' . $current_contact_wording . '<span style="font-weight:normal;">' . $current_person . '</span></div>';
                    $userList .= '                  <div style="margin:20px 0 0 0;font-weight:bold;" > ';
                    $userList .= '                      Select a new contact:';
                    $userList .= '                        <select id="selected_contact" name="selected_contact">';

                    // Add users that have User Rights to the selection list.
                    foreach ($availableContacts as $username => $userInfo) {
                        $userList .= '<option value="' . $userInfo['contact_sunetid'] . '">' . $userInfo['contact_firstname'] . ' ' . $userInfo['contact_lastname'] . ' [' . $userInfo['contact_sunetid'] . ']' . '</option>';
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

                        // If there is no currently selected contact, we will make the display bigger (2 lines) so it is prominent
                        $userList .= '<div>';
                        $userList .= '    <div class="col-sm">';
                        $userList .= '        <span style="margin-left:5px;">';
                        $userList .= '            <button type="button" class="fas fa-question-circle" style="margin-right:20px" title="What is a designated contact?" data-toggle="modal" data-target="#infomodal"></button>';
                        $userList .= '        </span>';
                        $userList .= '        <span style="font-weight:bold;color:#000; ">';
                        $userList .= '            <i class="fas fa-exclamation-triangle" style="margin-right:5px;"></i>';
                        $userList .= '        </span>';
                        $userList .= '        <span style="font-weight:normal; color:#000; margin-left:5px;">' . $current_person . '</span>';
                        $userList .= '        <span style="margin-left:20px;">';
                        $userList .= '            <button type="button" class="btn btn-sm btn-primary" style="font-size:12px" data-toggle="modal" data-target="#contactmodal">Click here!</button>';
                        $userList .= '        </span>';
                        $userList .= '    </div>';
                        $userList .= '</div>';

                    } else {

                        // The contact is already selected
                        $userList .= '<div>';
                        $userList .= '    <span style="margin-left:5px;">';
                        $userList .= '        <button type="button" class="fas fa-question-circle" style="margin-right:20px" title="What is a designated contact?" data-toggle="modal" data-target="#infomodal"></button>';
                        $userList .= '    </span>';
                        $userList .= '    <span style="font-weight:bold;color:#000;">';
                        $userList .= '      <i class="fas fa-address-book" style="margin-right:5px;"></i>';
                        $userList .=            $current_contact_wording;
                        $userList .= '    </span>';
                        $userList .= '    <span style="font-weight:normal;color:#000;margin-right:5px;">' . $current_person . '</span>';
                        $userList .= '    <span style="font-weight:normal;font-size:10px;color:#000;margin-right:5px;">' . $contact_timestamp . '</span>';
                        $userList .= '    <span style="margin-left:10px;">';
                        $userList .= '        <button type="button" class="btn btn-sm btn-secondary" style="font-size:12px" href="#" data-toggle="modal" data-target="#contactmodal">Change!</button>';
                        $userList .= '    </span>';
                        $userList .= '</div>';
                    }
                    $userList .= '</div>';
                }
            }

            ?>

            <!-- Fill in the current designated contact and user will have a chance to change -->
            <script type="text/javascript">

            window.setTimeout(function() {

                // Find the page we are on
                var current_page = window.location.href;

                // Create a new div and add the html to it
                var newDiv = document.createElement("div");
                newDiv.innerHTML = '<?php echo $userList; ?>';

                // Insert this new element before the User Roles table
                var existingElement;
                if (current_page.includes('UserRights')) {
                    existingElement = document.getElementById("user_rights_roles_table_parent");
                } else if (current_page.includes('ProjectSetup')) {
                    existingElement = document.getElementById("setupChklist-modify_project");
                }
                existingElement.parentNode.insertBefore(newDiv, existingElement);

            }, 500);

            function saveNewContact() {

                var new_contact = document.getElementById("selected_contact").value;
                var current_page = window.location.href;
                var url = document.getElementById("url").value;

                $.ajax({
                    type: "POST",
                    url: url,
                    data: {"selected_contact": new_contact},
                    success: function(data, textStatus, jqXHR) {
                        $('#contactmodal').modal('hide');
                        window.location.replace(current_page);
                    },
                    error: function(hqXHR, textStatus, errorThrown) {
                        $('#contactmodal').modal('hide');
                        window.location.replace(current_page);
                    }
                });
            }

            </script>

            <?php
        }
    }

}
