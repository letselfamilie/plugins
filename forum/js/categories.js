let $ = jQuery;

let fs = require('fs');
let ejs = require('ejs');

let category_templ = ejs.compile(fs.readFileSync("./forum/js/ejs_templates/forum_category.ejs", "utf8"));

$(function () {
    let $category_table = $("#categories_list");

    if($category_table) {
        getCategories();
    }

    function getCategories() {
        $.ajax({
            url: url_object.ajax_url,
            type: 'POST',
            data: {
                action: 'get_forum_categories',
                page_number: 1,
                per_page: 5
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



    $('.pagination').find('a').on('click', function () {
        $('.pagination').find('.active').removeClass('active');
        $(this).addClass('active');
    });

    document.addEventListener("touchstart", function(){}, true);
});