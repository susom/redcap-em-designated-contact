<?php

namespace Stanford\DesignatedContact;

class GetModal
{
    use emLoggerTrait;

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
        $userList .= '          <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">';
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
    function getDCModal($availableContacts, $current_contact, $user, $current_person, $contact_timestamp, $button_text, $url, $token) {

        $isMe = ($current_contact == $user);

        // This is the modal to update the designated contact
        $userList  = '    <div id="contactmodal" class="modal" tabindex="-1" role="dialog">';
        $userList .= '       <div class="modal-dialog" role="document">';
        $userList .= '          <div class="modal-content">';
        $userList .= '              <div class="modal-header" style="background-color:maroon;color:white">';
        $userList .= '                  <h5 class="modal-title">Choose a new Designated Contact</h5>';
        $userList .= '                  <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">';
        $userList .= '                      <span style="color:white;" aria-hidden="true">&times;</span>';
        $userList .= '                  </button>';
        $userList .= '                  <input hidden id="redcap_csrf_token" value="' . $token . '" />';
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
        $userList .= '                        <input type="button" data-bs-dismiss="modal" value="Close">';
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
            $userList .= '        <button type="button" class="fas fa-question-circle" style="margin-right:10px" title="What is a designated contact?" data-bs-toggle="modal" data-bs-target="#infomodal"></button>';
            $userList .= '    </span>';
            $userList .= '    <span style="font-weight:bold;color:#000; ">';
            $userList .= '        <i class="fas fa-exclamation-triangle" style="margin-right:5px;"></i>';
            $userList .= '    </span>';
            $userList .= '    <span style="margin:5px;">' . $current_person . '</span>';
            $userList .= '    <button type="button" class="btn btn-sm btn-primary" style="font-size:12px" data-bs-toggle="modal" data-bs-target="#contactmodal">' . $button_text . '</button>';
            $userList .= '</div>';

        } else {

            // The contact is already selected
            $userList .= '<div>';
            $userList .= '    <span style="margin-left:5px;">';
            $userList .= '        <button type="button" class="fas fa-question-circle" style="margin-right:10px" title="What is a designated contact?" data-bs-toggle="modal" data-bs-target="#infomodal"></button>';
            $userList .= '    </span>';
            $userList .= '    <span style="font-weight:bold;color:#000;">';
            $userList .= '        <i class="fas fa-address-book" style="margin-right:5px;"></i>';
            $userList .= '    </span>';
            $userList .= '    <span style="margin-right:5px;">' . $current_person . '</span>';
            $userList .= '    <span style="font-size:10px;margin-right:5px;">' . $contact_timestamp . '</span>';
            $userList .= '    <button type="button" class="btn btn-sm btn-secondary" style="font-size:12px" data-bs-toggle="modal" data-bs-target="#contactmodal">' . $button_text . '</button>';
            $userList .= '</div>';
        }

        return $userList;
    }

}
