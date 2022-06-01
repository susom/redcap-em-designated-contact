<select name="contact[]" multiple="multiple" id="contact">
    <option value="" disabled selected>--- Choose a contact ---</option>
    <option value="<?php echo $dc_email?>">Designated Contact</option>
    <?php
        foreach($users as $user) {
    ?>
    <option value="<?php echo $user . '@stanford.edu'; ?>"><?php echo $user; ?></option>
    <?php } ?>
    <!-- <option value="test@stanford.edu">test@stanford.edu</option>
    <option value="test2@stanford.edu">test2@stanford.edu</option>
    <option value="all">Send to All</option> -->
</select>

<script>
    $(document).ready(function() {       
        $('#contact').multiselect({		
            nonSelectedText: 'Select Current'				
        });
    });

    $(function () {
        $('#contact').multiselect({ 
            buttonText: function(options, select) {
                var labels = [];
                console.log(options);
                options.each(function() {
                labels.push($(this).val());
                });
            $("#current_select_values").val(labels.join(',') + '');
            return labels.join(', ') + '';
            //}
            }
        });
    });
</script>

<fieldset name="contact" id="contact">
    <legend>Choose a contact</legend>
    <?php
        foreach($users as $user) {
    ?>
    <input type="checkbox" name="contact[]" value="<?php echo $user . '@stanford.edu'; ?>">
    <label><?php echo $user; ?></label>
    <?php } ?>
    <input type="checkbox" name="contact[]" value="wcao226@stanford.edu">
    <label>wcao</label>
</fieldset>

// if (!empty($_POST['contact'])) {
//     $email_msg = $_POST['emailmsg'];
//     $email_subject = "Update notification about your project (pid: ".  $pid . ")";

//     foreach ($_POST['contact'] as $contact) {
//         REDCap::logEvent('ADMIN email sent', "Emailing to $contact :  $email_msg");
//         sendEmail($email_subject, $contact, $email_msg);
//     }
// }