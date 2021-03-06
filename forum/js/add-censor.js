$ = jQuery

$(function () {

   $('#create_censor').on('click', function (e) {
      e.preventDefault();

      $.ajax({
         url: url_object.ajax_url,
         type: 'POST',
         data: {
            action: 'add_censor',
            word: $('#censor_name').val()
         },
         success: function (res) {
            $('#censor_name').val('');
            $('#ajax-response').html("<div id='message' class='updated notice is-dismissible'><p>Censored word was added.</p></div>");
         },
         error: function (error) {
            console.log(error);
            $('#ajax-response').html("<div class='error'>\n" +
                "<p><strong>ERROR</strong>: Can not add this word. Probably, this word is already in list.</p></div>");
         }
      });
   });
});