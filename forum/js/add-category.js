$ = jQuery

$(function () {

   $('#create_category').on('click', function (e) {
      e.preventDefault();

      $.ajax({
         url: url_object.ajax_url,
         type: 'POST',
         data: {
            action: 'add_category',
            cat_name: $('#category_name').val()
         },
         success: function (res) {
            $('#category_name').val('');
            $('#ajax-response').html("<div id='message' class='updated notice is-dismissible'><p>Category added.</p></div>");
         },
         error: function (error) {
            console.log(error);
            $('#ajax-response').html("<div class='error'>\n" +
                "<p><strong>ERROR</strong>: Can not add this category. Probably, this category is already exists.</p></div>");
         }
      });
   });
});