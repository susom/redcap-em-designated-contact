// previous
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

// current
<select class="contact" name="contact[]" multiple="multiple" id="contact">
        <option value="<?php echo $dc_email?>">Designated Contact (<?php echo $dc_email; ?>)</option>
        <?php
            foreach($users as $user) {
                $user_email = $module->getUser($user)->getEmail();
        ?>
        <option value="<?php echo $user_email; ?>"><?php echo $user . ' (' . $user_email . ')' ; ?></option>
        <?php } ?>
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




// if (!empty($dc_email) && !empty($email_msg)) {

// if (empty($dc_email)) {
//     if (!empty($_POST['contact'])){
//         $dc_email = $_POST['contact'];
//     }
// }

// if (!empty($_POST['contact'])){
//     $dc_email = '';
// }

// Select2
<select class="contact" name="contact[]" multiple="multiple" id="contact">
        <option value="<?php echo $dc_email?>">Designated Contact</option>
        <?php
            foreach($users as $user) {
        ?>
        <option value="<?php echo $user . '@stanford.edu'; ?>"><?php echo $user; ?></option>
        <?php } ?>
</select>

// Multiselect
<select name="contact[]" multiple="multiple" id="contact">
    <option value="<?php echo $dc_email?>">Designated Contact</option>
    <?php
        foreach($users as $user) {
    ?>
    <option value="<?php echo $user . '@stanford.edu'; ?>"><?php echo $user; ?></option>
    <?php } ?>
    <!-- <option value="all">Send to All</option> -->
</select>


<!-- <input type="button" id="email_template" name="email_template" value="Test" onclick="document.getElementById('emailmsg').innerHTML = '<?php echo $test?>'"> -->
<?php
    $count_template     =           count($et_template);
    $counter            =           0;
    while ($counter < $count_template) {
?>
    <input type="button" id="email_template" name="email_template" value="<?php echo $values[$counter]['email-template-title'];?>" onclick="document.getElementById('emailmsg').innerHTML = '<?php echo $values[$counter]['email-template-body'];?>'">
<?php 
        $counter = $counter + 1;
    } 
?>


<!-- <input type="button" id="email_template" name="email_template" value="" onclick="document.getElementById('emailmsg').innerHTML = '<?php echo implode('',$user_array);?>'"> -->

<!-- <select name="Templates" id="Templates">
    <option value="" disabled selected>--- Choose a Template ---</option>
    <?php
        // $counter            =           0;
        // $current_body       =           [$et_body[$counter]);
        // $current_name       =           [$et_name[$counter]);
        foreach($values as $test) {
    ?>
    <option value="<?php echo $test; ?>"><?php echo JSON_encode($test); ?></option>
    <!-- <option value="<?php echo $test; ?>"><?php echo JSON_encode($test['Testing']); ?></option> -->
    <?php } ?>
    
</select> -->