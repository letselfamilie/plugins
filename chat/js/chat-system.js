$ = jQuery;

let fs = require('fs');
let ejs = require('ejs');

let chat_box = ejs.compile(fs.readFileSync("./chat/js/ejs_templates/chat-box.ejs", "utf8"));
let notification = ejs.compile(fs.readFileSync("./chat/js/ejs_templates/notification.ejs", "utf8"));

let conn;

$(function () {
    addChatBox();
    connectSocket();
});

function addChatBox() {
    let $chat_box_node = $(chat_box({}));
    $('body').append($chat_box_node);


    $("#mini-chat-header").click(() => {
        $("#mini-chat").toggleClass("chat-up");
    });

    $(document).on("click", ".close-message", function () {
        $(this).closest(".message-pop").remove();
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
                window.location.href = url_object.site_url + "/chat";

            } else (alert("Write your issue, please"))

            // TODO: check form for being filled in

            return false;
        });

        return false;

    };
}