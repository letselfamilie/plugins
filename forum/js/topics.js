let $ = jQuery;

let fs = require('fs');
let ejs = require('ejs');

let topic_templ = ejs.compile(fs.readFileSync("./forum/js/ejs_templates/forum_topic.ejs", "utf8"));

function decodeUrl(){
    let search = location.search.substring(1);
    let url_params = JSON.parse('{"' + decodeURI(search).replace(/"/g, '\\"').replace(/&/g, '","').replace(/=/g,'":"') + '"}');
    return url_params;
}

$(function () {
    let url_params = decodeUrl();
    console.log(url_params);

    $(".back").attr('href', url_object.site_url + "/categories");

    let $topic_table = $("#topics_list");

    if($topic_table) {
        getTopics();
    }
    $('#cat_name').text(url_params != null ? url_params['cat_name'] : '');

    function getTopics() {
        $.ajax({
            url: url_object.ajax_url,
            type: 'POST',
            data: {
                action: 'get_forum_topics',
                cat_name: url_params != null ? url_params['cat_name'] : ''
            },

            success: function (res) {
                console.log(res);
                res = JSON.parse(res);
                displayTopics(res, topic_templ, $topic_table);

            },
            error: function (error) {
                console.log(error);
            }
        });
    }

    function displayTopics(data, template, container) {
        container.html("");
        if(data){
            data.forEach(function (item) {
                let $node = $(template({topic: item, url: url_object.site_url}));

                $node.find('.topic-name').on('click', function () {
                    window.open( url_object.site_url + "/posts/?topic_id=" + encodeURI(item.topic_id), "_self" );
                });

                container.append($node);
            });
        }
    }

    // disable hover effects on mobile
    document.addEventListener("touchstart", function(){}, true);

    $('#add-topic').on('click', function () {
        $('#add-panel').attr('style', '');
        $('.container-blured').addClass('blur');
    });

    $('#close-add-panel').on('click', function () {
        $('#add-panel').attr('style', 'display:none')
        $('.container-blured').removeClass('blur');
    });

    $('#add-form').submit(function (event) {
        $.ajax({
            url: url_object.ajax_url,
            type: 'POST',
            data: {
                action: 'add_topic',
                cat_name: url_params != null ? url_params['cat_name'] : '',
                topic_name: $('#new-topic-name').val(),
                is_anonym: ($('#chech-anonym').is(":checked")) ? 1 : 0,
                user_id: 1
            },
            success: function (res) {
                $('#add-panel').attr('style', 'display:none')
                $('.container-blured').removeClass('blur');
                $('#new-topic-name').val('');
                console.log(res);
                getTopics();
            },
            error: function (error) {
                console.log(error);
            }
        });


        return false;
    });

});