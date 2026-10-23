/*!
    * Start Bootstrap - SB Admin v6.0.0 (https://startbootstrap.com/templates/sb-admin)
    * Copyright 2013-2020 Start Bootstrap
    * Licensed under MIT (https://github.com/BlackrockDigital/startbootstrap-sb-admin/blob/master/LICENSE)
    */
    (function($) {
    "use strict";

    // Add active state to sidbar nav links
    var path = window.location.href; // because the 'href' property of the DOM element is the absolute path
        $("#layoutSidenav_nav .sb-sidenav a.nav-link").each(function() {
            if (this.href === path) {
                $(this).addClass("active");

                // Expand the parent collapse panel (and mark its toggle link active)
                // when the active link lives inside a submenu
                var $collapseParent = $(this).closest(".collapse");
                if ($collapseParent.length) {
                    $collapseParent.addClass("show");
                    $('a[data-target="#' + $collapseParent.attr("id") + '"]')
                        .removeClass("collapsed")
                        .attr("aria-expanded", "true");
                }
            }
        });

    // Toggle the side navigation
    $("#sidebarToggle").on("click", function(e) {
        e.preventDefault();
        $("body").toggleClass("sb-sidenav-toggled");
    });
})(jQuery);
