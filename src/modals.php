
<script type="text/javascript">

$(function() {

    // Find the page we are on
    var current_page = window.location.href;

    // Create a new div and add the html to it
    var newDiv = document.createElement("div");
    newDiv.id = "designatedContact";
    newDiv.innerHTML = '<?php echo $userList; ?>';

    // Find the element that we want to insert the DC box before
    var existingElement;
    if (current_page.includes('UserRights')) {
        existingElement = document.getElementById("user_rights_roles_table_parent");
    } else if (current_page.includes('ProjectSetup')) {
        existingElement = document.getElementById("setupChklist-modify_project");
    }

    // Insert the designated contact box before the User Rights box
    if ((existingElement !== null) && (existingElement.parentNode !== null))
    {
        existingElement.parentNode.insertBefore(newDiv, existingElement);
    }

});


/**
 * Save the new selected Designated Contact and send email if the newly selected
 * person is not me or if the person taken off is not me.
 */
function saveNewContact() {

    var new_contact = document.getElementById("selected_contact").value;
    var current_page = window.location.href;
    var url = document.getElementById("url").value;
    var csrf_token = document.getElementById("redcap_csrf_token").value;

    $.ajax({
        type: "POST",
        url: url,
        data: {"selected_contact": new_contact,
               "redcap_csrf_token" : csrf_token},
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
