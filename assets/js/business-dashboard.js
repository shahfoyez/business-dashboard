// for tab
function openTab(evt, tabName) {
    var i, tabcontent, tablinks;
    tabcontent = document.getElementsByClassName("ct-tab-content");
    for (i = 0; i < tabcontent.length; i++) {
        tabcontent[i].style.display = "none";
    }
    tablinks = document.getElementsByClassName("ct-tab-btn");
    for (i = 0; i < tablinks.length; i++) {
        tablinks[i].className = tablinks[i].className.replace(" active", "");
    }
    document.getElementById(tabName).style.display = "block";
    evt.currentTarget.className += " active";
}
function showValidationBar(success, messages) {
    var errorBar = jQuery('#validation-bar');
    errorBar.empty();
    if (!success) {
        errorBar.css('background-color', '#FF6666');
    } else {
        errorBar.css('background-color', '#66FF66');
    }
    for (var i = 0; i < messages.length; i++) {
        errorBar.append('<p>' + messages[i] + '</p>');
    }
    errorBar.show();
    // setTimeout(function () {
    //     errorBar.hide();
    // }, 5000);
}
// Function to handle Ajax
jQuery(document).ready(function ($) {
    // Event listener for radio buttons and pagination
    $(document).on('click', '.foy-ct-remove-btn', function (e) {
        console.log("jkdagfjasgdfukahfguiasgduifggduigbsdiugbisug");
        var confirmation = confirm('Are you sure you want to remove this item?');
        if (confirmation) {
            var loading = true;
            loadingClassHandler(loading);
            // If confirmed, get the necessary data from the row
            var row = $(this).closest('tr');
            var course_id = row.find('.course-id a').attr('data-attr');
            var user_id = row.find('.user-id').attr('data-attr');
            var pdf_type = row.closest('tbody').find('input[name="pdf_type"]').val();
            console.log(course_id);
            console.log(user_id);
            console.log(pdf_type);
            if(course_id && user_id && pdf_type){
                console.log("hello");
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'ct_remove',
                        course_id: course_id,
                        user_id: user_id,
                        pdf_type: pdf_type,
                    },
                    beforeSend: function() {

                    },
                    success: function(response) {
                        console.log(response)
                        console.log(response.success)
                        if(response.success){
                            row.remove();
                        }
                        showValidationBar(response.success, response.messages);
                    },
                    complete: function() {
                        var loading = false;
                        loadingClassHandler(loading);
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        console.error('AJAX Error:', textStatus, errorThrown);
                    }
                });
            }
        }
        function loadingClassHandler(loading) {
            $('#loader').toggle(loading);
            $('.foy-ct-tab-content').toggleClass('foy-loading', loading);
        }
        // Function to show the validation error bar
    });

    $('#new-assignment').submit(function(e){
        e.preventDefault();
        console.log("hello");
        var course_id = $('#form_course_id').val();
        var user_id = $('#form_user_id').val();
        var user_name = $('#form_user_name').val();
        var completion_date = $('#form_completion_date').val();
        var type = $('#form_certificate_type').val();
        if(course_id && user_id){
            submitButton = $('#submit_new');
            updateSubmitButton(submitButton, true);
            // $('#submit_new').prop('disabled', true);
            // $('#submit_new').html('Assigning...');

            console.log(course_id);
            console.log(user_id);
            console.log(type);
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'ct_assign',
                    course_id: course_id,
                    user_id: user_id,
                    user_name: user_name,
                    completion_date: completion_date,
                    pdf_type: type
                },
                success: function(response){
                    console.log(response);
                    updateSubmitButton(submitButton, false);
                    if(response.success){
                        $('#assign-form')[0].reset();
                    }
                    showValidationBar(response.success, response.messages);
                },
                complete: function(){

                },
                error: function (jqXHR, textStatus, errorThrown) {
                    console.error('AJAX Error:', textStatus, errorThrown);
                }
            });
        }else{
            console.log('Please fill in all fields.');
        }
        function updateSubmitButton(submitButton, state){
            submitButton.prop('disabled', state);
            if(state){
                submitButton.html('Assigning...');
            }else{
                submitButton.html('Assign');
            }
        }

    });
});
