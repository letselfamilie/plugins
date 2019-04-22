let $ = jQuery;

let fs = require('fs');
let ejs = require('ejs');

let category_templ = ejs.compile(fs.readFileSync("./forum/js/ejs_templates/forum_category.ejs", "utf8"));

$(function () {
    let $category_table = $("#categories_list");

    var current_page = 1;
    var max_page = 1;
    var per_page = 1;


    initPagination();
    function initPagination() {
        $.ajax({
            url: url_object.ajax_url,
            type: 'POST',
            data: {
                action: 'n_pages',
                per_page: per_page
            },
            success: function (res) {
                max_page = res;
                var pagina_from = 1;
                var pagina_to = max_page > 5 ? 5 : max_page;
                for (var i = 2; i <= pagina_to; i++) {
                    $('.forward-arrow').before("<a class='num' href='#'>" + i + "</a>")
                }


                $('.pagination').find('.num').on('click', function () {
                    $('.pagination').find('.active').removeClass('active');
                    $(this).addClass('active');
                    current_page = parseInt($(this).text());
                    console.log(current_page + "/" + max_page);
                    getCategories();
                });

                $('.pagination').find('.back-arrow').on('click', function () {
                    if (current_page > pagina_from) {
                        let $n = $('.pagination').find('.active');
                        $n.removeClass('active');
                        $n.prev().addClass('active');
                        current_page -= 1;
                        getCategories();
                    } else if (current_page > 1) {
                        pagina_to--;
                        pagina_from--;
                        current_page--;
                        $('.num').each(function (n) {
                            $(this).text(parseInt($( this ).text()) - 1)
                        })
                    }
                    console.log(current_page + "/" + max_page);
                });

                $('.pagination').find('.forward-arrow').on('click', function () {
                    if (current_page < pagina_to) {
                        let $n = $('.pagination').find('.active');
                        $n.removeClass('active');
                        $n.next().addClass('active');
                        current_page += 1;
                        getCategories()
                    } else if (current_page < max_page) {
                        pagina_to++;
                        pagina_from++;
                        current_page++;
                        $('.num').each(function (n) {
                            $(this).text(parseInt($( this ).text()) + 1)
                        })
                    }
                    console.log(current_page + "/" + max_page);
                });
            },
            error: function (error) {
                console.log(error);
            }
        });
    }

    // "<a class='num' href='#'>$i</a>";

    if($category_table) {
        getCategories();
    }

    function getCategories() {
        $.ajax({
            url: url_object.ajax_url,
            type: 'POST',
            data: {
                action: 'get_forum_categories',
                page_number: current_page,
                per_page: per_page
            },

            success: function (res) {
                res = JSON.parse(res);
                console.log(res);
                displayCategories(res, category_templ, $category_table);
            },
            error: function (error) {
                console.log(error);
            }
        });
    }


    function displayCategories(data, template, container) {
        container.html("");
        data.forEach(function (item) {
            let $node = $(template({category: item, url: url_object.site_url}));
            $node.find('.cat-name').on('click', function () {
                window.open( url_object.site_url + "/topics/?cat_name=" + encodeURI(item.cat_name), "_self" );
            });
            container.append($node);
        });
    }



    document.addEventListener("touchstart", function(){}, true);
});