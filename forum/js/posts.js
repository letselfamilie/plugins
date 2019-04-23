let $ = jQuery;

let fs = require('fs');
let ejs = require('ejs');

let post_templ = ejs.compile(fs.readFileSync("./forum/js/ejs_templates/forum_post.ejs", "utf8"));

let paginationInit = require('./pagination');

function decodeUrl(){
    let search = location.search.substring(1);
    let url_params = JSON.parse('{"' + decodeURI(search).replace(/"/g, '\\"').replace(/&/g, '","').replace(/=/g,'":"') + '"}');
    return url_params;
}

$(function () {
    loader(true);

    function hasTouch() {
        return 'ontouchstart' in document.documentElement
            || navigator.maxTouchPoints > 0
            || navigator.msMaxTouchPoints > 0;
    }

    let url_params = decodeUrl();
    console.log(url_params);

    $('textarea').autoResize();

    let topic_id = url_params != null ? url_params['topic_id'] : -1;
    let user_id = user_object.id;
    console.log(user_id);

    var respond_to_id = null;
    var posts_table = $("#posts");
    var post_to_delete = null;
    var curr_category_url = null;

    var pagination_obj = {current_page: 1};
    var per_page = 20;

    if (topic_id == -1) {
        window.location.replace(url_object.site_url + "/categories");
    }

    getInfAboutTopic();
    // loadMyInfo();
    setUpListeners();

    loadPost(pagination_obj.current_page);

    initPagination();
    function initPagination() {
        $.ajax({
            url: url_object.ajax_url,
            type: 'POST',
            data: {
                action: 'n_posts_pages',
                per_page: per_page,
                topic_id: topic_id
            },
            success: function (res) {
                max_page = res;
                paginationInit(pagination_obj.current_page, max_page, 5, loadPost, pagination_obj);
            },
            error: function (error) {
                console.log(error);
            }
        });
    }

    $(".enter-butt").on("click", function (e) {
        var parent = e.target.parentElement;
        console.log($('#chech-anonym', parent).is(":checked"));

        if ($('#enter-textarea').val().trim() !== "") {
            $.ajax({
                url: url_object.ajax_url,
                type: 'POST',
                data: {
                    action: 'add_post',
                    response_to: (respond_to_id == null) ? 'NULL' : respond_to_id,
                    topic_id: topic_id,
                    user_id: user_id,
                    post_message: $('#enter-textarea').val().trim(),
                    is_anonym: ($('#chech-anonym', parent).is(":checked")) ? 1 : 0,
                    is_reaction: (respond_to_id == null) ? 0 : 1
                },
                success: function (res) {
                    console.log("POSTED!");
                    console.log(res);
                    $('#enter-textarea').val('')
                    $('textarea').css('height', '130px');
                    $('.respond-info').css('display', 'none');
                    respond_to_id = null;
                    loadPost(pagination_obj.current_page);
                    setUpListeners();
                },
                error: function (error) {
                    console.log(error);
                }
            });
        }
    });


    function getInfAboutTopic() {
        $.ajax({
            url: url_object.ajax_url,
            type: 'POST',
            data: {
                action: 'get_topic_by_id',
                topic_id: topic_id,
                user_id: user_id
            },

            success: function (res) {
                res = JSON.parse(res);
                console.log(res);
                if(res){
                    $("#topic_name").text(res['topic_name']);
                    $("#topic_date").text(res['create_timestamp']);
                    $("#added-by").text(res['user_name']);
                    $(".back").attr('href', url_object.site_url + "/topics/?cat_name=" + encodeURI(res.cat_name));
                    curr_category_url = url_object.site_url + "/topics/?cat_name=" + encodeURI(res.cat_name);
                    if (res.fav > 0) {
                        $(".star-empty").addClass('none');
                        $(".star-full").removeClass('none');
                    } else {
                        $(".star-empty").removeClass('none');
                        $(".star-full").addClass('none');
                    }
                }
            },
            error: function (error) {
                console.log(error);
            }
        });
    }


    function loader(turnOn) {
        if (turnOn) {
            $('#loader').removeClass('none');
            $('.container-blured').addClass('blur');
        } else {
            $('#loader').addClass('none');
            $('.container-blured').removeClass('blur');
        }
    }

    function loadPost(page) {
        loader(true);

        posts_table.find(".post-row").remove();

        $.ajax({
            url: url_object.ajax_url,
            type: 'POST',
            data: {
                action: 'get_posts',
                topic_id: topic_id,
                user_id: user_id,
                page_number: page,
                per_page: per_page

            },

            success: function (res) {
                console.log(res);
                res = JSON.parse(res);
                console.log(res);
                res.forEach(function (item) {
                    addPost(item)
                });
                setTimeout(function () {
                    loader(false);
                }, 1000);
            },
            error: function (error) {
                console.log(error);
            }
        });
    }

    // function loadMyInfo() {
    //     $.ajax({
    //         url: url_object.ajax_url,
    //         type: 'POST',
    //         data: {
    //             action: 'get_user',
    //             user_id: user_id
    //         },
    //
    //         success: function (res) {
    //             res = JSON.parse(res);
    //             console.log(res);
    //             $("#my-name").text(res.first_name + " " + res.surname);
    //             $("#my-photo").attr("src",url_object.template_directory + res.photo);
    //             curr_user = res;
    //         },
    //         error: function (error) {
    //             console.log(error);
    //         }
    //     });
    // }


    function addPost(data) {
        let $node = $(post_templ({post: data,
                user_id:user_id,
                url: url_object.template_directory,
                role: user_object.role}));

        var is_liked = data.liked == '1';
        var n_likes = parseInt(data.n_likes);
        var n_responds = parseInt(data.n_responds);


        if (user_id > 0) {
            $node.on('click', '.comment-full', function () {
                var post_text = data.post_message.substring(0, 75) + ((data.post_message.length <= 75) ? '' : "...");
                $("#quote-text").text(data.first_name + " " + data.surname + ': ' + post_text);

                $('.respond-info').css('display', 'inline-block');

                respond_to_id = data.post_id;
                console.log(respond_to_id);

                $("#enter-textarea").focus();
            });

            $node.on('click', '.comment-empty', function () {
                var post_text = data.post_message.substring(0, 75) + ((data.post_message.length <= 75) ? '' : "...");
                $("#quote-text").text(data.first_name + " " + data.surname + ': ' + post_text);

                $('.respond-info').css('display', 'inline-block');

                respond_to_id = data.post_id;
                console.log(respond_to_id);

                $("#enter-textarea").focus();
                $('.comment-full').addClass('none');
                $('.comment-empty').removeClass('none');
                $node.find('.comment-full').removeClass('none');
                $node.find('.comment-empty').addClass('none');
            });

            $node.on('click', '.full-like', function () {

                $node.find(".empty-like").removeClass('none');
                $node.find(".full-like").addClass('none');
                $.ajax({
                    url: url_object.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'dislike',
                        post_id: data.post_id,
                        user_id: user_id
                    },

                    success: function (res) {
                        console.log('disliked');

                        n_likes -= 1;
                        $node.find('.like-number').text(n_likes);
                    },
                    error: function (error) {
                        console.log(error);
                    }
                });

            });

            $node.on('click', '.empty-like', function () {

                $node.find(".empty-like").addClass('none');
                $node.find(".full-like").removeClass('none');
                $.ajax({
                    url: url_object.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'like',
                        post_id: data.post_id,
                        user_id: user_id
                    },

                    success: function (res) {
                        console.log('liked');
                        n_likes += 1;
                        $node.find('.like-number').text(n_likes);
                    },
                    error: function (error) {
                        console.log(error);
                    }
                });
            });

            $node.find('.dropdown').on('click', function () {
                dropdown($(this))
            });

            $node.on('click', '.delete', function () {
                $('.container-blured').addClass('blur');
                $('#delete-post-panel').attr('style', '');
                post_to_delete = data.post_id;
            });

            $node.on('click', '.edit', function () {
                $node.find('.content-edit').removeClass('none');
                $node.find('.edit-textarea').text($node.find('.message').html().replace(/<br>/g, '\n'));
                $node.find('.edit-textarea').focus();
                $node.find('.message').addClass('none');
            });

            $node.on('click', '.save-butt', function () {
                var textarea = $node.find('.edit-textarea').val();
                if (textarea.trim() !== '') {
                    $.ajax({
                        url: url_object.ajax_url,
                        type: 'POST',
                        data: {
                            action: 'update_post',
                            post_id: data.post_id,
                            user_id: user_id,
                            post_message: textarea
                        },

                        success: function (res) {
                            console.log(res);
                            $node.find('.message').removeClass('none');
                            $node.find('.content-edit').addClass('none');
                            $node.find('.message').html(textarea.replace(/\n/g, '<br>'));
                        },
                        error: function (error) {
                            console.log(error);
                        }
                    });
                }
            });
        } else {
            $node.on('click', '.comment-empty', function () {
                window.location.href =  url_object.site_url + "/register";
            });

            $node.on('click', '.empty-like', function () {
                window.location.href =  url_object.site_url + "/register";
            });
        }

        posts_table.append($node);
        $node.insertBefore(".post-enter");
    }

    function setUpListeners() {
        $('#del-quote').on('click', function (e) {
            $('.respond-info').css('display', 'none');
            respond_to_id = null;

            console.log(respond_to_id);
            $('.comment-full').addClass('none');
            $('.comment-empty').removeClass('none');
        });
    }










    // Post delete
    $('#delete-post-panel').find('.cancel-butt').on('click', function () {
        $('.container-blured').removeClass('blur');
        $('#delete-post-panel').attr('style', 'display:none');
    });

    $('#delete-post-panel').find('.close-delete-panel').on('click', function () {
        $('.container-blured').removeClass('blur');
        $('#delete-post-panel').attr('style', 'display:none');
    });

    $('#delete-post-panel').find('.delete-butt').on('click', function () {
        $.ajax({
            url: url_object.ajax_url,
            type: 'POST',
            data: {
                action: 'delete_post',
                post_id: post_to_delete,
                user_id: user_id
            },

            success: function (res) {
                console.log('deleted');
                $('.container-blured').removeClass('blur');
                $('#delete-post-panel').attr('style', 'display:none');
                loadPost(pagination_obj.current_page);
            },
            error: function (error) {
                console.log(error);
            }
        });
    });


    // Editing topic dropdown

    $('#topic-dropdown').find('.delete').on('click', function () {
        $('.container-blured').addClass('blur');
        $('#delete-topic-panel').attr('style', '');
    });

    $('#delete-topic-panel').find('.cancel-butt').on('click', function () {
        $('.container-blured').removeClass('blur');
        $('#delete-topic-panel').attr('style', 'display:none');
    });

    $('#delete-topic-panel').find('.close-delete-panel').on('click', function () {
        $('.container-blured').removeClass('blur');
        $('#delete-topic-panel').attr('style', 'display:none');
    });

    $('#delete-topic-panel').find('.delete-butt').on('click', function () {
        $('.container-blured').removeClass('blur');
        $('#delete-topic-panel').attr('style', 'display:none');

        $.ajax({
            url: url_object.ajax_url,
            type: 'POST',
            data: {
                action: 'delete_topic',
                topic_id: topic_id,
                user_id: user_id
            },

            success: function (res) {
                console.log(res);
                window.location.replace(curr_category_url);
            },
            error: function (error) {
                console.log(error);
            }
        });
    });


    $('.dropdown').on('click', function() {
        dropdown($(this))
    });

    function dropdown(d) {
        d.find('.dropdown-content').attr('style', 'display:block');
        d.unbind('click');

        d.on('click', function () {
            d.find('.dropdown-content').attr('style', '');
            d.unbind('click');
            d.on('click',  function() {
                dropdown(d);
            });
        })
    }



    $('.star-full').on('click', function () {

        $(".star-empty").removeClass('none');
        $(".star-full").addClass('none');

        $.ajax({
            url: url_object.ajax_url,
            type: 'POST',
            data: {
                action: 'change_fav',
                topic_id: topic_id,
                user_id: user_id,
                state: 'delete'
            },

            success: function (res) {
                console.log(res);
                console.log('disliked');
            },
            error: function (error) {
                console.log(error);
            }
        });

    });

    $('.star-empty').on('click', function () {

        $(".star-empty").addClass('none');
        $(".star-full").removeClass('none');
        $.ajax({
            url: url_object.ajax_url,
            type: 'POST',
            data: {
                action: 'change_fav',
                topic_id: topic_id,
                user_id: user_id,
                state: 'add'
            },

            success: function (res) {
                console.log('liked');
            },
            error: function (error) {
                console.log(error);
            }
        });
    });




    document.addEventListener("touchstart", function(){}, true);



});



