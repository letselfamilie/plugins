$ = jQuery;

// First we get the viewport height and we multiple it by 1% to get a value for a vh unit
let vh = window.innerHeight * 0.01;
// Then we set the value in the --vh custom property to the root of the document
document.documentElement.style.setProperty('--vh', `${vh}px`);

// We listen to the resize event
window.addEventListener('resize', () => {
    // We execute the same script as before
    let vh = window.innerHeight * 0.01;
    document.documentElement.style.setProperty('--vh', `${vh}px`);
});


$(function () {
    //simple test
    let conn = new WebSocket('ws://178.128.202.94:8000/?userId='+user_object.id);
    conn.onopen = function(e) {
        console.log("Connection established!");
        console.log(e);


        $('.messages').animate({ scrollTop: $(document).height() }, 'fast');

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
                '<p>' + message + '<br/>' +
                '<small class="float-right mt-2">' + time + '</small>' +
                '</p></li>';

            $(html_message).appendTo($('.messages ul'));

            messageInput.val(null);

            $('.conversation.active .preview').html('<span>You: </span>' + message);

            $('.messages').animate({ scrollTop: $(document).height() }, 'fast');
        }$('.messages').animate({ scrollTop: $(document).height() }, 'fast');

        // setTimeout(function() {
        //     var new_messages_banner = $(".mes-break")[0];
        //     new_messages_banner.parentNode.removeChild(new_messages_banner);
        //     $('.messages').animate({ scrollTop: $(document).height() }, 'fast');
        // }, 5000);

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

            conn.send(JSON.stringify({
                user_id_from:user_object.id,
                command:'message',
                dialog_id: 1,
                message: message
            }));

            var today = new Date();
            var time = today.getHours() + ":" + today.getMinutes() + ":" + today.getSeconds();

            var html_message = '<li class="sent">' +
                '<img src="' + url_object.plugin_directory +'/images/logo.png" alt="" />' +
                '<p>' + message + '<br/>' +
                '<small class="float-right mt-2">' + time + '</small>' +
                '</p></li>';

            $(html_message).appendTo($('.messages ul'));

            messageInput.val(null);

            $('.conversation.active .preview').html('<span>You: </span>' + message);

            $('.messages').animate({ scrollTop: $(document).height() }, 'fast');
        }

    };

    conn.onmessage = function(e) {
        console.log(e.data);
    };


    setTimeout(function() {
        var new_messages_banner = $(".mes-break")[0];
        new_messages_banner.parentNode.removeChild(new_messages_banner);
        $('.messages').animate({ scrollTop: $(document).height() }, 'fast');
    }, 5000);

});