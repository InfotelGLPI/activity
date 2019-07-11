function activity_initMenu(params) {
    $(document).ready(function () {
        //Inject activity menu
        var element = $("#header");
        if (element != null && element != undefined) {
            $("#header").after("<div id='plugin_activity_menu'>\n\
                                <a href='javascript:void(0);' class='plugin_activity_close'></a>\n\
                                <div id='activity_load_menu' style='color:#fff'></div>\n\
                                </div>\n\
                                <a href='javascript:void(0);' class='plugin_activity_menu_btn'></a>");
            activity_showMenu(params);
        }
    });
}

function activity_showMenu(params) {
    $(".plugin_activity_close").hide();

    var isMenuOpen = false;

    $('.plugin_activity_menu_btn').click(function () {
        // Load menu on click only once
        if ($('#activity_load_menu').html() == '') {
            $.ajax({
                type: 'POST',
                url: params.root_doc + '/plugins/activity/ajax/lateralmenu.php',
                data: {
                    'action': 'showMenu'
                },
                success: function (data, textStatus, jqXHR) {
                    var menu = data;
                    $("#activity_load_menu").html(menu);

                    var scripts, scriptsFinder = /<script[^>]*>([\s\S]+?)<\/script>/gi;
                    while (scripts = scriptsFinder.exec(menu)) {
                        eval(scripts[1]);
                    }
                },
                error: function (jqXHR, textStatus, errorThrown) {
                }
            });
        }

        if (isMenuOpen == false) {
            //alert('je suis dans le bon cas')
            $("#plugin_activity_menu").clearQueue().animate({
                right: '0'
            })
            $("#plugin_activity_page").clearQueue().animate({
                "margin-left": '-290px'
            })

            $(this).fadeOut(200).hide();
            $(".plugin_activity_close").fadeIn(300).show();

            isMenuOpen = true;
        }
    });

    $('.plugin_activity_close').click(function () {
        if (isMenuOpen == true) {
            $("#plugin_activity_menu").clearQueue().animate({
                right: '-240px'
            })
            $("#plugin_activity_page").clearQueue().animate({
                "margin-left": '0px'
            })

            $(this).fadeOut(200).hide();
            $(".plugin_activity_menu_btn").fadeIn(300).show();

            isMenuOpen = false;
        }
    });

    $('.plugin_activity_button, #page, #header').click(function () {
        if (isMenuOpen == true) {
            $("#plugin_activity_menu").clearQueue().animate({
                right: '-240px'
            })
            $("#plugin_activity_page").clearQueue().animate({
                "margin-left": '0px'
            })

            $('.plugin_activity_close').fadeOut(200).hide();
            $(".plugin_activity_menu_btn").fadeIn(300).show();

            isMenuOpen = false;
        }
    });

}