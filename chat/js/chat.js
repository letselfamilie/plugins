$ = jQuery;
let fs = require('fs');
let ejs = require('ejs');

const {Howl, Howler} = require('howler');

// First we get the viewport height and we multiple it by 1% to get a value for a vh unit
let vh = window.innerHeight * 0.01;
// Then we set the value in the --vh custom property to the root of the document
document.documentElement.style.setProperty('--vh', `${vh}px`);
let default_photo = "http://178.128.202.94/wp-content/plugins/ultimate-member/assets/img/default_avatar.jpg"
let myprofilelogo = url_object.plugin_directory + '/images/user.png';
let dialog_templ = ejs.compile(fs.readFileSync("./chat/js/ejs_templates/dialog.ejs", "utf8"));
let mes_templ = ejs.compile(fs.readFileSync("./chat/js/ejs_templates/message.ejs", "utf8"));
let conn;

// We listen to the resize event
window.addEventListener('resize', () => {
    // We execute the same script as before
    let vh = window.innerHeight * 0.01;
    document.documentElement.style.setProperty('--vh', `${vh}px`);
});

//http://178.128.202.94/wp-content/uploads/2019/04/unconvinced.mp3
$(function () {
    $.ajax({
        url: url_object.ajax_url,
        type: 'POST',
        data: {
            action: 'get_dialogs',
            user_id: user_object.id, // example
            //other parameters
        },
        success: function (res) {
            console.log("Res: " + res);
            loadChat(JSON.parse(res));
        },
        error: function (error) {
            console.log(error);
        }
    });
});

function loadChat(mes) {
    let is_consultant = (user_object.role == 'adviser');
    let url = 'ws://178.128.202.94:8000/?userId=' + user_object.id + '&consultan=' + ((is_consultant) ? 1 : 0);
    conn = new WebSocket(url);


    conn.onopen = function (e) {
        console.log("Connection established.");
        console.log(e);

        fillChat(mes);

        $('.messages').animate({scrollTop: $(document).height()}, 'fast');

        $('.submit').click(function () {
            newMessage();
        });

        $(window).on('keydown', function (e) {
            if (e.which == 13) {
                newMessage();
                return false;
            }
        });

        $('#search').keyup(function () {

            $('#inputSearch').focus();
            var input = $('#inputSearch').val().trim();

            if (input !== "") {
                console.log("Search " + input);

                $("li.conversation").filter(function () {
                    return $(this).find(".name").text().indexOf(input) >= 0;
                }).addClass("not_to_hide").removeClass("hidden");

                $("li.conversation").filter(function () {
                    return $(this).find(".name").text().indexOf(input) < 0;
                }).removeClass("not_to_hide");

                $('li.conversation:not(.not_to_hide)').addClass("hidden");


            } else $('li.conversation').removeClass("hidden not_to_hide");
        });

        function newMessage() {
            var messageInput = $(".message-input input");

            message = messageInput.val();

            if ($.trim(message) == '') {
                return false;
            }

            var d_id = parseInt($('.conversation.active').attr("id"));

            conn.send(JSON.stringify({
                user_id_from: user_object.id,
                command: 'message',
                dialog_id: d_id,
                message: message
            }));

            var today = new Date();
            var day = today.getDate();
            var month = today.getMonth() + 1;

            day = (day < 10) ? "0" + day : "" + day;
            month = (month < 10) ? "0" + month : "" + month;

            var time = today.getFullYear() + "-" + day + "-" + month + " " + today.getHours() + ":" + today.getMinutes() + ":" + today.getSeconds();

            var m = {user_from_id: user_object.id, message_body: message, create_timestamp: time};

            addMes(m, myprofilelogo, "0");

            let key = parseInt(searchObjKey(mes, d_id));

            var new_message = {
                message_id: "" + mes[Object.keys(mes).length - 1].message_id + 1,
                user_from_id: "" + user_object.id,
                dialog_id: "" + d_id, is_read: "0",
                message_body: "" + message,
                create_timestamp: time
            };


            mes[key].messages.push(new_message);

            messageInput.val(null);

            $('.conversation.active .preview').html('<span>You: </span>' + message);

            //$('.messages').animate({ scrollTop: $('.messages ul').children('li').last().position().top }, 'fast');

            $('.messages ul').children('li').last().focus();

            gotoBottom('messages-container');

        }


        $("#resolve-btn").click(function () {
            var badge = '<span class="badge badge-resolved ml-2">Resolved</span>';
            $(badge).appendTo($("#chat-title"));

            badge = '<span class="badge badge-resolved ml-2">R</span>';
            $(badge).appendTo($(".conversation.active .wrap .meta .name"));

            newBanner("This problem has been resolved");

            // TODO: deprive of the possibility to send messages in a resolved dialog
            // TODO: add this unfo to server

        });

        $("#btn-newmessage").click(function () {

            $(".conversation.active").removeClass("active");

            $(".contact-profile").css('display', 'none');
            $(".messages").css('display', 'none');
            $(".message-input").css('display', 'none');

            $(".new-convo").css('display', 'block');
            // TODO: add new dialog to server

        });

        $("#addNewDialog").click(function ()
        {
            let topic = $("#inputTopic").val();
            let messageFirst = $("#inputFirstMessage").val();
            messageFirst= (messageFirst===null || messageFirst===undefined)? "" : messageFirst;
            if (topic !== "") {
                console.log(topic);
                console.log(messageFirst);


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


        });

    };


    conn.onmessage = function (e) {
        console.log(e.data);
        var data = JSON.parse(e.data)
        console.log("e.data.type " + data.type);

        if (data.type === "message") {

            var sound = new Howl({
                src: ['http://178.128.202.94/wp-content/uploads/2019/04/unconvinced.mp3']
            });
            sound.play();

            let from = data.from;
            let time = data.time;
            let mess = data.message;
            let dial_id = data.dialog_id;
            let is_chat_with_employee = data.is_employee_chat;

            let key = searchObjKey(mes, dial_id);

            $("#" + dial_id + " p.preview").text(mess);
            let $node = $("#" + dial_id);
            $node.detach();
            $node.prependTo("#conversations ul");

            let isRead = "0";

            if ($node.hasClass("active")) {

                //adding message in the open chat
                var m = {user_from_id: from, message_body: mess, create_timestamp: time};
                addMes(m, $('.conversation.active').find("img").attr('src'), is_chat_with_employee);

                isRead = "1";

                conn.send(JSON.stringify({
                    command: 'mark_messages',
                    dialog_id: dial_id
                }));

                
                $('.messages ul').children('li').last().focus();

            } else {

                if ($node.find(".badge-counter").length === 0) {
                    let badge = '<span class="badge badge-counter ml-2">1</span>';
                    $(badge).appendTo($node.find(".wrap .meta .name"));
                } else {
                    let val = $node.find(".badge-counter").text();
                    $node.find(".badge-counter").text(parseInt(val) + 1);
                }

                $node.find(".badge-counter").removeClass("hidden");

                // TODO: add badges of new messages + counter to the conversation

                console.log("Dialog " + dial_id + " has new message");
            }


            var new_message = {
                message_id: "" + mes[Object.keys(mes).length - 1].message_id + 1,
                user_from_id: from,
                dialog_id: dial_id,
                is_read: isRead,
                message_body: mess,
                create_timestamp: time
            };

            mes[key].messages.push(new_message);

            gotoBottom('messages-container');
        }

        if (data.type === "new_chat") {
            console.log(data.message);

            /*{
                "message":"New chat with employee 3 was added",
                "topic":"blsbla",
                "is_emp_available":false,
                "type":"new_chat",
                "second_user":"3",
                "dialog_id":"14",
                "dialog_type":"employee_chat",
                "first_message":
                {
                    'message' => $first_message,
                    'time' => $time,
                    'from'=> $user_id_from
                },
                "user_info_1": {
                "user_id":"1",
                    "user_login":"Letsel",
                    "user_photo":null
            },
                "user_info_2": {
                "user_id":"3",
                    "user_login":"blsbla",
                    "user_photo":null
            }
            }*/
            let dialog_id = data.dialog_id;

            if($('#'+dialog_id).length!==0) return;
            console.log($('#'+dialog_id).length);

            let message = data.message;
            let first_user_id = data.user_info_1.user_id;
            let second_user_id = data.user_info_2.user_id; //id second user
            let first_user_name = data.user_info_1.user_login;
            let first_user_photo = data.user_info_1.user_photo;
            let second_user_name = data.user_info_2.user_login;
            let second_user_photo = data.user_info_2.user_photo;
            let dialog_type = data.dialog_type; //  employee_chat || user_chat
            let topic = data.topic;  // absent for user
            let is_emp_available =  data.is_emp_available; //absent for user
            let first_message = data.first_message;



            let isread = (second_user_id!==user_object.id)?"1":"0";

            var m = (first_message===null || first_message===undefined || first_message.message==="")? [] :
                [{
                    message_id: "1",
                    dialog_id: ""+ dialog_id,
                    is_read: isread,
                    user_from_id: first_message.from,
                    message_body: first_message.message,
                    create_timestamp: first_message.time
                }];

            if(dialog_type==="employee_chat")
            {

                var newDialog = {
                    dialog_id: dialog_id,
                    is_employee_chat: "1",
                    dialog_topic: topic,
                    user1_id: "" + user_object.id,
                    user2_id: second_user_id,
                    second_user_nickname: null,
                    second_user_photo: url_object.plugin_directory + "/images/question.png",
                    messages: m
                };

                mes[Object.keys(mes).length] = newDialog;

                addDialog(newDialog, mes);

                if(!is_emp_available)
                {
                    alert ("The consultant is not available at the moment.");
                }

                if(second_user_id===user_object.id)
                {
                    $(".contact-profile").css('display', 'none');
                    $(".messages").css('display', 'none');
                    $(".message-input").css('display', 'none');
                    $(".new-convo").css('display', 'none');
                }

            }

            if(dialog_type==="user_chat")
            {
                console.log(message);

                var newDialog = {
                    dialog_id: dialog_id,
                    is_employee_chat: "0",
                    dialog_topic: topic,
                    user1_id: "" + user_object.id,
                    user2_id: second_user_id,
                    second_user_nickname: second_user_name,
                    second_user_photo: default_photo,
                    messages: []
                };

                mes[Object.keys(mes).length] = newDialog;

                addDialog(newDialog, mes);
            }



            var sound = new Howl({
                src: ['http://178.128.202.94/wp-content/uploads/2019/04/unconvinced.mp3']
            });
            sound.play();


        }
    };
}

function newBanner(message) {
    var html_banner = '<li id="banner" class="mes-break">' +
        '<p>' + message + '</p></li>';

    $(html_banner).appendTo($('.messages ul'));
}

function searchObjKey(obj, query) {
    var new_obj = obj;

    delete new_obj.curr_user;

    for (let key in new_obj) {
        if (new_obj[key].dialog_id == query)
            return key;
    }
    return null;
}

function fillChat(mes) {
    var res = mes;
    delete res.curr_user;
    $("#conversations ul").empty();

    for (let i = 0; i < Object.keys(res).length; i++) {
        addDialog(res[i], mes);
    }

    let url = new URL(window.location.href);
    let d_id = url.searchParams.get("dialog_id");

    if(d_id!==null)
    {
        conn.send(JSON.stringify({
            user_id_from: user_object.id,
            command: 'new_chat',
            dialog_type: 'user_chat',
            dialog_id : d_id
        }));

        $("#" + d_id).click();
    }
}

function addDialog(item, mes) {

    let dialog_id = item.dialog_id;
    let is_employee_chat = item.is_employee_chat;
    let dialog_topic = item.dialog_topic;
    let user1_id = item.user1_id;
    let user2_id = item.user2_id;
    let messages = (item.messages === null || item.messages === undefined) ? [] : item.messages;

    let img = (is_employee_chat === "1") ? url_object.plugin_directory + "/images/question.png" : item.second_user_photo;
    let name = (is_employee_chat === "1") ? ((dialog_topic === null) ? item.second_user_nickname : dialog_topic) : item.second_user_nickname;
    name = (name === null || name === "" || name === undefined) ? "Question" : name;

    let preview = messages[messages.length - 1];
    let fromyou = (messages.length !== 0 && preview !== undefined) ? (preview.user_from_id === user_object.id) : false;
    let $node = $(dialog_templ({
        id: dialog_id,
        photo: img,
        name: name,
        sent: fromyou,
        preview: (preview !== undefined) ? preview : ""
    }));


    let N_unread = 0;
    for(var i = messages.length-1; i>0; i--)
    {
        if(messages[i].is_read ==="1")
        {
            break;
        }
        else
        {
            if(messages[i].user_from_id !== user_object.id)
            {
                N_unread++;
            }
        }
    }

    if(N_unread>0)
    {

        if ($node.find(".badge-counter").length === 0) {
            let badge = '<span class="badge badge-counter ml-2">' + N_unread+ '</span>';
            $(badge).appendTo($node.find(".wrap .meta .name"));
            $(badge).removeClass("hidden");

        } else {
            let val = $node.find(".badge-counter").text();
            $node.find(".badge-counter").text(N_unread);
            $node.removeClass("hidden");
        }
    }



    $node.click(function () {

        var newMessages = false;
        $(".contact-profile").css('display', '')
        $(".messages").css('display', '')
        $(".message-input").css('display', '')
        $(".new-convo").css('display', 'none');
        $('.contact-profile').removeClass("hidden");
        $('.message-input').removeClass("hidden");

        var idDialogHTML = $(this).attr('id');

        var idDialog = searchObjKey(mes, idDialogHTML);

        $('.conversation.active').removeClass("active");

        $(this).addClass("active");

        $('li.conversation').removeClass("hidden not_to_hide");

        var user2logo = $(this).find("img").attr('src');
        var user2name = $(this).find(".name").text();

        $('.contact-profile img').attr('src', user2logo);
        $('.contact-profile p').text(user2name);

        $('.messages ul').empty();

        if (idDialog !== undefined && idDialog !== null) {
            let value = parseInt($node.find(".badge-counter").text());
            if(value>0)
            {
                conn.send(JSON.stringify({
                    command: 'mark_messages',
                    dialog_id: idDialogHTML
                }));

                console.log("marked read/ id: " + idDialogHTML);
            }

            $node.find(".badge-counter").text(0);
            $node.find(".badge-counter").addClass("hidden");

            if (mes[idDialog].messages === null || mes[idDialog].messages === undefined) mes[idDialog].messages = [];

            for (let i = 0; i < mes[idDialog].messages.length; i++) {
                if (i === mes[idDialog].messages.length - value) {
                    if ($(".mes-break")[0] === undefined) {
                        newMessages = true;
                        newBanner("New messages");

                        setTimeout(function () {
                            var new_messages_banner = $(".mes-break")[0];
                            if (new_messages_banner !== undefined) new_messages_banner.parentNode.removeChild(new_messages_banner);
                        }, 5000);
                    }
                }
                addMes(mes[idDialog].messages[i], user2logo, is_employee_chat);
            }

            //gotoBottom('messages-container');

        }
        // TODO: badges

        scrollToBanner();
    });
    $("#conversations ul").prepend($node);
}

function addMes(item, user2logo, is_employee_chat) {
    var st = ((item.user_from_id === user_object.id) ? "sent" : "replies");

    var png = ((item.user_from_id === user_object.id) ? myprofilelogo : user2logo);

    if (is_employee_chat === "1" && item.user_from_id !== user_object.id) png = url_object.plugin_directory + "/images/logo.png";

    let $node = $(mes_templ({status: st, image: png, mes: item.message_body, time: item.create_timestamp}));

    $('.messages ul').append($node);
}

function gotoBottom(id) {
    var element = document.getElementById(id);
    element.scrollTop = element.scrollHeight - element.clientHeight;
}

function scrollToBanner() {
    var topPos = document.getElementById('banner').offsetTop;
    document.getElementById('messages-container').scrollTop = topPos-100;
}




