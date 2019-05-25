$ = jQuery;

let fs = require('fs');
let ejs = require('ejs');

let chat_box = ejs.compile(fs.readFileSync("./chat/js/ejs_templates/chat-box.ejs", "utf8"));
let notification = ejs.compile(fs.readFileSync("./chat/js/ejs_templates/notification.ejs", "utf8"));

let conn;

$(function () {
    if (!url_object.is_post) addChatBox();
    connectSocket();
    addNotification();
});

function addNotification() {
    let $notification_node = $(notification({photo:user_object.photo}));
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

        // if (data.type === "message") {
        //
        //     var sound = new Howl({
        //         src: ['http://178.128.202.94/wp-content/uploads/2019/04/unconvinced.mp3']
        //     });
        //     sound.play();
        //
        //     let from = data.from;
        //     let time = data.time;
        //     let mess = data.message;
        //     let dial_id = data.dialog_id;
        //     let is_chat_with_employee = data.is_employee_chat;
        //
        //     let key = searchObjKey(mes, dial_id);
        //
        //     $("#" + dial_id + " p.preview").text(mess);
        //     let $node = $("#" + dial_id);
        //     $node.detach();
        //     $node.prependTo("#conversations ul");
        //
        //     let isRead = "0";
        //
        //     if ($node.hasClass("active")) {
        //
        //         //adding message in the open chat
        //         var m = {user_from_id: from, message_body: mess, create_timestamp: time};
        //         addMes(m, $('.conversation.active').find("img").attr('src'), is_chat_with_employee);
        //
        //         isRead = "1";
        //
        //         conn.send(JSON.stringify({
        //             command: 'mark_messages',
        //             dialog_id: dial_id
        //         }));
        //
        //
        //         $('.messages ul').children('li').last().focus();
        //
        //     } else {
        //
        //         if ($node.find(".badge-counter").length === 0) {
        //             let badge = '<span class="badge badge-counter ml-2">1</span>';
        //             $(badge).appendTo($node.find(".wrap .meta .name"));
        //         } else {
        //             let val = $node.find(".badge-counter").text();
        //
        //             $node.find(".badge-counter").text(isNaN(parseInt(val))? 1 : parseInt(val) + 1);
        //         }
        //
        //         $node.find(".badge-counter").removeClass("hidden");
        //
        //         // TODO: add badges of new messages + counter to the conversation
        //
        //         console.log("Dialog " + dial_id + " has new message");
        //     }
        //
        //
        //     var new_message = {
        //         message_id: "" + mes[Object.keys(mes).length - 1].message_id + 1,
        //         user_from_id: from,
        //         dialog_id: dial_id,
        //         is_read: isRead,
        //         message_body: mess,
        //         create_timestamp: time
        //     };
        //
        //     mes[key].messages.push(new_message);
        //
        //     gotoBottom('messages-container');
        // }

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
    };
}