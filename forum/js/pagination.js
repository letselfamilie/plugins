module.exports = function(curr_page, max_page, n_pages = 5, updateFunc, pagination_obj) {

    max_page = (max_page > 0) ? max_page : 1;

    pagination_obj = {
        current_page: curr_page,
        pagina_from: 1,
        pagina_to: max_page > n_pages ? n_pages : max_page
    }


    createNums();
    let $n = $('.before-dots');
    $n.next().addClass('active');

    function createNums() {
        for (var i = pagination_obj.pagina_from; i <= pagination_obj.pagina_to; i++) {
            $('.after-dots').before("<a class='num' href='#'>" + i + "</a>")
        }
    }

    setUpNums();

    function setUpNums() {
        $('.pagination').find('.num').on('click', function () {
            $('.pagination').find('.active').removeClass('active');
            $(this).addClass('active');
            pagination_obj.current_page = parseInt($(this).text());
            console.log(pagination_obj.current_page + "/" + max_page);
            updateFunc(pagination_obj.current_page);
        });
    }

    threeDots();

    function threeDots() {
        if (pagination_obj.current_page == 1 || pagination_obj.pagina_from == 1) {
            $('.before-dots').css('display', 'none');
        } else {
            $('.before-dots').css('display', 'inline-block');
        }

        if (pagination_obj.pagina_to == max_page || pagination_obj.current_page == max_page) {
            $('.after-dots').css('display', 'none');
        } else {
            $('.after-dots').css('display', 'inline-block');
        }

    }

    $('.pagination').find('.back-arrow').on('click', function () {
        if (pagination_obj.current_page > pagination_obj.pagina_from) {
            let $n = $('.pagination').find('.active');
            $n.removeClass('active');
            $n.prev().addClass('active');
            pagination_obj.current_page -= 1;
            updateFunc(pagination_obj.current_page);
        } else if (current_page > 1) {
            pagination_obj.pagina_to--;
            pagination_obj.pagina_from--;
            pagination_obj.current_page--;
            $('.num').each(function (n) {
                $(this).text(parseInt($(this).text()) - 1)
            });
            updateFunc(pagination_obj.current_page);
            threeDots();
        }
        console.log(pagination_obj.current_page + "/" + max_page);
    });

    $('.pagination').find('.forward-arrow').on('click', function () {
        if (pagination_obj.current_page < pagination_obj.pagina_to) {
            let $n = $('.pagination').find('.active');
            $n.removeClass('active');
            $n.next().addClass('active');
            pagination_obj.current_page += 1;
            updateFunc(pagination_obj.current_page);
        } else if (current_page < max_page) {
            pagination_obj.pagina_to++;
            pagination_obj.pagina_from++;
            pagination_obj.current_page++;
            $('.num').each(function (n) {
                $(this).text(parseInt($(this).text()) + 1)
            });
            updateFunc(pagination_obj.current_page);
            threeDots();
        }
        console.log(pagination_obj.current_page + "/" + max_page);
    });

    $('.pagination').find('.back-end-arrow').on('click', function () {
        if (pagination_obj.current_page != 1) {

            $('.num').remove();

            pagination_obj.pagina_to = pagination_obj.pagina_to - (pagination_obj.pagina_from - 1);
            pagination_obj.pagina_from = 1;
            createNums();
            setUpNums();

            let $n = $('.before-dots');
            $n.next().addClass('active');

            pagination_obj.current_page = 1;
            updateFunc(pagination_obj.current_page);
            threeDots();
        }
    });

    $('.pagination').find('.forward-end-arrow').on('click', function () {
        if (pagination_obj.current_page != max_page) {

            $('.num').remove();

            pagination_obj.pagina_from = pagination_obj.pagina_from + (max_page - pagination_obj.pagina_to);
            pagination_obj.pagina_to = max_page;
            createNums();
            setUpNums();

            let $n = $('.after-dots');
            $n.prev().addClass('active');


            pagination_obj.current_page = max_page;
            updateFunc(pagination_obj.current_page);
            threeDots();
        }
    });
}