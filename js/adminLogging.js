// Creates a dropdown list for contacts
$(document).ready(function() {
    $('.contact').select2();
})

// Input buttons remain orange upon active
$('input[type=button]').on("click",function(){  
    $('input[type=button]').not(this).removeClass();
    $(this).toggleClass('active');
});