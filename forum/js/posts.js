let $ = jQuery;

let fs = require('fs');
let ejs = require('ejs');

Date.prototype.ddmmyyyyhhmm = function() {
    var mm = this.getMonth() + 1;
    var dd = this.getDate();

    var HH = this.getHours();
    var MM = this.getMinutes();
    return ((dd>9 ? '' : '0') + dd) + '-' + ((mm>9 ? '' : '0') + mm) +  '-' + this.getFullYear() + ' ' +
        ((HH > 9 ? '' : '0') + HH) + ':' + ((MM > 9 ? '' : '0') + MM);
};

let post_templ = ejs.compile(fs.readFileSync("./forum/js/ejs_templates/forum_post.ejs", "utf8"));

let paginationInit = require('./pagination');

function decodeUrl(){

    let search = location.search.substring(1);
    console.log(search)
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

    var scroll_down = false;

    var pagination_obj = {current_page: (url_params['pag'] == null ? 1 : url_params['pag'])};
    var per_page = 20;
    var max_page = 0;

    if (topic_id == -1) {
        window.location.replace(url_object.site_url + "/categories");
    }

    getInfAboutTopic();
    // loadMyInfo();
    setUpListeners();

    initPagination(pagination_obj.current_page);
    function initPagination(curr_p=1) {
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
                pagination_obj.current_page = curr_p
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

                    $.ajax({
                        url: url_object.ajax_url,
                        type: 'POST',
                        data: {
                            action: 'n_posts_pages',
                            per_page: per_page,
                            topic_id: topic_id
                        },
                        success: function (res) {
                            scroll_down = true;
                            initPagination(res);
                        },
                        error: function (error) {
                            console.log(error);
                        }
                    });

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
                    $("#topic_date").text(new Date(res['create_timestamp'].replace(/\s/, 'T')).ddmmyyyyhhmm());
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
        pagination_obj.current_page = page;
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
                history.pushState(null, '', url_object.site_url + '/posts/?topic_id=' + topic_id + '&pag=' + page);
                console.log(window.location.href);

                console.log(res);
                res = JSON.parse(res);
                console.log(res);
                res.forEach(function (item) {
                    addPost(item)
                });
                setTimeout(function () {
                    $('.hide').removeClass('hide');
                    loader(false);
                    if (scroll_down) {
                        console.log('scroll down');
                        document.body.scrollTop = document.body.scrollHeight; // For Safari
                        document.documentElement.scrollTop = document.body.scrollHeight; // For Chrome, Firefox, IE and Opera
                        scroll_down = !scroll_down;
                    }
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
                user_id: user_id,
                url: url_object.template_directory,
                role: user_object.role}));

        $node.addClass('hide');

        var is_liked = data.liked == '1';
        var n_likes = parseInt(data.n_likes);
        var n_responds = parseInt(data.n_responds);


        if (user_id > 0) {
            $node.on('click', '.comment-full', function () {
                var post_text = data.post_message.substring(0, 75) + ((data.post_message.length <= 75) ? '' : "...");
                $("#quote-text").text(data.login + ': ' + post_text);

                $('.respond-info').css('display', 'inline-block');

                respond_to_id = data.post_id;
                console.log(respond_to_id);

                $("#enter-textarea").focus();
            });

            $node.on('click', '.comment-empty', function () {
                var post_text = data.post_message.substring(0, 75) + ((data.post_message.length <= 75) ? '' : "...");
                $("#quote-text").text(data.login + ': ' + post_text);

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

                $node.find('.comment-full').addClass('hide');
                $node.find('.comment-empty').addClass('hide');
                $node.find('.like-number').addClass('hide');
                $node.find('.reaction-number').addClass('hide');
                $node.find('.empty-like').addClass('hide');
                $node.find('.full-like').addClass('hide');

                $node.find('.post-text').attr('style', 'padding-bottom:8px;');
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

                            $node.find('.comment-full').removeClass('hide');
                            $node.find('.comment-empty').removeClass('hide');
                            $node.find('.like-number').removeClass('hide');
                            $node.find('.reaction-number').removeClass('hide');
                            $node.find('.empty-like').removeClass('hide');
                            $node.find('.full-like').removeClass('hide');
                            $node.find('.post-text').attr('style', '');
                        },
                        error: function (error) {
                            console.log(error);
                        }
                    });
                }
            });

            $node.on('click', '.send-message', function () {
                $.ajax({
                    url: url_object.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'add_dialog',
                        user_to: data.user_id
                    },
                    success: function (res) {
                        window.location.href = url_object.site_url + "/chat?dialog_id=" + res;
                    },
                    error: function (error) {
                        console.log(error);
                    }
                });
            });

        } else {
            $node.on('click', '.comment-empty', function () {
                window.location.href =  url_object.site_url + "/login";
            });

            $node.on('click', '.empty-like', function () {
                window.location.href =  url_object.site_url + "/login";
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
                loadPost($('.active').text());
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

    window.onscroll = function() {scrollFunction()};
    function scrollFunction() {
        // var up = false
        // if (document.body.scrollTop > 20 || document.documentElement.scrollTop > 20) {
        //     up = true
        //     document.getElementById("down-butt").style.display = "block";
        // } else {
        //     document.getElementById("down-butt").style.display = "none";
        // }
        //
        // var down = false
        // if (document.body.scrollTop < document.body.scrollHeight - 20 ||
        //     document.documentElement.scrollTop < document.body.scrollHeight - 20) {
        //     var down = true
        //     document.getElementById("top-butt").style.display = "block";
        // } else {
        //     document.getElementById("top-butt").style.display = "none";
        // }
        //
        // if (down && up) {
        //     document.getElementById("down-butt").style.bottom = "20px";
        //     $('#up-butt').css('bottom: 67px');
        // } else if (down) {
        //     document.getElementById("down-butt").style.bottom = "20px";
        // } else {
        //     $('#up-butt').css('bottom: 20px');
        // }


    }

    $('#top-butt').on('click', function () {
        document.body.scrollTop = 0; // For Safari
        document.documentElement.scrollTop = 0; // For Chrome, Firefox, IE and Opera
    })

    $('#down-butt').on('click', function () {
        document.body.scrollTop = document.body.scrollHeight; // For Safari
        document.documentElement.scrollTop = document.body.scrollHeight; // For Chrome, Firefox, IE and Opera
    })
});

