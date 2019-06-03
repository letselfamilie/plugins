$ = jQuery;

const {Howl, Howler} = require('howler');
let fs = require('fs');
let ejs = require('ejs');

let chat_box = ejs.compile(fs.readFileSync("./chat/js/ejs_templates/chat-box.ejs", "utf8"));
let notification = ejs.compile(fs.readFileSync("./chat/js/ejs_templates/notification.ejs", "utf8"));

let conn;

let push_sound_prop = 0;

$(function () {
    if (typeof AudioContext != "undefined" || typeof webkitAudioContext != "undefined") {
        var resumeAudio = function () {
            if (typeof g_WebAudioContext == "undefined" || g_WebAudioContext == null) return;
            if (g_WebAudioContext.state == "suspended") g_WebAudioContext.resume();
            document.removeEventListener("click", resumeAudio);
        };
        document.addEventListener("click", resumeAudio);
    }

    if (wp_object.is_post == 0) addChatBox();
    console.log('is chat = ' + wp_object.is_chat + '--------------');

    getPushNotifSoundProp(function (push_sound) {
        push_sound_prop = push_sound;
        connectSocket();
    });
});


function getPushNotifSoundProp(callback) {
    $.ajax({
        url: url_object.ajax_url,
        type: 'POST',
        data: {
            action: 'push_notif_prop'
        },
        success: function (res) {
            res = JSON.parse(res);
            console.log("push_sound_prop: " + res);
            callback(res);
        },
        error: function (error) {
            console.log(error);
            callback(0);
        }
    });
}


function addNotification(title, text, photo, url = null, rounded = true) {
    $('.message-pop-n').remove();

    let $notification_node = $(notification(
        {
            photo: photo,
            title: title,
            text: text
        }));
    $notification_node.find('.message-content-n').on('click', function () {
        if (url != null) window.location.href = url;
    });

    $notification_node.find('.user-icon-n').on('click', function () {
        if (url != null) window.location.href = url;
    });

    if (!rounded) $notification_node.find('.user-icon-n').css('border-radius', '0%');
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
            messageFirst = (messageFirst === null || messageFirst === undefined) ? "" : messageFirst;
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

        if (data.type === "message" && wp_object.is_chat == 0) {

            var sound = new Howl({
                src: ['http://178.128.202.94/wp-content/uploads/2019/04/unconvinced.mp3']
            });

            if(push_sound_prop == 0) {
                console.log("push sound");
                sound.play();
            }

            let from = data.from_username;
            let mess = data.message;
            let dial_id = data.dialog_id;
            let photo = data.photo;

            addNotification(from, mess, photo, url_object.site_url + "/chat?dialog_id=" + dial_id)
        }

        if (data.type === "new_chat") {
            let dialog_id = data.dialog_id;
            let dialog_type = data.dialog_type; //  employee_chat || user_chat
            if (dialog_type === "employee_chat" && data.user_info_1.user_id == user_object.id) {
                window.location.href = url_object.site_url + "/chat?dialog_id=" + dialog_id;
            }

            // var sound = new Howl({
            //     src: ['http://178.128.202.94/wp-content/uploads/2019/04/unconvinced.mp3']
            // });
            // sound.play();
        }

        if (data.type === "new_post") {

            var sound = new Howl({
                src: ['http://178.128.202.94/wp-content/uploads/2019/04/unconvinced.mp3']
            });

            if(push_sound_prop == 0) {
                console.log("push sound");
                sound.play();
            }

            let from = data.from_username;
            let mess = data.post_text;
            let topic_id = data.topic_id;
            let photo = data.photo;

            addNotification(from, mess, photo, url_object.site_url + "/forum?topic_id=" + topic_id)
        }

        if (data.type === "bad_word") {
            var sound = new Howl({
                src: ['http://178.128.202.94/wp-content/uploads/2019/05/warning.wav']
            });

            if(push_sound_prop == 0) {
                console.log("push sound");
                sound.play();
            }

            let from = data.user_login_from;
            let mess = data.message_text;
            let dial_id = data.dialog_id;
            let photo = wp_object.plugin_directory + '/images/bad_word.svg';

            addNotification("BAD WORD", from + ': ' + mess, photo, url_object.site_url + '/wp-admin/admin.php?page=sn_report', false)
        }


        if (data.type === "report") {
            var sound = new Howl({
                src: ['http://178.128.202.94/wp-content/uploads/2019/05/warning.wav']
            });

            if(push_sound_prop == 0) {
                console.log("push sound");
                sound.play();
            }

            let from = data.user_login_from;
            let mess = data.message_text;
            let dial_id = data.dialog_id;
            let photo = wp_object.plugin_directory + '/images/complain.svg';

            addNotification("REPORT", from + ': ' + mess, photo, url_object.site_url + '/wp-admin/admin.php?page=sn_report', false)
        }
    };
}