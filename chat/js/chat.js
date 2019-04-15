$ = jQuery;

$(function () {

    console.log('HELLO!');

    $('.messages').animate({ scrollTop: $(document).height() }, 'fast');

    setTimeout(function() {
        var new_messages_banner = $(".mes-break")[0];
        new_messages_banner.parentNode.removeChild(new_messages_banner);
        $('.messages').animate({ scrollTop: $(document).height() }, 'fast');
    }, 5000);

    $('.submit').click(function() {
        newMessage();
    });

    $(window).on('keydown', function (e) {
        if (e.which == 13) {
            newMessage();
            return false;
        }
    });

    $('#convOptions').click()

    function newMessage() {
        var messageInput = $(".message-input input");

        message = messageInput.val();

        if($.trim(message) == '') {
            return false;
        }

        var today = new Date();
        var time = today.getHours() + ":" + today.getMinutes() + ":" + today.getSeconds();

        var html_message = '<li class="sent">' +
            '<img src="' + url_object.plugin_directory +'/images/logo.png" alt="" />' +
            '<h6>' + message + '<br/>' +
            '<small class="float-right mt-2">' + time + '</small>' +
            '</h6></li>';

        $(html_message).appendTo($('.messages ul'));

        messageInput.val(null);

        $('.conversation.active .preview').html('<span>You: </span>' + message);

        $('.messages').animate({ scrollTop: $(document).height() }, 'fast');
    }
});