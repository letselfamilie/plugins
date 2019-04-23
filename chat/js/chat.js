$ = jQuery;
let fs = require('fs');
let ejs = require('ejs');

// First we get the viewport height and we multiple it by 1% to get a value for a vh unit
let vh = window.innerHeight * 0.01;
// Then we set the value in the --vh custom property to the root of the document
document.documentElement.style.setProperty('--vh', `${vh}px`);

let myprofilelogo = url_object.plugin_directory +'/images/user.png';
let dialog_templ = ejs.compile(fs.readFileSync("./chat/js/ejs_templates/dialog.ejs", "utf8"));
let mes_templ = ejs.compile(fs.readFileSync("./chat/js/ejs_templates/message.ejs", "utf8"));

// We listen to the resize event
window.addEventListener('resize', () => {
    // We execute the same script as before
    let vh = window.innerHeight * 0.01;
    document.documentElement.style.setProperty('--vh', `${vh}px`);
});

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
    let conn = new WebSocket('ws://178.128.202.94:8000/?userId='+user_object.id);
    conn.onopen = function(e) {
        console.log("Connection established!");
        console.log(e);

        fillChat(mes);

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

        $('#search').keyup(function() {

            /*let $node = $("#"+ 1);
            if($node.find("span.counter").length===0)
            {
                $node.find(".wrap").append("<span class='counter hidden'>1</span>");
            }
            else
            {
                let value = $node.find("span.counter").text();
                $node.find("span.counter").text(parseInt(value)+1);
            }*/



            $('#inputSearch').focus();
            var input = $('#inputSearch').val().trim();

            if(input!=="")
            {
                console.log("Search "+input);

                $("li.conversation").filter(function() {
                    return $( this ).find(".name").text().indexOf(input) >= 0;
                }).addClass("not_to_hide").removeClass("hidden");

                $("li.conversation").filter(function() {
                    return $( this ).find(".name").text().indexOf(input) < 0;
                }).removeClass("not_to_hide");

                $('li.conversation:not(.not_to_hide)').addClass("hidden");


            }
            else $('li.conversation').removeClass("hidden not_to_hide");
        });

        function newMessage() {
            var messageInput = $(".message-input input");

            message = messageInput.val();

            if($.trim(message) == '') {
                return false;
            }

            var d_id = parseInt($('.conversation.active').attr("id"));

            conn.send(JSON.stringify({
                user_id_from:user_object.id,
                command:'message',
                dialog_id: d_id,
                message: message
            }));

            var today = new Date();
            var day = today.getDate();
            var month = today.getMonth()+1;

            day = (day<10)? "0"+day : "" + day;
            month = (month<10)? "0"+month : "" + month;

            var time = today.getFullYear()+"-"+day+"-"+month +" "+ today.getHours() + ":" + today.getMinutes() + ":" + today.getSeconds();

            var m ={user_from_id:user_object.id, message_body: message, create_timestamp: time};

            addMes(m , myprofilelogo, "0");

            let key = parseInt(searchObjKey (mes, d_id));

            var new_message = {message_id: "" + mes[Object.keys(mes).length -1 ].message_id + 1 , user_from_id:"" + user_object.id,
                dialog_id: ""+d_id ,is_read:"0",message_body:""+ message, create_timestamp:time};

            mes[key].messages.push(new_message );

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

        });

        $("#btn-newmessage").click(function () {
            $(".contact-profile").css('display', 'none');
            $(".messages").css('display', 'none');
            $(".message-input").css('display', 'none');

            $(".new-convo").css('display', 'block');
            // TODO: add new dialog to server and to side-bar

        });

        $( "#addNewDialog" ).click(function( ) {
            event.preventDefault();
            let topic = $("#inputTopic").val();
            let messageFirst = $("#inputFirstMessage").val();
            if(topic!== "" )
            {
                console.log(topic);
                console.log(messageFirst);

                // TODO: add new dialog to server

                var today = new Date();
                var day = today.getDate();
                var month = today.getMonth()+1;

                day = (day<10)? "0"+day : "" + day;
                month = (month<10)? "0"+month : "" + month;
                var time = today.getFullYear()+"-"+day+"-"+month +" "+ today.getHours() + ":" + today.getMinutes() + ":" + today.getSeconds();

                var m ={message_id:"1", dialog_id:"3" ,is_read: "0",
                    user_from_id: user_object.id, message_body: messageFirst, create_timestamp: time};

                var newDialog = {dialog_id:"4", //TODO: auto-increase dialog_id
                    is_employee_chat:"1", dialog_topic:topic, user1_id: ""+user_object.id,
                    user2_id:"6",  // TODO: auto-transfer to certain employee id
                    second_user_nickname:null, second_user_photo: url_object.plugin_directory +"/images/question.png",
                    messages: [m] };

                mes[Object.keys(mes).length] = newDialog;


                addDialog(newDialog, mes);

                $(".contact-profile").css('display', '');
                $(".messages").css('display', 'none');
                $(".message-input").css('display', 'none');

                $(".new-convo").css('display', 'none');
                $(".conversation.active").removeClass("active");
            }
            else(alert("Write your issue, please"))

            // TODO: add new dialog to side-bar

            // TODO: check form for being filled in


        });

    };

    conn.onmessage = function(e) {
        console.log(e.data);

        var data = JSON.parse(e.data)

        console.log("e.data.type "+data.type);

        if(data.type==="message")
        {
            let from = data.from;
            let time = data.time;
            let mess = data.message;
            let dial_id = data.dialog_id;
            let is_chat_with_employee = data.is_employee_chat;

            let key = searchObjKey (mes, dial_id);


            var new_message = {message_id: "" + mes[Object.keys(mes).length -1 ].message_id + 1 , user_from_id: from,
                dialog_id: dial_id , is_read:"0", message_body:mess, create_timestamp:time};

            mes[key].messages.push(new_message);


            $("#"+ dial_id+" p.preview").text(mess);
            let $node = $("#"+ dial_id);
            $node.detach();
            $node.prependTo("#conversations ul");


            if($node.hasClass("active"))
            {

                //adding message in the open chat
                var m ={ user_from_id: from, message_body: mess, create_timestamp: time};
                addMes(m , $('.conversation.active').find("img").attr('src') , is_chat_with_employee);


                $('.messages ul').children('li').last().focus();
                //$('.messages').animate({ scrollTop: $(document).height() }, 'fast');
            }
            else{

                if($node.find("span.counter").length===0)
                {
                    $node.find(".wrap").append("<span class='counter hidden'>1</span>");
                }
                else
                {
                    let value = $node.find("span.counter").text();
                    $node.find("span.counter").text(parseInt(value)+1);
                }

                // TODO: add badges of new messages + counter to the conversation

                console.log("Dialog "+ dial_id + " has new message");
            }

            gotoBottom('messages-container');
        }

        if(data.type==="dialog")
        {
            // TODO: when someone wants to create new dialog with you
        }
    };

}

function newBanner(message) {
    var html_banner = '<li id="banner" class="mes-break">' +
        '<p>' + message + '</p></li>';

    $(html_banner).appendTo($('.messages ul'));
}

function searchObjKey (obj, query) {

    var new_obj = obj;

    delete new_obj.curr_user;

    for (let key in new_obj) {
        if(new_obj[key].dialog_id==query) return  key;
    }
    return null;
}

function fillChat (mes) {
    var res = mes;
    delete res.curr_user;
    $("#conversations ul").empty();


    for(let i =0 ; i<Object.keys(res).length; i++)
    {
        addDialog(res[i], mes);
    }


}

function addDialog(item, mes) {
    let dialog_id = item.dialog_id;
    let is_employee_chat = item.is_employee_chat;
    let dialog_topic = item.dialog_topic;
    let user1_id = item.user1_id;
    let user2_id = item.user2_id;
    let messages = (item.messages===null || item.messages===undefined)?[] : item.messages;

    let img = (is_employee_chat==="1")? url_object.plugin_directory +"/images/question.png" : item.second_user_photo;
    let name = (is_employee_chat==="1")? dialog_topic : item.second_user_nickname;

    let preview = messages[messages.length - 1];
    let sent = (messages.length!==0 && preview!==undefined)? (preview.user_from_id == parseInt(mes.curr_user)):  false   ;
    let $node = $(dialog_templ({id: dialog_id, photo: img, name:name, sent: sent, preview: (preview!== undefined)?preview: "" }));


    $node.click(function() {

        var newMessages =false;
        $(".contact-profile").css('display', '')
        $(".messages").css('display', '')
        $(".message-input").css('display', '')
        $(".new-convo").css('display', 'none');
        $('.contact-profile').removeClass("hidden");
        $('.message-input').removeClass("hidden");

        var idDialogHTML = $(this).attr('id');

        var idDialog = searchObjKey(mes,idDialogHTML );

        $('.conversation.active').removeClass("active");

        $(this).addClass("active");

        $('li.conversation').removeClass("hidden not_to_hide");

        var user2logo = $(this).find("img").attr('src');
        var user2name = $(this).find(".name").text();

        $('.contact-profile img').attr('src', user2logo);
        $('.contact-profile p').text(user2name);

        $('.messages ul').empty();

        if(idDialog!== undefined && idDialog!== null)
        {
            let value = parseInt($node.find("span.counter").text());
            $node.find("span.counter").text(0);

            if(mes[idDialog].messages === null || mes[idDialog].messages === undefined) mes[idDialog].messages=[];

            for(let i = 0; i< mes[idDialog].messages.length; i++)
            {
                if(i===mes[idDialog].messages.length-value)
                {
                    if($(".mes-break")[0] === undefined)
                    {
                        newMessages =true;
                        newBanner("New messages");

                        setTimeout(function() {
                            var new_messages_banner = $(".mes-break")[0];
                            if(new_messages_banner!==undefined) new_messages_banner.parentNode.removeChild(new_messages_banner);
                        }, 5000);
                    }
                }
                addMes(mes[idDialog].messages[i] , user2logo, is_employee_chat);
            }
            if(newMessages) {gotoBottom('banner');}
            else {gotoBottom('messages-container');}
            newMessages = false;
        }
        // TODO: badges


    });
    $("#conversations ul").prepend($node);
}

function addMes(item, user2logo, is_employee_chat) {
    var st = ((item.user_from_id===user_object.id) ? "sent" : "replies");

    var png = ((item.user_from_id===user_object.id) ? myprofilelogo : user2logo);

    if(is_employee_chat==="1" && item.user_from_id!==user_object.id) png = url_object.plugin_directory +"/images/logo.png";

    let $node = $(mes_templ({status: st, image: png, mes:item.message_body, time: item.create_timestamp }));

    $('.messages ul').append($node);
}

function gotoBottom(id){
    var element = document.getElementById(id);
    element.scrollTop = element.scrollHeight - element.clientHeight;
}
