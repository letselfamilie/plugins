let $ = jQuery;

let fs = require('fs');
let ejs = require('ejs');

let category_templ = ejs.compile(fs.readFileSync("./forum/js/ejs_templates/forum_category.ejs", "utf8"));

let paginationInit = require('./pagination');

$(function () {
    let $category_table = $("#categories_list");

    var current_page = 1;
    var per_page = 10;

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
                paginationInit(current_page, max_page, 5, getCategories, {});
            },
            error: function (error) {
                console.log(error);
            }
        });
    }

    if($category_table) {
        getCategories(current_page);
    }

    function getCategories(page) {
        $.ajax({
            url: url_object.ajax_url,
            type: 'POST',
            data: {
                action: 'get_forum_categories',
                page_number: page,
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