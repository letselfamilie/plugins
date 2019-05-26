$ = jQuery;

const {Howl, Howler} = require('howler');
let fs = require('fs');
let ejs = require('ejs');

let chat_box = ejs.compile(fs.readFileSync("./chat/js/ejs_templates/chat-box.ejs", "utf8"));
let notification = ejs.compile(fs.readFileSync("./chat/js/ejs_templates/notification.ejs", "utf8"));

let conn;

$(function () {
    if (!url_object.is_post) addChatBox();
    connectSocket();
});

function addNotification(title, text, photo) {
    $('.message-pop-n').remove();


    let $notification_node = $(notification(
        {
            photo: photo,
            title: title,
            text: text
        }));
    $('body').append($notification_node);


    $(document).on("click", ".close-message-n", function () {
        $notification_node.remove();
    });
    setTimeout(function () {
        $notification_node.remove();
    }, 7000);
}

function addChatBox() {
    let $chat_box_node = $(chat_box({}));
    $('body').append($chat_box_node);
    console.log('added box chat');

    $("#mini-chat-header").click(() => {
        $("#mini-chat").toggleClass("chat-up");
    });
}

function connectSocket() {
    let is_consultant = (user_object.role == 'adviser');
    let url = 'ws://178.128.202.94:8000/?userId=' + user_object.id + '&consultan=' + ((is_consultant) ? 1 : 0);
    conn = new WebSocket(url);

    conn.onopen = function (e) {
        console.log("Connection established.");
        console.log(e);

        var keys = Object.keys(user_object);
        console.log("user_object" + keys);


        $('#addNewDialog').click(function () {
            let topic = $("#inputTopic").val();
            let messageFirst = $("#inputFirstMessage").val();
            messageFirst= (messageFirst===null || messageFirst===undefined)? "" : messageFirst;
            if (topic !== "") {
                // socket add dialog
                conn.send(JSON.stringify({
                    user_id_from: user_object.id,
                    command: 'new_chat',
                    dialog_type: 'employee_chat',
                    topic: topic,
                    message: messageFirst
                }));

                console.log("Request of creating new dialog has been sent to server");

            } else (alert("Write your issue, please"))

            // TODO: check form for being filled in

            return false;
        });

        return false;

    };



    conn.onmessage = function (e) {
        console.log(e.data);
        var data = JSON.parse(e.data);

        if (data.type === "message") {

            var sound = new Howl({
                src: ['http://178.128.202.94/wp-content/uploads/2019/04/unconvinced.mp3']
            });
            sound.play();

            let from = data.from_username;
            let mess = data.message;
            let dial_id = data.dialog_id;
            let photo = data.photo;

            addNotification(from, mess, photo)
        }

        if (data.type === "new_chat") {
            let dialog_id = data.dialog_id;
            let dialog_type = data.dialog_type; //  employee_chat || user_chat
            if(dialog_type==="employee_chat")
            {
                window.location.href = url_object.site_url + "/chat?dialog_id=" + dialog_id;
            }

            // var sound = new Howl({
            //     src: ['http://178.128.202.94/wp-content/uploads/2019/04/unconvinced.mp3']
            // });
            // sound.play();
        }

        if (data.type === "bad_word") {
            var sound = new Howl({
                src: ['http://178.128.202.94/wp-content/uploads/2019/05/warning.wav']
            });
            sound.play();

            let from = data.user_login_from;
            let mess = data.message_text;
            let dial_id = data.dialog_id;
            let photo = url_object.plugin_directory + '/images/bad_word.swg';

            addNotification("BAD WORD", from + ': ' + mess, photo)
        }
    };
}