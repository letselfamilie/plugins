$ = jQuery;
let fs = require('fs');
let ejs = require('ejs');

const {Howl, Howler} = require('howler');
Date.prototype.ddmmyyyyhhmm = function () {
    var mm = this.getMonth() + 1;
    var dd = this.getDate();

    var HH = this.getHours();
    var MM = this.getMinutes();
    return ((dd > 9 ? '' : '0') + dd) + '-' + ((mm > 9 ? '' : '0') + mm) + '-' + this.getFullYear() + ' ' +
        ((HH > 9 ? '' : '0') + HH) + ':' + ((MM > 9 ? '' : '0') + MM);
};

Date.prototype.isSameDateAs = function(pDate) {
    return (
        this.getFullYear() === pDate.getFullYear() &&
        this.getMonth() === pDate.getMonth() &&
        this.getDate() === pDate.getDate()
    );
}

// First we get the viewport height and we multiple it by 1% to get a value for a vh unit
let vh = window.innerHeight * 0.01;
// Then we set the value in the --vh custom property to the root of the document
document.documentElement.style.setProperty('--vh', `${vh}px`);
let default_photo = "http://178.128.202.94/wp-content/plugins/ultimate-member/assets/img/default_avatar.jpg"
let myprofilelogo = url_object.plugin_directory + '/images/user.png';
let dialog_templ = ejs.compile(fs.readFileSync("./chat/js/ejs_templates/dialog.ejs", "utf8"));
let mes_templ = ejs.compile(fs.readFileSync("./chat/js/ejs_templates/message.ejs", "utf8"));
let conn;

let chat_sound_prop = 0;

// We listen to the resize event
window.addEventListener('resize', () => {
    // We execute the same script as before
    let vh = window.innerHeight * 0.01;
    document.documentElement.style.setProperty('--vh', `${vh}px`);
});


//http://www.letselfamilie.nl/wp-content/uploads/2019/06/unconvinced.mp3
$(function () {
    if (typeof AudioContext != "undefined" || typeof webkitAudioContext != "undefined") {
        var resumeAudio = function () {
            if (typeof g_WebAudioContext == "undefined" || g_WebAudioContext == null) return;
            if (g_WebAudioContext.state == "suspended") g_WebAudioContext.resume();
            document.removeEventListener("click", resumeAudio);
        };
        document.addEventListener("click", resumeAudio);
    }

    getChatSoundProp(function (sound_prop) {
        chat_sound_prop = sound_prop;
        getDialogs();
    });
});

function getChatSoundProp(callback) {
    $.ajax({
        url: url_object.ajax_url,
        type: 'POST',
        data: {
            action: 'sound_prop'
        },
        success: function (res) {
            res = JSON.parse(res);
            console.log("sound_prop: " + res);
            callback(res);
        },
        error: function (error) {
            console.log(error);
            callback(0);
        }
    });
}

function getDialogs() {
    $.ajax({
        url: url_object.ajax_url,
        type: 'POST',
        data: {
            action: 'get_dialogs',
            user_id: user_object.id, // example
            //other parameters
        },
        success: function (res) {
            console.log("Res_own_dialogs: " + res);

            if (user_object.role == 'adviser') {
                $.ajax({
                    url: url_object.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'get_general_dialogs'
                    },
                    success: function (res2) {
                        console.log("Res_general_dialogs: " + res2);

                        if (typeof res2 !== 'undefined' && res2.length > 0) {
                            var combined_res = concatArray(JSON.parse(res), JSON.parse(res2));
                            console.log("combined_res " + JSON.stringify(combined_res));
                        } else {
                            var combined_res = JSON.parse(res);
                        }

                        loadChat(combined_res);

                    },
                    error: function (error) {
                        console.log(error);
                    }
                });
            } else {
                console.log("JSON.parse: " + JSON.parse(res));

                loadChat(JSON.parse(res));
            }

            $('#messages-container').on('scroll', function () {
                if ($('#messages-container').scrollTop() < 1) {
                    var d_id = parseInt($('.conversation.active').attr("id"));
                    $.ajax({
                        url: url_object.ajax_url,
                        type: 'POST',
                        data: {
                            action: 'get_messages',
                            dialog_id: d_id,
                            from: $('#messages-container').find('ul').find('li').length,
                            to: $('#messages-container').find('ul').find('li').length + 20
                        },
                        success: function (res) {
                            console.log($('#messages-container').find('ul').find('li').length);
                            console.log("new mess: " + res);
                            res = JSON.parse(res);
                            // SCROLL MESS
                            res.reverse().forEach(function (item) {
                                addMes(item, null, null, true)
                            });

                        },
                        error: function (error) {
                            console.log(error);
                        }
                    });
                }
            })
        },
        error: function (error) {
            console.log(error);
        }
    });
}

function loadChat(mes) {
    console.log("LAST VERSION");

    let is_consultant = (user_object.role == 'adviser');
    let url = 'ws://178.128.202.94:8000/?userId=' + user_object.id + '&consultan=' + ((is_consultant) ? 1 : 0);
    conn = new WebSocket(url);

    $("#profile-img").attr('src', user_object.photo);

    $("#profile").find("p").text(user_object.username);

    conn.onopen = function (e) {
        console.log("Connection established.");
        console.log(e);

        var keys = Object.keys(user_object);

        console.log("user_object" + keys);

        fillChat(mes);

        $('.messages').animate({scrollTop: $(document).height()}, 'fast');

        $('.submit').click(function () {
            newMessage();
        });

        $('#form-question').on('submit', function () {
            e.preventDefault();
            return false;
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

            // var today = new Date();
            // var day = today.getDate();
            // var month = today.getMonth() + 1;
            //
            // day = (day < 10) ? "0" + day : "" + day;
            // month = (month < 10) ? "0" + month : "" + month;
            //
            // var time = today.getFullYear() + "-" + month + "-" + day + " " + today.getHours() + ":" + today.getMinutes() + ":" + today.getSeconds();

            conn.send(JSON.stringify({
                user_id_from: user_object.id,
                command: 'message',
                dialog_id: d_id,
                message: message,
                photo: user_object.photo,
                from_login: user_object.username
            }));

            //var m = {user_from_id: user_object.id, message_body: message, create_timestamp: time};

            // addMes(m, myprofilelogo, "0");
            //
            // let key = parseInt(searchObjKey(mes, d_id));
            //
            // var new_message = {
            //     message_id: "" + mes[Object.keys(mes).length - 1].message_id + 1,
            //     user_from_id: "" + user_object.id,
            //     dialog_id: "" + d_id, is_read: "0",
            //     message_body: "" + message,
            //     create_timestamp: time
            // };
            //
            // mes[key].messages.push(new_message);
            //
            messageInput.val(null);
            //
            // $('.conversation.active .preview').html('<span>You: </span>' + message);
            //
            // //$('.messages').animate({ scrollTop: $('.messages ul').children('li').last().position().top }, 'fast');
            //
            // $('.messages ul').children('li').last().focus();
            //
            // gotoBottom('messages-container');

        }

        $("#redirect_choose_consultant").click(function () {

            if (!($(".multi-collapse").hasClass("show"))) {
                var myNode = document.getElementById("consultantSelect");
                while (myNode.firstChild) {
                    myNode.removeChild(myNode.firstChild);
                }
                conn.send(JSON.stringify({
                    user_id_from: user_object.id,
                    command: 'get_employees',
                }));
            }
        });

        $("#redirect_btn").click(function () {
            var e = document.getElementById("consultantSelect");
            //var strUser = e.options[e.selectedIndex].value;
            var strUser = e.options[e.selectedIndex];
            var d_id = parseInt($('.conversation.active').attr("id"));
            console.log("val " + strUser);
            if (strUser !== undefined) {
                conn.send(JSON.stringify({
                    command: 'redirect_chat',
                    dialog_id: d_id,
                    new_employee: strUser.value,
                    user_id_from: user_object.id
                }));

                $("#" + d_id).detach();

                $(".conversation.active").removeClass("active");
                $(".contact-profile").css('display', 'none');
                $(".messages").css('display', 'none');
                $(".message-input").css('display', 'none');
                $(".redirect.multi-collapse").removeClass("show");

                var idDialog = searchObjKey(mes, d_id);
                delete mes[idDialog];
            }
        });

        $("#resolve-btn").click(function () {
            var d_id = parseInt($('.conversation.active').attr("id"));

            conn.send(JSON.stringify({
                command: 'close_chat',
                dialog_id: d_id
            }));


        });

        $("#btn-newmessage").click(function () {

            $(".conversation.active").removeClass("active");

            $(".contact-profile").css('display', 'none');
            $(".messages").css('display', 'none');
            $(".message-input").css('display', 'none');

            $(".new-convo").css('display', 'block');
            // TODO: add new dialog to server

        });

        $("#addNewDialog").click(function () {
            let topic = $("#inputTopic").val();
            let messageFirst = $("#inputFirstMessage").val();
            messageFirst = (messageFirst === null || messageFirst === undefined) ? "" : messageFirst;
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
                $('#inputTopic').val('');
                $('#inputFirstMessage').val('');
            } else (alert("Write your issue, please"))

            // TODO: check form for being filled in


        });

        return false;
    };

    conn.onmessage = function (e) {
        console.log(e.data);
        var data = JSON.parse(e.data);

        if (data.type === "close_chat") {
            if (data.state === "success") {
                resolvedDialogBanners();
                let dialog_id = data.dialog_id;
                let dialog_id_in_mes = searchObjKey(mes, dialog_id);
                if(dialog_id_in_mes !== undefined && dialog_id_in_mes !== null){
                    mes[dialog_id_in_mes].is_closed = 1;
                }

            } else {
                alert("Error occurred");
            }
        }

        if (data.type === "message") {
            console.log("new message");

            let sound = new Howl({
                src: ['http://www.letselfamilie.nl/wp-content/uploads/2019/06/unconvinced.mp3']
            });

            //    getChatSoundProp(function (sound_prop){
            //        console.log("getChatSoundProp");
            if (chat_sound_prop == 0) {
                console.log("sound");
                sound.play();
            }
            //    });

            let from = data.from;
            let time = data.create_timestamp;
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

                if (from != user_object.id) {

                    isRead = "1";

                    conn.send(JSON.stringify({
                        command: 'mark_messages',
                        dialog_id: dial_id
                    }));

                }

                $('.messages ul').children('li').last().focus();

            } else {

                if ($node.find(".badge-counter").length === 0) {
                    let badge = '<span class="badge badge-counter ml-2">1</span>';
                    $(badge).appendTo($node.find(".wrap .meta .name"));
                } else {
                    let val = $node.find(".badge-counter").text();

                    $node.find(".badge-counter").text(isNaN(parseInt(val)) ? 1 : parseInt(val) + 1);
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

            let dialog_id = data.dialog_id;


            if ($('#' + dialog_id).length > 0) {
                console.log("New dialog won't be created as it already exists");

                if (dialog_id !== null) {
                    let $node = $("#" + dialog_id);
                    $node.detach();
                    $node.prependTo("#conversations ul");
                    $node.click();
                    console.log("you created new chat with user ");
                }

                return;
            }

            let message = data.message;
            let first_user_id = data.user_info_1.user_id;
            let second_user_id = data.user_info_2.user_id; //id second user
            let first_user_name = data.user_info_1.user_login;
            let first_user_photo = data.user_info_1.user_photo;
            let second_user_name = data.user_info_2.user_login;
            let second_user_photo = data.user_info_2.user_photo;
            let dialog_type = data.dialog_type; //  employee_chat || user_chat
            let topic = data.topic;  // absent for user
            let is_emp_available = data.is_emp_available; //absent for user
            let first_message = data.first_message;

            console.log("I received request to create new dialog view");

            let isread = (second_user_id !== user_object.id) ? "1" : "0";

            var m = (first_message === null || first_message === undefined || first_message.message === "") ? [] :
                [{
                    message_id: "1",
                    dialog_id: "" + dialog_id,
                    is_read: isread,
                    user_from_id: first_message.from,
                    message_body: first_message.message,
                    create_timestamp: first_message.time
                }];

            console.log("dialog_type" + dialog_type);

            if (dialog_type === "employee_chat") {
                console.log("Employee chat view is requested to be created");

                var newDialog = {
                    dialog_id: dialog_id,
                    is_employee_chat: "1",
                    dialog_topic: topic,
                    user1_id: "" + user_object.id,
                    user2_id: second_user_id,
                    second_user_nickname: null,
                    second_user_photo: 'http://www.letselfamilie.nl/wp-content/uploads/2019/06/LetselFamilie-logo-1.png',
                    messages: m,
                    is_closed:0
                };

                mes[Object.keys(mes).length] = newDialog;

                addDialog(newDialog, mes);

                console.log(topic);

                if (user_object.role != 'adviser') {
                    $('#' + dialog_id).click();

                    if (!is_emp_available) {
                        newBanner("No consultant available at the moment - please wait till working hours.");
                        setTimeout(function () {
                            var new_messages_banner = $(".mes-break")[0];
                            if (new_messages_banner !== undefined) new_messages_banner.parentNode.removeChild(new_messages_banner);
                        }, 5000);

                    }


                }


                if ($("#" + dialog_id).find(".badge-counter").length === 0 && user_object.role == 'adviser') {
                    let badge = '<span class="badge badge-counter ml-2">new</span>';
                    $(badge).appendTo($("#" + dialog_id).find(".wrap .meta .name"));
                    $(badge).removeClass("hidden");

                } else {
                    $("#" + dialog_id).find(".badge-counter").text((m === []) ? "new" : 1);
                    $("#" + dialog_id).removeClass("hidden");

                }


                if (second_user_id === user_object.id) {
                    $(".contact-profile").css('display', 'none');
                    $(".messages").css('display', 'none');
                    $(".message-input").css('display', 'none');
                    $(".new-convo").css('display', 'none');
                }
            }

            if (dialog_type === "user_chat") {
                console.log("User chat view is requested to be created");


                var newDialog = {
                    dialog_id: dialog_id,
                    is_employee_chat: "0",
                    dialog_topic: topic,
                    user1_id: "" + user_object.id,
                    user2_id: second_user_id,
                    second_user_nickname: second_user_name,
                    second_user_photo: default_photo,
                    messages: [],
                    is_closed: 0
                };

                mes[Object.keys(mes).length] = newDialog;

                console.log(newDialog);

                addDialog(newDialog, mes);

                if ($("#" + dialog_id).find(".badge-counter").length === 0) {
                    let badge = '<span class="badge badge-counter ml-2">new</span>';
                    $(badge).appendTo($("#" + dialog_id).find(".wrap .meta .name"));
                    $(badge).removeClass("hidden");
                    //$("#"+dialog_id).detach();
                    //$("#"+dialog_id).prependTo("#conversations ul");
                } else {
                    $("#" + dialog_id).find(".badge-counter").text("new");
                    $("#" + dialog_id).removeClass("hidden");
                    //$("#"+dialog_id).detach();
                    //$("#"+dialog_id).prependTo("#conversations ul");
                }

                let url = new URL(window.location.href);
                let d_id = url.searchParams.get("dialog_id");

                if (d_id !== null) {
                    let $node = $("#" + d_id);
                    $node.detach();
                    $node.prependTo("#conversations ul");
                    $node.click();
                    console.log("you created new chat with user ");
                }
            }

            console.log(data.message);

            let sound = new Howl({
                src: ['http://www.letselfamilie.nl/wp-content/uploads/2019/06/unconvinced.mp3']
            });
            //  getChatSoundProp(function (sound_prop){
            if (chat_sound_prop == 0) {
                console.log("sound");
                sound.play();
            }
            //  });
        }

        if (data.type === "take_dialog") {
            if (data.state === "success") {
                var dIdHTML = data.dialog_id;
                var idDialog = searchObjKey(mes, dIdHTML); //id in global array
                delete mes[idDialog];
                let $node = $("#" + dIdHTML);
                $node.detach();
            } else {
                console.log("Error with receiving response to somebody taking the dialog");
            }
        }

        if (data.type === "get_employees") {
            console.log("Employees that are online");

            var consultants_online = data.employees_information;

            for (var i = 0; i < consultants_online.length; i++) {
                var log = consultants_online[i].user_login;
                var id = consultants_online[i].user_id;
                if (id !== user_object.id) {
                    var line = '<option value="' + id + '">' + log + '</option>';
                    $(line).appendTo($("#consultantSelect"));
                }

            }


        }


        if (data.type === "redirect_chat" && data.state === "success") {
            var dialog_id = data.dialog_id;
            var topic = data.dialog_info.dialog_topic;
            var last_message_timestamp = data.dialog_info.last_message_timestamp;
            var client_id = data.dialog_info.user_id;
            var messages = data.dialog_info.messages;

            console.log("Chat is redirected");

            if ($('#' + dialog_id).length > 0) {
                console.log("New dialog won't be created as it already exists");

                if (dialog_id !== null) {
                    let $node = $("#" + dialog_id);
                    $node.detach();
                    $node.prependTo("#conversations ul");
                    $node.click();
                    console.log("you created new chat with user ");
                }

                return;
            }

            var newDialog = {
                dialog_id: dialog_id,
                is_employee_chat: "1",
                dialog_topic: topic,
                user1_id: "" + user_object.id,
                user2_id: client_id,
                second_user_nickname: null,
                second_user_photo: 'http://www.letselfamilie.nl/wp-content/uploads/2019/06/LetselFamilie-logo-1.png',
                messages: messages
            };

            mes[Object.keys(mes).length] = newDialog;
            addDialog(newDialog, mes);

            if ($("#" + dialog_id).find(".badge-counter").length === 0) {
                let badge = '<span class="badge badge-counter ml-2">redirected</span>';
                $(badge).appendTo($("#" + dialog_id).find(".wrap .meta .name"));
                $(badge).removeClass("hidden");

            } else {
                $("#" + dialog_id).find(".badge-counter").text((m === []) ? "redirected" : 1);
                $("#" + dialog_id).removeClass("hidden");

            }

        }
    };
}

//RESOLVED DIALOG BANNERS
function resolvedDialogBanners() {
    insideDialogResolvedBanners();
    resolvedBage($(".conversation.active .wrap .meta .name"));
}

function insideDialogResolvedBanners() {
    var badge = '<span class="badge badge-resolved ml-2">Opgelost</span>';
    $(badge).appendTo($("#chat-title"));

    newBanner("Dit probleem is opgelost");
    $('.message-input').css('display', 'none');
    //$("#resolve-btn").addClass("hidden");
    $("#chat_options").addClass("hidden");
}

function resolvedBage($appendNode) {
    var badge = '<span class="badge badge-resolved ml-2">R</span>';
    $(badge).appendTo($appendNode);
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

    if (d_id !== null) {
        conn.send(JSON.stringify({
            user_id_from: user_object.id,
            command: 'new_chat',
            dialog_type: 'user_chat',
            dialog_id: d_id
        }));

        console.log("Request to create new dialog with user has been sent");
    }
}

function addDialog(item, mes) {
    let dialog_id = item.dialog_id;

    let is_employee_chat = item.is_employee_chat;
    let dialog_topic = item.dialog_topic;
 //   let user1_id = item.user1_id;
    let user2_id = item.user2_id == user_object.id ? item.user1_id : item.user2_id;
    let without_employee = item.without_employee;
    let messages = (item.messages === null || item.messages === undefined) ? [] : item.messages;

    let is_closed = item.is_closed;

    let img = (is_employee_chat === "1") ? 'http://www.letselfamilie.nl/wp-content/uploads/2019/06/LetselFamilie-logo-1.png' : item.second_user_photo;
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

    /*COUNT NUMBER OF UNREAD MESSAGES*/
    for (let i = messages.length - 1; i > -1; i--) {
        if (messages[i].is_read === "1") {
            break;
        }

        else {
            if (messages[i].user_from_id === user2_id) {
                N_unread++;
            }
        }
    }

    /*ADD BADGE TO DIALOG WITH A NUMBER OF UNREAD MESSAGES*/
    if (N_unread > 0) {

        if ($node.find(".badge-counter").length === 0) {
            var text = N_unread;
            if (item.without_employee === '1') {
                text = "in line"
            }
            let badge = '<span class="badge badge-counter ml-2">' + text + '</span>';
            $(badge).appendTo($node.find(".wrap .meta .name"));
            $(badge).removeClass("hidden");

        } else {
            $node.find(".badge-counter").text(N_unread);
            $node.removeClass("hidden");
        }
    }

    if(is_employee_chat === "1"){
        if (is_closed === '1') {
            resolvedBage($node.find(".wrap .meta .name"));
        }
    }


    /* WHEN SCROLLING SHOW DATE BUBBLE ON TOP OF CHAT */
    /*var stopScrollTimer = null;
    $("#messages-container").on('scroll', function() {
        //setDateBubble();
        if(stopScrollTimer !== null) {
            setDateBubble();
            clearTimeout(stopScrollTimer);
        }
        stopScrollTimer = setTimeout(function() {
            $("#messages-container").removeClass("messages-move-top");
            $("#date-bubble").addClass("hidden");
            console.log("Finished scrolling");
        }, 3000);
    });

    var scrollTimer = null;
    function setDateBubble() {
        if (scrollTimer !== null) {
            clearTimeout(scrollTimer);
        }
        scrollTimer = setTimeout(function () {
            let messages = $("#messages-container ul").children().toArray();
            messages.forEach((mes) => {
                let offset = mes.offsetTop;
                //console.log(offset);
                if (offset < 150) {
                    console.log(offset + " " + mes.children[1]);

                    let date_reg = new RegExp('(.|\\s)*(\\d{4}-\\d{2}-\\d{2})(.|\\s)*');
                    let text = mes.innerHTML;
                    text = text.replace(date_reg, '$2');
                    console.log(text);
                    $("#date-bubble").innerHTML = text;
                }
            });

            $("#messages-container").addClass("messages-move-top");
            $("#date-bubble").removeClass("hidden");
        }, 300);
    }*/


    $node.click(function () {
        var newMessages = false;
        $(".contact-profile").css('display', '');
        $(".messages").css('display', '');
        $(".message-input").css('display', '');
        $(".new-convo").css('display', 'none');
        $('.contact-profile').removeClass("hidden");
        $('.message-input').removeClass("hidden");

        $("#chat-title").text(name);

        var idDialogHTML = $(this).attr('id'); //idDialog in DB

        var idDialog = searchObjKey(mes, idDialogHTML); //id in global array

        $('.conversation.active').removeClass("active");

        $(this).addClass("active");

        $('li.conversation').removeClass("hidden not_to_hide");

        var user2logo = $(this).find("img").attr('src');

        var user2name = $(this).find(".name").text();

        $('.contact-profile img').attr('src', user2logo);

        if ($('.contact-profile p').text() === "") $('.contact-profile p').text(user2name);

        $('.messages ul').empty();

        if(is_employee_chat === "1") {
            if (is_closed === '1') {
                insideDialogResolvedBanners();
            } else {
                $("#chat_options").removeClass("hidden");
            }
        } else {
            $("#chat_options").addClass("hidden");
        }

        if (idDialog !== undefined && idDialog !== null) {
            if(mes[idDialog].is_closed === 1){
                insideDialogResolvedBanners();
            }

         //   let dialog_mes = mes[idDialog].messages;
         //   let last_mes = dialog_mes.length > 0 ? dialog_mes[dialog_mes.length - 1] : null;
           // console.log("length " + dialog_mes.length);

            let value = parseInt($node.find(".badge-counter").text());

            if (!fromyou) {
                /*MARK MESSAGES TO BE READ*/
                if (value > 0) {
                    conn.send(JSON.stringify({
                        command: 'mark_messages',
                        dialog_id: idDialogHTML
                    }));

                    console.log("marked read/ id: " + idDialogHTML);
                }

                $node.find(".badge-counter").text(0);
                $node.find(".badge-counter").addClass("hidden");
            }

            if (mes[idDialog].messages === null || mes[idDialog].messages === undefined) mes[idDialog].messages = [];

            /*ADD MESSAGES TO THE DIALOG*/
            for (let i = 0; i < mes[idDialog].messages.length; i++) {
                if (i === mes[idDialog].messages.length - value && !fromyou) {
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


            /*IF EMPLOYEE TAKES DIALOG WHICH IS IN LINE (NOBODY'S)*/
            if (item.without_employee === '1') {

                conn.send(JSON.stringify({
                    user_id_from: user_object.id,
                    command: 'take_dialog',
                    dialog_id: idDialogHTML
                }));

                conn.send(JSON.stringify({
                    command: 'mark_messages',
                    dialog_id: idDialogHTML
                }));

                mes[idDialog].without_employee = "0";
                mes[idDialog].user2_id = user_object.id;
            }
        }
        scrollToBanner();
    });

    $("#conversations ul").prepend($node);
}

function addMes(item, user2logo, is_employee_chat, prepend) {
    var st = ((item.user_from_id === user_object.id) ? "sent" : "replies");
    var png = ((item.user_from_id === user_object.id) ? myprofilelogo : user2logo);
    if (is_employee_chat === "1" && item.user_from_id !== user_object.id) png = url_object.plugin_directory + "/images/logo.png";

    var now = new Date();
    var today = new Date(Date.UTC(now.getUTCFullYear(), now.getUTCMonth(), now.getUTCDate() ));
    var parts =item.create_timestamp.split(' ')[0].split('-');
    var mydate = new Date(parts[0], parts[1] - 1, parts[2]);
    var temp = new Date(); temp.setDate(today.getDate() - 1);

    let $node = $(mes_templ({status: st, image: png, mes: item.message_body, time: new Date(item.create_timestamp.replace(/\s/, 'T')).ddmmyyyyhhmm()})); // new Date(item.create_timestamp.replace(/\s/, 'T')).ddmmyyyyhhmm()}));


    var text = item.create_timestamp.split(' ')[0];
    if(mydate.isSameDateAs(today)) {
        text= "today";
    }
    if(mydate.isSameDateAs(temp)){
        text= "yesterday";
    }

    var addBanner = true;

    var lastMes = (!prepend)? $(".messages ul li.mes").last() : $(".messages ul li.mes").first();
    //var lastMes = $(".messages ul li.mes:last-child");

    let date_reg = new RegExp('(.|\\s)*(\\d{2}-\\d{2}-\\d{4})(.|\\s)*');
    let date = lastMes.prop('outerHTML');


    if(date!==undefined){
        date = date.replace(date_reg, '$2');
        var parts2 =date.split(' ')[0].split('-');
        var dateOfPrevMes = new Date(parts2[2], parts2[1] - 1, parts2[0]);

        addBanner = (mydate.isSameDateAs(dateOfPrevMes))? false:true;
        console.log("date of mes: " + date);
    }
    
    var html_banner = '<li id="banner" class="mes-break">' +  '<p>' +  text + '</p></li>';
    if (prepend) {
        if(addBanner)
        {
            $(html_banner).prependTo($('.messages ul'));
        }
        $('.messages ul').prepend($node);

    } else {

        if(addBanner)
        {
            $(html_banner).appendTo($('.messages ul'));
        }
        $('.messages ul').append($node);
    }
}

function gotoBottom(id) {
    var element = document.getElementById(id);
    element.scrollTop = element.scrollHeight - element.clientHeight;
}


function concatArray(a1, a2) {

    Object.size = function (obj) {
        var size = 0, key;
        for (key in obj) {
            if (obj.hasOwnProperty(key)) size++;
        }
        return size;
    };
    var size = Object.keys(a1).length - 1;

    for (var i = 0; i < a2.length; i++) {
        a1[size] = a2[i]
        size++;
    }


    return a1;
}

function scrollToBanner() {
    if (document.getElementById('banner') != null) {
        var topPos = document.getElementById('banner').offsetTop;
        document.getElementById('messages-container').scrollTop = topPos;
        $('#messages-container').scrollTop($('#messages-container').scrollTop() - 100);
    }
}







